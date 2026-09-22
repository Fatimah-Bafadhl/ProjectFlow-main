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
use App\Http\Requests\UpdatePersonRequest;
use App\Services\PersonProfileService;


class EmployeeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية استعراض هذه الصفحة.');
        }

        $projects = Project::all();
        $clients = Client::all();
               $employees = Employee::with(['projects', 'user'])->withCount('tasks')->get();
               $managers  = User::where('role', \App\Enums\Role::Manager)
                         ->with('managedProjects')
                         ->withCount('managedProjects')
                         ->latest()
                         ->get();

        return view('employees.index', compact('employees', 'managers', 'projects', 'clients'));    }

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

    public function update(UpdatePersonRequest $request, Employee $employee, PersonProfileService $accounts)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية تعديل بيانات موظف.');
        }

        if (!$employee->user_id) {
            return redirect()->back()->withErrors(['account' => 'لا يمكن تعديل موظف بلا حساب مستخدم.']);
        }

        $accounts->update($employee->user, $request->validated());

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'تعديل موظف',
                'تم تعديل بيانات الموظف: ' . $employee->refresh()->name,
                route('employees.index')
            ));
        }

        return redirect()->back()->with('success', 'تم تعديل بيانات الموظف بنجاح');
    }

public function destroy(Employee $employee, \App\Services\AccountLifecycle $accounts)    {
           if (!Auth::user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية حذف موظف.');
           }

        $employeeName = $employee->name;
        $linkedUserId = $employee->user_id;

             try {
            $accounts->delete($employee, auth()->user());
        } catch (\App\Services\AccountLifecycleException $e) {
            return redirect()->back()->withErrors(['account' => $e->getMessage()]);
        }

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