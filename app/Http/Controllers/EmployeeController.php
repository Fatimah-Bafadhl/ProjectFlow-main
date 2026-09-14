<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\SystemActivityNotification;

class EmployeeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية استعراض هذه الصفحة.');
        }

        $tasks = Task::with(['assignedUser'])->latest()->get();
        $projects = Project::all();
        $clients = Client::all();
               $employees = Employee::with(['projects', 'user'])->withCount('tasks')->get();
        $managers  = User::where('role', \App\Enums\Role::Manager)
                         ->withCount('managedProjects')
                         ->latest()
                         ->get();

        return view('employees.index', compact('employees', 'managers', 'tasks', 'projects', 'clients'));
    }

    public function store(Request $request)
    {
        abort(404);
    if (!Auth::user()->isAdmin()) {
    abort(403, 'عذراً، لا تمتلك صلاحية إضافة موظف.');
}
    

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:employees,email',
            'phone'      => 'required|string|max:20',
        ]);

        $validated['user_id'] = auth()->id();
        $employee = Employee::create($validated);

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'إضافة موظف',
                'تم إضافة الموظف: ' . $employee->name,
                route('employees.index')
            ));
        }

        return redirect()->back()->with('success', 'تم إضافة الموظف بنجاح');
    }

    public function update(Request $request, Employee $employee)
    {
             if (!Auth::user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية تعديل بيانات موظف.');
             }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:employees,email,' . $employee->employee_id . ',employee_id',
            'phone'      => 'required|string|max:20',
        ]);

                $employee->update($validated);

        if ($employee->user_id) {
            User::where('user_id', $employee->user_id)->update(['username' => $employee->name]);
        }

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'تعديل موظف',
                'تم تعديل بيانات الموظف: ' . $employee->name,
                route('employees.index')
            ));
        }

        return redirect()->back()->with('success', 'تم تعديل بيانات الموظف بنجاح');
    }

        public function destroy(Employee $employee)
    {
           if (!Auth::user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية حذف موظف.');
           }

        $employeeName = $employee->name;
        $linkedUserId = $employee->user_id;

        DB::transaction(function () use ($employee, $linkedUserId) {
            $employee->delete();

            if ($linkedUserId) {
                User::where('user_id', $linkedUserId)->delete();
            }
        });

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'حذف موظف',
                'تم حذف الموظف: ' . $employeeName,
                route('employees.index')
            ));
        }

        return redirect()->back()->with('success', 'تم حذف الموظف بنجاح');
    }
}