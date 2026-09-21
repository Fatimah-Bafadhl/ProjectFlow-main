<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Employee;
use App\Models\Client;
use App\Notifications\SystemActivityNotification;

class TrashController extends Controller
{
    protected function resolveModel(string $type): string
    {
        return match ($type) {
            'project'  => Project::class,
            'task'     => Task::class,
            'employee' => Employee::class,
            'client'   => Client::class,
            default    => abort(404, 'نوع غير معروف.'),
        };
    }

    public function index()
    {
        $projects  = Project::withArchived()->onlyTrashed()->get();
        $tasks     = Task::onlyTrashed()->with('project')->get();
        $employees = Employee::onlyTrashed()->get();
        $clients   = Client::onlyTrashed()->get();

        return view('trash.index', compact('projects', 'tasks', 'employees', 'clients'));
    }

    public function restore(string $type, $id)
    {
        $modelClass = $this->resolveModel($type);

        $query = $type === 'project'
            ? $modelClass::withArchived()->onlyTrashed()
            : $modelClass::onlyTrashed();

        $record = $query->findOrFail($id);
        $record->restore();

        $label = $this->labelFor($record, $type);

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'استعادة من المحذوفات',
                'تم استعادة: ' . $label,
                route('trash.index')
            ));
        }

        return redirect()->back()->with('success', 'تم الاستعادة بنجاح');
    }

    public function forceDelete(string $type, $id)
    {
        $modelClass = $this->resolveModel($type);

        $query = $type === 'project'
            ? $modelClass::withArchived()->onlyTrashed()
            : $modelClass::onlyTrashed();

        $record = $query->findOrFail($id);
        $label = $this->labelFor($record, $type);

        try {
            $record->forceDelete();
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
        };
    }
}