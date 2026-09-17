<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Ticket;
use App\Models\Client;
use App\Models\Comment;
use App\Models\ProjectDocument;

class DashboardController extends Controller
{
   public function index(Request $request)
{
    $user = Auth::user();

    // 1. تحديد نطاق المشاريع والمهام المرئية حسب الدور
     $employeeId = null;
       if ($user->isAdmin()) {
        $projectBase = Project::query();
        $taskBase = Task::query();

        // Alerts strip queries (Admin only)
        $overdueProjectsCount = (clone $projectBase)->where('end_project', '<', now())->where('status', '!=', 'مكتملة')->count();
        $overdueTasksCount = (clone $taskBase)->where('end_task', '<', now())->where('status', '!=', 'مكتملة')->count();
               $openTicketsCount = Ticket::where('status', 'open')->count();
        $unassignedTasksCount = (clone $taskBase)->whereDoesntHave('assignedEmployees')->count();
              // KPI row queries (Admin only)
        $activeProjectsCount = (clone $projectBase)->where('status', '!=', 'مكتملة')->count();
        $activeTasksCount = (clone $taskBase)->where('status', '!=', 'مكتملة')->count();
        $totalClientsCount = Client::count();

                // Team composition (Admin only) — Employee rows only exist for role=Employee
        // (Managers are promoted-out and soft-deleted from `employees`, per your role-switch logic)
        $nonManagerEmployeesCount = Employee::count();
        $managersCount = \App\Models\User::where('role', \App\Enums\Role::Manager)->count();
        $totalStaffCount = $nonManagerEmployeesCount + $managersCount;

                    // Pipeline distribution (Admin only)
        $pipelineRange = $request->query('pipeline_range', '30d');
        [$pipelineStart, $pipelineEnd] = $this->resolveRange(
            $pipelineRange,
            $request->query('pipeline_from'),
            $request->query('pipeline_to')
        );

        $pipelineStagesRaw = \App\Models\ProjectStage::whereHas('project', function($q) use ($pipelineStart, $pipelineEnd) {
            $q->where('status', '!=', 'مكتملة');
            if ($pipelineStart && $pipelineEnd) {
                $q->whereBetween('created_at', [$pipelineStart, $pipelineEnd]);
            }
        })
        ->select('stage_key', 'status', \DB::raw('count(*) as total'))
        ->groupBy('stage_key', 'status')
        ->get();

        $pipelineDistribution = [];
        foreach (\App\Enums\ProjectStageName::cases() as $stage) {
            $pipelineDistribution[$stage->value] = [
                'label' => $stage->label(),
                'color' => $stage->color(),
                'count' => 0
            ];
        }

               foreach ($pipelineStagesRaw as $row) {
            // Casts on ProjectStage return enum objects — extract the raw string values.
            $statusValue   = $row->status instanceof \App\Enums\ProjectStageStatus
                                ? $row->status->value
                                : $row->status;
            $stageKeyValue = $row->stage_key instanceof \App\Enums\ProjectStageName
                                ? $row->stage_key->value
                                : $row->stage_key;

            if ($statusValue === \App\Enums\ProjectStageStatus::InProgress->value
                && isset($pipelineDistribution[$stageKeyValue])) {
                $pipelineDistribution[$stageKeyValue]['count'] += (int) $row->total;
            }
        }

        // Deadlines this week (Admin only) — next 14 days, future only, excluding completed.
        $deadlinesThisWeek = (clone $projectBase)
            ->whereBetween('end_project', [now(), now()->addDays(14)])
            ->where('status', '!=', 'مكتملة')
            ->orderBy('end_project')
            ->take(6)
            ->get(['project_id', 'project_name', 'company_name', 'end_project']);

        // Team load (Admin only) — active tasks grouped by assignee, top 5.
               $activeTaskIds = (clone $taskBase)->where('status', '!=', 'مكتملة')->pluck('task_id');

        $teamLoadRaw = \DB::table('task_employee')
            ->whereIn('task_id', $activeTaskIds)
            ->select('employee_id', \DB::raw('count(*) as total'))
            ->groupBy('employee_id')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $employeeIds = $teamLoadRaw->pluck('employee_id')->filter()->unique()->values()->all();
        $employeesMap = \App\Models\Employee::whereIn('employee_id', $employeeIds)
            ->get(['employee_id', 'name'])
            ->keyBy('employee_id');

        $teamLoad = $teamLoadRaw->map(function ($row) use ($employeesMap) {
            $emp = $employeesMap->get($row->employee_id);
            return [
                'name'  => $emp ? $emp->name : 'غير معروف',
                'count' => (int) $row->total,
            ];
        });

                // Activity feed (Admin only) — latest handled tickets, comments, documents.
        $activityRange = $request->query('activity_range', '30d');
        [$activityStart, $activityEnd] = $this->resolveRange(
            $activityRange,
            $request->query('activity_from'),
            $request->query('activity_to')
        );

        $activityTickets = Ticket::where('status', 'handled')
            ->with('project')
            ->when($activityStart && $activityEnd, fn($q) => $q->whereBetween('created_at', [$activityStart, $activityEnd]))
            ->latest()
            ->take(4)
            ->get()

            ->map(function ($ticket) {
                return [
                    'type'       => 'ticket',
                    'title'      => 'تذكرة تمت معالجتها',
                    'text'       => \Illuminate\Support\Str::limit($ticket->message, 60),
                    'author'     => $ticket->client_name,
                    'created_at' => $ticket->created_at,
                    'url'        => $ticket->project ? route('projects.show', $ticket->project_id) : '#',
                ];
            });

                $activityComments = Comment::with(['task', 'project'])
            ->when($activityStart && $activityEnd, fn($q) => $q->whereBetween('created_at', [$activityStart, $activityEnd]))
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($comment) {
                $url = '#';
                if ($comment->task_id) {
                    $url = route('tasks.show', $comment->task_id);
                } elseif ($comment->project_id) {
                    $url = route('projects.show', $comment->project_id);
                }
                return [
                    'type'       => 'comment',
                    'title'      => 'تعليق جديد',
                    'text'       => \Illuminate\Support\Str::limit($comment->comment_text, 60),
                    'author'     => $comment->author_name ?? 'مستخدم',
                    'created_at' => $comment->created_at,
                    'url'        => $url,
                ];
            });

              $activityDocuments = ProjectDocument::when($activityStart && $activityEnd, fn($q) => $q->whereBetween('created_at', [$activityStart, $activityEnd]))
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($doc) {
                return [
                    'type'       => 'document',
                    'title'      => 'مستند جديد: ' . $doc->title,
                    'text'       => $doc->type === 'link' ? 'رابط' : ($doc->original_filename ?? 'ملف'),
                    'author'     => $doc->added_by_name ?? 'مستخدم',
                    'created_at' => $doc->created_at,
                    'url'        => route('projects.show', $doc->project_id),
                ];
            });

