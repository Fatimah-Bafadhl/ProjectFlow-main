<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Notifications\SystemActivityNotification;

class TaskController extends Controller
{
    

   public function index()
{
    $user = auth()->user();

            if ($user->isManager()) {
        $managedProjectIds = $user->managedProjects()->pluck('projects.project_id');
        $tasks = Task::whereIn('project_id', $managedProjectIds)->with(['project', 'attachments'])->get();
        $projects = $user->managedProjects()->with('stages')->get();
           } elseif ($user->isEmployee()) {
            $employee = Employee::where('user_id', $user->user_id)->first();
            $employeeId = $employee->employee_id ?? 0;
            $tasks = Task::whereHas('assignedEmployees', fn ($q) => $q->where('employees.employee_id', $employeeId))
                ->with(['project', 'attachments'])
                ->get();
            $projectIds = $tasks->pluck('project_id')->unique();
            $projects = Project::whereIn('project_id', $projectIds)->with('stages')->get();
    } else {
        $tasks = Task::with(['project', 'attachments'])->get();
        $projects = Project::with('stages')->get();
    }

    $employees = Employee::all();

    return view('tasks.index', compact('tasks', 'projects', 'employees'));
}

   public function create()
{
    if (auth()->user()->isClient() || auth()->user()->isEmployee()) {
        abort(403, 'عذراً، لا تمتلك صلاحية إضافة مهام.');
    }

    $projects = auth()->user()->isManager()
        ? auth()->user()->managedProjects
        : Project::all();

    $employees = Employee::all();
    return view('tasks.create', compact('projects', 'employees'));
}

    public function store(Request $request)
    {
        if (auth()->user()->isClient() || auth()->user()->isEmployee()) {
            abort(403, 'عذراً، لا تمتلك صلاحية إضافة مهام.');
        }

               $request->validate([
            'project_id'       => 'required|exists:projects,project_id',
            'task_title'       => 'required|string|max:255',
            'task_description' => 'required|string',
            'priority'         => 'required|in:منخفض,متوسط,عالي',
            'status'           => 'required|string',
            'start_task'       => 'required|date',
            'end_task'         => 'required|date|after_or_equal:start_task',
                      'assigned_to'      => 'required|array|min:1',
            'assigned_to.*'    => 'exists:employees,employee_id',
            'stage_id'         => [
                'required',
                \Illuminate\Validation\Rule::exists('project_stages', 'project_stage_id')
                    ->where('project_id', $request->project_id),
            ],

             'attachments'      => 'nullable|array',
            'attachments.*'    => 'file|max:20480',
        ]);

        if (auth()->user()->isManager() && !auth()->user()->managedProjects()->where('projects.project_id', $request->project_id)->exists()) {
    abort(403, 'عذراً، لا تمتلك صلاحية إضافة مهام لهذا المشروع.');
}
              // $task = Task::create($request->all());
                       $task = new Task($request->only([
            'task_title', 'task_description', 'status', 'priority', 'start_task', 'end_task', 'company_name',
        ]));
        $task->project_id = $request->project_id;
        $task->stage_id = $request->stage_id;
        $task->save();

        $task->assignedEmployees()->sync($request->assigned_to);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file->isValid()) {
                    continue;
                }
                $path = $file->store('task_attachments', 'public');
                $task->attachments()->create([
                    'type'              => 'file',
                    'title'             => $file->getClientOriginalName(),
                    'file_path'         => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'added_by_user_id'  => auth()->id(),
                    'added_by_name'     => auth()->user()->username,
                ]);
            }
        }

        // المهام هي التي تحدد حالة المشروع ونسبته تلقائياً
        if ($task->project && method_exists($task->project, 'syncStatus')) {
            $task->project->syncStatus();
        }

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'إضافة مهمة',
                'تم إضافة مهمة جديدة: ' . $task->task_title,
                route('tasks.show', $task->task_id)
            ));
        }

        return redirect()->route('tasks.index')->with('success', 'تم إضافة المهمة بنجاح وتحديث حالة المشروع');
    }

