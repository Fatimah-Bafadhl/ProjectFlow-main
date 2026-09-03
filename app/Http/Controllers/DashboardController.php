<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class DashboardController extends Controller
{
   public function index()
{
    $user = Auth::user();

    // 1. تحديد نطاق المشاريع والمهام المرئية حسب الدور
     $employeeId = null;
    if ($user->isAdmin()) {
        $projectBase = Project::query();
        $taskBase = Task::query();

    } elseif ($user->isManager()) {
        $projectIds = $user->managedProjects()->pluck('projects.project_id');
        $projectBase = Project::whereIn('project_id', $projectIds);
        $taskBase = Task::whereIn('project_id', $projectIds);

    } elseif ($user->isEmployee()) {
        $employee = Employee::where('user_id', $user->user_id)->first();
        $employeeId = $employee->employee_id ?? 0;
        $taskBase = Task::where('assigned_to', $employeeId);
        $projectIds = (clone $taskBase)->pluck('project_id')->unique();
        $projectBase = Project::whereIn('project_id', $projectIds);

    } else { // Client
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

    // 4. المشاريع الأخيرة ضمن النطاق المسموح فقط
     $recentProjects = (clone $projectBase)->with(['tasks' => function ($query) use ($user, $employeeId) {
        if ($user->isEmployee()) {
            $query->where('assigned_to', $employeeId ?? 0);
        }
    }])->latest()->take(5)->get();
    return view('dashboard.index', compact(
        'totalProjects', 'totalTasks',
        'projectCompletedCount', 'taskCompletedCount',
        'projectInReviewCount', 'taskInReviewCount',
        'projectInProgressCount', 'taskInProgressCount',
        'projectPendingCount', 'taskPendingCount',
        'projectPausedCount', 'taskPausedCount',
        'recentProjects'
    ));
}
}