        $activityFeed = $activityTickets
            ->concat($activityComments)
            ->concat($activityDocuments)
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

    } elseif ($user->isManager()) {
        $projectIds = $user->managedProjects()->pluck('projects.project_id');
        $projectBase = Project::whereIn('project_id', $projectIds);
        $taskBase = Task::whereIn('project_id', $projectIds);

            } elseif ($user->isEmployee()) {
            $employee = Employee::where('user_id', $user->user_id)->first();
            $employeeId = $employee->employee_id ?? 0;
            $taskBase = Task::whereHas('assignedEmployees', fn ($q) => $q->where('employees.employee_id', $employeeId));
            $projectIds = (clone $taskBase)->pluck('project_id')->unique();
            $projectBase = Project::whereIn('project_id', $projectIds);
        }else { // Client
        $client = $user->client;
        $projectIds = $client ? $client->projects()->pluck('projects.project_id') : collect();
        $projectBase = Project::whereIn('project_id', $projectIds);
        $taskBase = null; // العملاء لا يرون المهام إطلاقاً
    }

    // 2. الإجماليات
    $totalProjects = (clone $projectBase)->count();
    $totalTasks = $taskBase ? (clone $taskBase)->count() : 0;

    // 3. عداد المشاريع والمهام لكل حالة
    $projectCompletedCount = (clone $projectBase)->where('status', 'مكتملة')->count();
    $taskCompletedCount = $taskBase ? (clone $taskBase)->where('status', 'مكتملة')->count() : 0;

    $projectInReviewCount = (clone $projectBase)->where('status', 'قيد المراجعة')->count();
    $taskInReviewCount = $taskBase ? (clone $taskBase)->where('status', 'قيد المراجعة')->count() : 0;

    $projectInProgressCount = (clone $projectBase)->where('status', 'قيد التنفيذ')->count();
    $taskInProgressCount = $taskBase ? (clone $taskBase)->where('status', 'قيد التنفيذ')->count() : 0;

    $projectPendingCount = (clone $projectBase)->where('status', 'قيد الانتظار')->count();
    $taskPendingCount = $taskBase ? (clone $taskBase)->where('status', 'قيد الانتظار')->count() : 0;

    $projectPausedCount = (clone $projectBase)->where('status', 'متوقف مؤقتاً')->count();
    $taskPausedCount = $taskBase ? (clone $taskBase)->where('status', 'متوقف مؤقتاً')->count() : 0;

    return view('dashboard.index', compact(
        'totalProjects', 'totalTasks',
        'projectCompletedCount', 'taskCompletedCount',
        'projectInReviewCount', 'taskInReviewCount',
        'projectInProgressCount', 'taskInProgressCount',
        'projectPendingCount', 'taskPendingCount',
        'projectPausedCount', 'taskPausedCount',
        
        'overdueProjectsCount', 'overdueTasksCount', 'openTicketsCount', 'unassignedTasksCount',
        'activeProjectsCount', 'activeTasksCount', 'totalClientsCount', 'pipelineDistribution',
         'deadlinesThisWeek', 'teamLoad','activityFeed', 
        'nonManagerEmployeesCount', 'managersCount', 'totalStaffCount'
    ));
}
/**
 * Resolve a preset + optional custom range into a [Carbon, Carbon] tuple.
 * Returns [null, null] for the "all" preset (no filter).
 */
