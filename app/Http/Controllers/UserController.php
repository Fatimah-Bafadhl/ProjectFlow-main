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

    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'username'     => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email|unique:employees,email|unique:clients,email',
            'password'     => 'required|string|min:8',
            'role'         => 'required|in:admin,manager,employee,client',
            'phone'        => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
                        'project_ids'   => 'nullable|array',
            'project_ids.*' => 'exists:projects,project_id',
           // 'project_id' => 'nullable|exists:projects,project_id',
           // 'department'   => 'required_if:role,employee|string|max:255',
           // 'project_id'   => 'required_if:role,client|exists:projects,project_id',
            'company_name' => 'required_if:role,client|nullable|string|max:255',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        if ($validated['role'] === 'employee') {
    Employee::create([
        'user_id'    => $user->user_id,
        'name'       => $user->username,
        'department' => $validated['department'],
        'email'      => $user->email,
        'phone'      => $user->phone,
    ]);
} elseif ($validated['role'] === 'client') {
    $projectIds = $validated['project_ids'] ?? [];
    $firstProject = !empty($projectIds) ? Project::find($projectIds[0]) : null;

    $client = Client::create([
        'user_id'      => $user->user_id,
        'name'         => $user->username,
        'company_name' => $validated['company_name'],
        'email'        => $user->email,
        'phone'        => $user->phone,
        'project_name' => $firstProject?->project_name,
    ]);

    if (!empty($projectIds)) {
        $client->projects()->attach($projectIds);
    }
}

        auth()->user()->notify(new SystemActivityNotification(
            'إضافة مستخدم',
            'تم إضافة المستخدم: ' . $user->username . ' بصلاحية ' . $user->role->value,
            route('users.index')
        ));

        return redirect()->back()->with('success', 'تم إضافة المستخدم بنجاح');
    }

        public function update(Request $request, User $user)
    {
        $oldRole = $user->role->value;

        $validated = $request->validate([
            'username'     => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'password'     => 'nullable|string|min:8',
            'role'         => 'required|in:admin,manager,employee,client',
            'phone'        => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'department'   => 'required_if:role,employee|string|max:255',
            'project_ids'   => 'nullable|array',
            'project_ids.*' => 'exists:projects,project_id',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        $newRole = $user->role->value;

        if ($oldRole !== $newRole) {
            $this->handleRoleChange($user, $oldRole, $newRole, $request);
        }

        
        if ($user->role === \App\Enums\Role::Client && $request->has('project_ids')) {
            $client = Client::where('user_id', $user->user_id)->first();

            if ($client) {
                $client->projects()->sync($validated['project_ids']);

                $firstProject = Project::find($validated['project_ids'][0] ?? null);
                if ($firstProject) {
                    $client->update(['project_name' => $firstProject->project_name]);
                }
            }
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
                Task::where('assigned_to', $employee->employee_id)->update(['assigned_to' => null]);
                $employee->projects()->detach();
                $employee->delete();
            }
        }
    }

    public function destroy(User $user)
    {
        if ($user->user_id === auth()->id()) {
            abort(403, 'لا يمكنك حذف حسابك الخاص.');
        }

        $username = $user->username;
        $user->delete();

        auth()->user()->notify(new SystemActivityNotification(
            'حذف مستخدم',
            'تم حذف المستخدم: ' . $username,
            route('users.index')
        ));

        return redirect()->back()->with('success', 'تم حذف المستخدم بنجاح');
    }
}