<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Project;
use App\Models\Task;
use App\Models\Employee;
use App\Models\Client;
use App\Models\User;
use App\Notifications\SystemActivityNotification;
use App\Services\AccountLifecycle;
use App\Services\AccountLifecycleException;

class TrashController extends Controller
{
    /** Types whose delete/restore/force-delete goes through AccountLifecycle. */
    private const PEOPLE_TYPES = ['user', 'employee', 'client'];

    protected function resolveModel(string $type): string
    {
        return match ($type) {
            'project'  => Project::class,
            'task'     => Task::class,
            'employee' => Employee::class,
            'client'   => Client::class,
            'user'     => User::class,
            default    => abort(404, 'نوع غير معروف.'),
        };
    }

        public function index()
    {
        $projects = Project::withArchived()->onlyTrashed()->get();

        // Only top-level tasks. A task trashed together with its project comes back with the project.
        $tasks = Task::onlyTrashed()->whereHas('project')->with('project')->get();

        // Only rows whose user is also trashed (or missing, for legacy). This hides employee rows
        // left over from an employee -> manager role change, where the user stays active.
        $personVisible = fn ($q) => $q->whereNull('user_id')->orWhereHas('user', fn ($u) => $u->onlyTrashed());

        $employees = Employee::onlyTrashed()->where($personVisible)->get();
        $clients   = Client::onlyTrashed()->where($personVisible)->get();

        $users = User::onlyTrashed()
            ->whereIn('role', [Role::Admin->value, Role::Manager->value])
            ->get();

        return view('trash.index', compact('projects', 'tasks', 'employees', 'clients', 'users'));
    }

        public function restore(string $type, $id, AccountLifecycle $accounts)
    {
        $modelClass = $this->resolveModel($type);

        $query = $type === 'project'
            ? $modelClass::withArchived()->onlyTrashed()
            : $modelClass::onlyTrashed();

        $record = $query->findOrFail($id);
        $label = $this->labelFor($record, $type);

        if ($type === 'task') {
            $projectTrashed = Project::withArchived()->onlyTrashed()
                ->where('project_id', $record->project_id)->exists();

            if ($projectTrashed) {
                return redirect()->back()->withErrors([
                    'trash' => 'لا يمكن استعادة المهمة لأن مشروعها ما زال في المحذوفات. استعيدي المشروع أولاً.',
                ]);
            }
        }

        try {
            if (in_array($type, self::PEOPLE_TYPES, true)) {
                $accounts->restore($record);
            } else {
                $record->restore();
            }
        } catch (AccountLifecycleException $e) {
            return redirect()->back()->withErrors(['trash' => $e->getMessage()]);
        }

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'استعادة من المحذوفات',
                'تم استعادة: ' . $label,
                route('trash.index')
            ));
        }

        return redirect()->back()->with('success', 'تم الاستعادة بنجاح');
    }

       public function forceDelete(string $type, $id, AccountLifecycle $accounts)
    {
        $modelClass = $this->resolveModel($type);

        $query = $type === 'project'
            ? $modelClass::withArchived()->onlyTrashed()
            : $modelClass::onlyTrashed();

               $record = $query->findOrFail($id);
        $label = $this->labelFor($record, $type);

        try {
            if (in_array($type, self::PEOPLE_TYPES, true)) {
                $accounts->forceDelete($record);
            } else {
                $record->forceDelete();
            }
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withErrors([
                'trash' => 'تعذر الحذف النهائي لـ "' . $label . '". لم يتم تغيير أي بيانات.',
            ]);
        }

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'حذف نهائي',
                'تم الحذف النهائي: ' . $label,
                route('trash.index')
            ));
        }

        return redirect()->back()->with('success', 'تم الحذف النهائي بنجاح');
    }

       protected function labelFor($record, string $type): string
    {
        return match ($type) {
            'project'  => $record->project_name,
            'task'     => $record->task_title,
            'employee' => $record->name,
            'client'   => $record->name,
            'user'     => $record->username,
        };
    }
}