private function resolveRange(?string $preset, ?string $from, ?string $to): array
{
    $now = now();
    switch ($preset) {
        case 'today':
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        case '7d':
            return [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()];
        case '30d':
            return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()];
        case 'custom':
            $start = $from ? \Carbon\Carbon::parse($from)->startOfDay() : $now->copy()->subDays(29)->startOfDay();
            $end   = $to   ? \Carbon\Carbon::parse($to)->endOfDay()     : $now->copy()->endOfDay();
            return [$start, $end];
        case 'all':
        default:
            return [null, null];
    }
}
/**
 * AJAX — Pipeline distribution data for the current filter.
 */
public function pipelineData(Request $request)
{
    [$start, $end] = $this->resolveRange(
        $request->query('range', '30d'),
        $request->query('from'),
        $request->query('to')
    );

    $stagesRaw = \App\Models\ProjectStage::whereHas('project', function ($q) use ($start, $end) {
        $q->where('status', '!=', 'مكتملة');
        if ($start && $end) {
            $q->whereBetween('created_at', [$start, $end]);
        }
    })
    ->select('stage_key', 'status', \DB::raw('count(*) as total'))
    ->groupBy('stage_key', 'status')
    ->get();

    $distribution = [];
    foreach (\App\Enums\ProjectStageName::cases() as $stage) {
        $distribution[$stage->value] = [
            'label' => $stage->label(),
            'color' => $stage->color(),
            'count' => 0,
        ];
    }

    foreach ($stagesRaw as $row) {
        $statusValue   = $row->status instanceof \App\Enums\ProjectStageStatus ? $row->status->value : $row->status;
        $stageKeyValue = $row->stage_key instanceof \App\Enums\ProjectStageName ? $row->stage_key->value : $row->stage_key;

        if ($statusValue === \App\Enums\ProjectStageStatus::InProgress->value && isset($distribution[$stageKeyValue])) {
            $distribution[$stageKeyValue]['count'] += (int) $row->total;
        }
    }

    return response()->json([
        'labels' => array_values(array_column($distribution, 'label')),
        'counts' => array_values(array_column($distribution, 'count')),
        'colors' => array_values(array_column($distribution, 'color')),
        'total'  => array_sum(array_column($distribution, 'count')),
    ]);
}

/**
 * AJAX — Activity feed HTML for the current filter.
 */
public function activityFeedData(Request $request)
{
    [$start, $end] = $this->resolveRange(
        $request->query('range', '30d'),
        $request->query('from'),
        $request->query('to')
    );

    $activityTickets = Ticket::where('status', 'handled')
        ->with('project')
        ->when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
        ->latest()->take(4)->get()
        ->map(function ($ticket) {
            return [
                'type'       => 'ticket',
                'title'      => 'تذكرة تمت معالجتها',
                'text'       => \Illuminate\Support\Str::limit($ticket->message, 60),
                'author'     => $ticket->client_name,
                'created_at' => $ticket->created_at,
                'url'        => $ticket->project ? route('projects.show', $ticket->project_id) : '#',
            ];
        });

    $activityComments = Comment::with(['task', 'project'])
        ->when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
        ->latest()->take(4)->get()
        ->map(function ($comment) {
            $url = '#';
            if ($comment->task_id) {
                $url = route('tasks.show', $comment->task_id);
            } elseif ($comment->project_id) {
                $url = route('projects.show', $comment->project_id);
            }
            return [
                'type'       => 'comment',
                'title'      => 'تعليق جديد',
                'text'       => \Illuminate\Support\Str::limit($comment->comment_text, 60),
                'author'     => $comment->author_name ?? 'مستخدم',
                'created_at' => $comment->created_at,
                'url'        => $url,
            ];
        });

    $activityDocuments = ProjectDocument::when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
        ->latest()->take(4)->get()
        ->map(function ($doc) {
            return [
                'type'       => 'document',
                'title'      => 'مستند جديد: ' . $doc->title,
                'text'       => $doc->type === 'link' ? 'رابط' : ($doc->original_filename ?? 'ملف'),
                'author'     => $doc->added_by_name ?? 'مستخدم',
                'created_at' => $doc->created_at,
                'url'        => route('projects.show', $doc->project_id),
            ];
        });

    $activityFeed = $activityTickets
        ->concat($activityComments)
        ->concat($activityDocuments)
        ->sortByDesc('created_at')
        ->take(8)
        ->values();

    return view('dashboard.partials.activity-feed', ['activityFeed' => $activityFeed]);
}

}