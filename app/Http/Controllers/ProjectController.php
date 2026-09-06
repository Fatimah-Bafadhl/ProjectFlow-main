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
         $projects = $user->managedProjects()
            ->with(['employee', 'tasks', 'managers', 'employees'])
            ->withCount([
                'comments',
                'tickets as open_tickets_count' => function ($query) {
                    $query->where('status', '!=', \App\Enums\TicketStatus::Handled);
                },
            ])
            ->get();
    } elseif ($user->isEmployee()) {
        $employee = Employee::where('user_id', $user->user_id)->first();
        $employeeId = $employee->employee_id ?? 0;
        $projectIds = Task::where('assigned_to', $employeeId)->pluck('project_id')->unique();
        $projects = Project::whereIn('project_id', $projectIds)->with(['employee', 'tasks', 'managers', 'employees'])->get();
    } else {
        $projects = Project::with(['employee', 'tasks', 'managers', 'employees'])
            ->withCount([
                'comments',
                'tickets as open_tickets_count' => function ($query) {
                    $query->where('status', '!=', \App\Enums\TicketStatus::Handled);
                },
            ])
            ->get();
    }
       $managers = User::where('role', 'manager')->get();
    $employees = Employee::all();
    $allUsers = User::whereIn('role', ['admin', 'manager'])->get();

    return view('projects.index', compact('projects', 'managers', 'employees', 'allUsers'));
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
            'creator_name'        => auth()->user()->username,
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

$newEmployeeIds = $request->input('employee_ids', []);
$currentEmployeeIds = $project->employees()->pluck('employees.employee_id')->toArray();
$removedEmployeeIds = array_diff($currentEmployeeIds, $newEmployeeIds);

if (! empty($removedEmployeeIds)) {
    Task::where('project_id', $project->project_id)
        ->whereIn('assigned_to', $removedEmployeeIds)
        ->update(['assigned_to' => null]);
}

$project->employees()->sync($newEmployeeIds);

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
    // جمع معرفات حسابات المستخدمين للموظفين المسندة إليهم مهام في هذا المشروع، قبل الحذف
    $assignedEmployeeIds = Task::where('project_id', $project->project_id)
        ->whereNotNull('assigned_to')
        ->pluck('assigned_to')
        ->unique();

    $assignedUserIds = Employee::whereIn('employee_id', $assignedEmployeeIds)
        ->pluck('user_id')
        ->filter()
        ->unique();

    $projectName = $project->project_name;

    $project->delete();

    foreach ($assignedUserIds as $userId) {
        $employeeUser = User::find($userId);
        if ($employeeUser) {
            $employeeUser->notify(new SystemActivityNotification(
                'تم حذف مشروع',
                'تم حذف المشروع الذي كانت مسندة إليك مهام فيه: ' . $projectName,
                route('projects.index')
            ));
        }
    }

    return redirect()->route('projects.index')->with('success', 'تم حذف المشروع بنجاح!');
}

    public function reassignCreator(Request $request, $project_id)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'عذراً، فقط المدير العام يمتلك صلاحية تصحيح منشئ المشروع.');
        }

        $project = Project::withArchived()->withTrashed()->findOrFail($project_id);

        $request->validate([
            'new_creator_id' => 'required|exists:users,user_id',
        ]);

        $newCreator = User::find($request->new_creator_id);

        $project->update([
            'user_id'      => $newCreator->user_id,
            'creator_name' => $newCreator->username,
        ]);

        return redirect()->back()->with('success', 'تم تصحيح منشئ المشروع بنجاح');
    }

        
   
}