public function show($id)
{
            $task = Task::with(['project', 'stage', 'attachments'])->findOrFail($id);

    if (auth()->user()->isClient()) {
$client = auth()->user()->client;
        if (!$client || !$client->projects()->where('projects.project_id', $task->project_id)->exists()) {
            abort(403, 'عذراً، ليس لديك صلاحية استعراض هذه المهمة.');
        }
    } elseif (auth()->user()->isManager()) {
        if (!auth()->user()->managedProjects()->where('projects.project_id', $task->project_id)->exists()) {
            abort(403, 'عذراً، ليس لديك صلاحية استعراض هذه المهمة.');
        }

           } elseif (auth()->user()->isEmployee()) {
            $employee = Employee::where('user_id', auth()->user()->user_id)->first();
            $employeeId = $employee->employee_id ?? 0;
            if (! $task->assignedEmployees()->where('employees.employee_id', $employeeId)->exists()) {
                abort(403, 'عذراً، ليس لديك صلاحية استعراض هذه المهمة.');
            }
        }

        $projects = collect();
    $employees = collect();
    if (auth()->user()->isAdmin() || auth()->user()->isManager()) {
        $projects = auth()->user()->isManager()
            ? auth()->user()->managedProjects()->with('stages')->get()
            : Project::with('stages')->get();
        $employees = Employee::all();
    }

    return view('tasks.project-show', compact('task', 'projects', 'employees'));
}
    public function edit($id)
{
    if (auth()->user()->isClient()) {
        abort(403, 'عذراً، لا تمتلك صلاحية تعديل المهام.');
    }

    $task = Task::findOrFail($id);

    if (auth()->user()->isManager() && !auth()->user()->managedProjects()->where('projects.project_id', $task->project_id)->exists()) {
        abort(403, 'عذراً، لا تمتلك صلاحية تعديل هذه المهمة.');
    }

           if (auth()->user()->isEmployee()) {
            $employee = Employee::where('user_id', auth()->user()->user_id)->first();
            $employeeId = $employee->employee_id ?? 0;
            if (! $task->assignedEmployees()->where('employees.employee_id', $employeeId)->exists()) {
                abort(403, 'عذراً، لا تمتلك صلاحية تعديل هذه المهمة.');
            }
        }

        $projects = auth()->user()->isManager()
            ? auth()->user()->managedProjects
            : Project::all();

        $employees = Employee::all();
        return view('tasks.edit', compact('task', 'projects', 'employees'));
}

    public function update(Request $request, $id)
    {
        if (auth()->user()->isClient()) {
            abort(403, 'عذراً، لا تمتلك صلاحية تعديل المهام.');
        }

        $task = Task::findOrFail($id);
       // $oldProjectId = $task->project_id;

        if (auth()->user()->isManager() && !auth()->user()->managedProjects()->where('projects.project_id', $task->project_id)->exists()) {
    abort(403, 'عذراً، لا تمتلك صلاحية تعديل هذه المهمة.');
}

               if (auth()->user()->isEmployee()) {
            $employee = Employee::where('user_id', auth()->user()->user_id)->first();
            $employeeId = $employee->employee_id ?? 0;
            if (! $task->assignedEmployees()->where('employees.employee_id', $employeeId)->exists()) {
                abort(403, 'عذراً، لا تمتلك صلاحية تعديل هذه المهمة.');
            }

            $request->validate(['status' => 'required|string']);
        $task->update(['status' => $request->status]);
            
            // تحديث حالة المشروع بناءً على المهام بعد تعديل الموظف للحالة
            if ($task->project && method_exists($task->project, 'syncStatus')) {
                $task->project->syncStatus();
            }

            return redirect()->back()->with('success', 'تم تحديث حالة المهمة وتحديث المشروع بنجاح');
        }

                                       $request->validate([
            'task_title'       => 'required|string|max:255',
            'task_description' => 'required|string',
            'priority'         => 'required|in:منخفض,متوسط,عالي',
            'status'           => 'required|string',
            'start_task'       => 'required|date',
            'end_task'         => 'required|date|after_or_equal:start_task',
            'assigned_to'      => 'required|array|min:1',
            'assigned_to.*'    => 'exists:employees,employee_id',
            'stage_id'         => [
                'nullable',
                \Illuminate\Validation\Rule::exists('project_stages', 'project_stage_id')
                    ->where('project_id', $task->project_id),
            ],
            'attachments'      => 'nullable|array',
            'attachments.*'    => 'file|max:20480',
        ]);

        $task->update($request->only([
            'task_title', 'task_description', 'status', 'priority', 'start_task', 'end_task', 'company_name',
        ]));
        $task->stage_id = $request->stage_id;
        $task->save();

        $task->assignedEmployees()->sync($request->assigned_to);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file->isValid()) {
                    continue;
                }
                $path = $file->store('task_attachments', 'public');
                $task->attachments()->create([
                    'type'              => 'file',
                    'title'             => $file->getClientOriginalName(),
                    'file_path'         => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'added_by_user_id'  => auth()->id(),
                    'added_by_name'     => auth()->user()->username,
                ]);
            }
        }

        // تحديث حالة المشروع بعد تعديل المهمة
        if ($task->project && method_exists($task->project, 'syncStatus')) {
            $task->project->syncStatus();
        }

        return redirect()->route('tasks.index')->with('success', 'تم تعديل المهمة وتحديث حالة المشروع بنجاح');
    }

    public function destroy($id)
    {
        if (auth()->user()->isClient() || auth()->user()->isEmployee()) {
            abort(403, 'عذراً، لا تمتلك صلاحية حذف المهام.');
        }

        $task = Task::findOrFail($id);

        if (auth()->user()->isManager() && !auth()->user()->managedProjects()->where('projects.project_id', $task->project_id)->exists()) {
        abort(403, 'عذراً، لا تمتلك صلاحية حذف هذه المهمة.');}

        $project = $task->project; 
        $task->delete();

        // تحديث حالة المشروع تلقائياً بعد حذف المهمة
        if ($project && method_exists($project, 'syncStatus')) {
            $project->syncStatus();
        }

        return redirect()->route('tasks.index')->with('success', 'تم حذف المهمة وتحديث حالة المشروع بنجاح');
    }
}