<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Notifications\SystemActivityNotification;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Services\PersonProfileService;

class UserController extends Controller
{
       public function index()
    {
        $users = User::latest()->get();
        $roles = Role::cases();
        $projects = Project::all();

        $clientProjectIds = Client::with('projects')
            ->get()
            ->mapWithKeys(fn ($client) => [
                $client->user_id => $client->projects->pluck('project_id')->toArray(),
            ]);

        return view('users.index', compact('users', 'roles', 'projects', 'clientProjectIds'));
    }

    public function store(StorePersonRequest $request, PersonProfileService $accounts)
    {
        $user = $accounts->create($request->validated());

        auth()->user()->notify(new SystemActivityNotification(
            'إضافة مستخدم',
            'تم إضافة المستخدم: ' . $user->username . ' بصلاحية ' . $user->role->value,
            route('users.index')
        ));

        return redirect()->back()->with('success', 'تم إضافة المستخدم بنجاح');
    }
        

    public function update(UpdatePersonRequest $request, User $user)
    {
        $oldRole = $user->role->value;

        $accounts = app(PersonProfileService::class);
        $user = $accounts->update($user, $request->validated());

        $newRole = $user->role->value;

        if ($oldRole !== $newRole) {
            $this->handleRoleChange($user, $oldRole, $newRole, $request);
        }

        auth()->user()->notify(new SystemActivityNotification(
            'تعديل مستخدم',
            'تم تعديل بيانات المستخدم: ' . $user->username,
            route('users.index')
        ));

        return redirect()->back()->with('success', 'تم تعديل بيانات المستخدم بنجاح');
    }

        private function handleRoleChange(User $user, string $oldRole, string $newRole, Request $request): void
    {
        if ($oldRole === 'manager' && $newRole === 'employee') {
            $user->managedProjects()->detach();

            $employee = Employee::withTrashed()->where('user_id', $user->user_id)->first();

            if ($employee) {
                $employee->restore();
                $employee->update([
                    'name'       => $user->username,
                    'department' => $request->department,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                ]);
            } else {
                Employee::create([
                    'user_id'    => $user->user_id,
                    'name'       => $user->username,
                    'department' => $request->department,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                ]);
            }
        }

       if ($oldRole === 'employee' && $newRole === 'manager') {
    $employee = Employee::where('user_id', $user->user_id)->first();

    if ($employee) {
        $employee->tasks()->detach();
        $employee->projects()->detach();
        $employee->delete();
    }
}
    }

        public function destroy(User $user, \App\Services\AccountLifecycle $accounts)
    {
        $username = $user->username;

        try {
            $accounts->delete($user, auth()->user());
        } catch (\App\Services\AccountLifecycleException $e) {
            return redirect()->back()->withErrors(['account' => $e->getMessage()]);
        }

        auth()->user()->notify(new SystemActivityNotification(
            'حذف مستخدم',
            'تم حذف المستخدم: ' . $username,
            route('users.index')
        ));

        return redirect()->back()->with('success', 'تم حذف المستخدم بنجاح');
    }
}