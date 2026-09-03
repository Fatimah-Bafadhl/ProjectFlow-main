<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Notifications\SystemActivityNotification;
use App\Models\User;
use App\Models\Employee;
use App\Models\Task;

class ProjectController extends Controller
{
   

   public function index()
{
        $user = auth()->user();

    if ($user->isClient()) {
     $client = $user->client;
        $client = $user->client;
        $projects = $client
            ? $client->projects()->with(['employee', 'tasks', 'managers', 'employees'])->get()
            : collect();
       } elseif ($user->isManager()) {
        $projects = $user->managedProjects()->with(['employee', 'tasks', 'managers', 'employees'])->get();
    } elseif ($user->isEmployee()) {
        $employee = Employee::where('user_id', $user->user_id)->first();
        $employeeId = $employee->employee_id ?? 0;
        $projectIds = Task::where('assigned_to', $employeeId)->pluck('project_id')->unique();
        $projects = Project::whereIn('project_id', $projectIds)->with(['employee', 'tasks', 'managers', 'employees'])->get();
    } else {
        $projects = Project::with(['employee', 'tasks', 'managers', 'employees'])->get();
    }
    $managers = User::where('role', 'manager')->get();
    $employees = Employee::all();

    return view('projects.index', compact('projects', 'managers', 'employees'));
}

    public function store(Request $request)
    {
        if (auth()->user()->isClient() || auth()->user()->isEmployee()) {
            abort(403, 'عذراً، لا تمتلك صلاحية إضافة مشاريع.');
        }

        $request->validate([
            'project_name'        => 'required|string|max:255',
    'company_name'        => 'required|string|max:255',
    'project_description' => 'required|string',
    'start_project'       => 'required|date|after_or_equal:today',
    'end_project'         => 'required|date|after_or_equal:start_project',
    'status'              => 'required|string',
    'manager_ids'         => 'nullable|array',
    'manager_ids.*'       => 'exists:users,user_id',
    'employee_ids'        => 'nullable|array',
    'employee_ids.*'      => 'exists:employees,employee_id',
        ]);

        $project = Project::create([
            'project_name'        => $request->project_name,
            'company_name'        => $request->company_name,
            'project_description' => $request->project_description,
            'start_project'       => $request->start_project,
            'end_project'         => $request->end_project,
            'status'              => $request->status,
            'user_id'             => auth()->id(),
        ]);

        if (auth()->user()->isManager()) {
    $project->managers()->attach(auth()->id());
} elseif (auth()->user()->isAdmin()) {
    $project->managers()->sync($request->input('manager_ids', []));
}

$project->employees()->sync($request->input('employee_ids', []));


        // مزامنة وتحديد الحالة بناءً على المهام
        if (method_exists($project, 'syncStatus')) {
            $project->syncStatus();
        }

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'إضافة مشروع',
                'تم إضافة مشروع جديد: ' . $project->project_name,
                route('projects.show', $project->project_id)
            ));
        }

        return redirect()->route('projects.index')->with('success', 'تم إضافة المشروع بنجاح');
    }

    public function show($id)
{
    $project = Project::withArchived()->with(['tasks', 'user'])->findOrFail($id);
 // $project = Project::with(['tasks', 'user'])->findOrFail($id);

    $isManagerOfThisProject = false;

    if (auth()->user()->isClient()) {
          $client = auth()->user()->client;
       $client = auth()->user()->client;
        if (!$client || !$client->projects()->where('projects.project_id', $project->project_id)->exists()) {
            abort(403, 'عذراً، ليس لديك صلاحية استعراض هذا المشروع.');
        }
       } elseif (auth()->user()->isManager()) {
        if (!$project->managers()->where('users.user_id', auth()->id())->exists()) {
            abort(403, 'عذراً، ليس لديك صلاحية استعراض هذا المشروع.');
        }
    } elseif (auth()->user()->isEmployee()) {
        $employee = Employee::where('user_id', auth()->user()->user_id)->first();
        $employeeId = $employee->employee_id ?? 0;
        if (!Task::where('project_id', $project->project_id)->where('assigned_to', $employeeId)->exists()) {
            abort(403, 'عذراً، ليس لديك صلاحية استعراض هذا المشروع.');
        }
    }
        elseif (auth()->user()->isAdmin()) {
        $isManagerOfThisProject = true;
    }

    return view('projects.show', compact('project', 'isManagerOfThisProject'));}

    public function update(Request $request, $id)
    {
        if (auth()->user()->isClient()) {
            abort(403, 'عذراً، لا تمتلك صلاحية تعديل المشاريع.');
        }

        $project = Project::findOrFail($id);

            if (auth()->user()->isManager() && !$project->managers()->where('users.user_id', auth()->id())->exists()) {
        abort(403, 'عذراً، لا تمتلك صلاحية تعديل هذا المشروع.');
    }

                    if (auth()->user()->isEmployee()) {
        abort(403, 'عذراً، لا تمتلك صلاحية تعديل حالة المشروع مباشرة.');
    }
        


        $request->validate([
              'project_name'        => 'required|string|max:255',
    'company_name'        => 'required|string|max:255',
    'project_description' => 'required|string',
    'start_project'       => ['required', 'date', 'after_or_equal:' . $project->start_project],
    'end_project'         => 'required|date|after_or_equal:start_project',
    'status'              => 'required|string',
    'manager_ids'         => 'nullable|array',
    'manager_ids.*'       => 'exists:users,user_id',
    'employee_ids'        => 'nullable|array',
    'employee_ids.*'      => 'exists:employees,employee_id',
        ]);

        $project->update([
            'project_name'        => $request->project_name,
            'company_name'        => $request->company_name,
            'project_description' => $request->project_description,
            'start_project'       => $request->start_project,
            'end_project'         => $request->end_project,
            'status'              => $request->status,
        ]);

        if (auth()->user()->isAdmin()) {
    $project->managers()->sync($request->input('manager_ids', []));
}

$project->employees()->sync($request->input('employee_ids', []));

        // تحديث حالة المشروع ونسبته بناءً على المهام بعد التعديل
        if (method_exists($project, 'syncStatus')) {
            $project->syncStatus();
        }

        return redirect()->route('projects.index')->with('success', 'تم تعديل المشروع بنجاح');
    }
    
    public function destroy($id)
{
    $project = Project::findOrFail($id);

    if (auth()->user()->isClient() || auth()->user()->isEmployee()) {
        abort(403, 'عذراً، لا تمتلك صلاحية حذف المشاريع.');
    }

    if (auth()->user()->isManager() && !$project->managers()->where('users.user_id', auth()->id())->exists()) {
        abort(403, 'عذراً، لا تمتلك صلاحية حذف هذا المشروع.');
    }

    $project->delete();
    return redirect()->route('projects.index')->with('success', 'تم حذف المشروع بنجاح!');
}
}