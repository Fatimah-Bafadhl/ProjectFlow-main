@extends('layouts.app')
@section('title', 'الصفحة الرئيسية')
@section('content-class', 'p-4 flex-grow-1 bg-white')

@section('content')

@php
    $isClient = auth()->user()->isClient();
@endphp

<!-- شبكة الكروت الإحصائية -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-5" dir="rtl">
    <!-- إجمالي المشاريع -->
    <div class="col">
        <div class="stat-card text-end">
            <div class="stat-number">{{ $totalProjects ?? 0 }}</div>
            <div class="stat-label">
                <span>اجمالي المشاريع</span>
                <i class="fa-solid fa-briefcase"></i>
            </div>
        </div>
    </div>
    
    <!-- إجمالي المهام -->
     @if(!$isClient)
    <div class="col">
        <div class="stat-card text-end">
            <div class="stat-number">{{ $totalTasks ?? 0 }}</div>
            <div class="stat-label">
                <span>اجمالي المهام</span>
                <i class="fa-solid fa-list-check"></i>
            </div>
        </div>
    </div>
    @endif
    
    <!-- مكتملة -->
    <div class="col">
        <div class="stat-card text-end">
            <div class="stat-number">{{  $isClient ? $projectCompletedCount : $projectCompletedCount + $taskCompletedCount }}</div>
            <div class="stat-label">
                <span>مكتملة</span>
                <i class="fa-regular fa-circle-check text-success"></i>
            </div>
            <div class="stat-subtext">
    @if($isClient) مشاريع: {{ $projectCompletedCount }} @else مشاريع: {{ $projectCompletedCount }} | مهام: {{ $taskCompletedCount }} @endif
</div>
        </div>
    </div>
    
    <!-- قيد المراجعة -->
    <div class="col">
        <div class="stat-card text-end">
            <div class="stat-number">{{  $isClient ? $projectInReviewCount : $projectInReviewCount + $taskInReviewCount }}</div>
            <div class="stat-label">
                <span>قيد المراجعة</span>
                <i class="fa-regular fa-clipboard"></i>
            </div>
<div class="stat-subtext">
    @if($isClient) مشاريع: {{ $projectInReviewCount }} @else مشاريع: {{ $projectInReviewCount }} | مهام: {{ $taskInReviewCount }} @endif
</div>
        </div>
    </div>
    
    <!-- قيد التنفيذ -->
    <div class="col">
        <div class="stat-card text-end">
            <div class="stat-number">{{ $isClient ? $projectInProgressCount : $projectInProgressCount + $taskInProgressCount }}</div>
            <div class="stat-label">
                <span>قيد التنفيذ</span>
                <i class="fa-solid fa-users-gear"></i>
            </div>
         <div class="stat-subtext">
    @if($isClient) مشاريع: {{ $projectInProgressCount }} @else مشاريع: {{ $projectInProgressCount }} | مهام: {{ $taskInProgressCount }} @endif
</div>
        </div>
    </div>
    
    <!-- قيد الانتظار -->
    <div class="col">
        <div class="stat-card text-end">
            <div class="stat-number">{{$isClient ? $projectPendingCount : $projectPendingCount + $taskPendingCount }}</div>
            <div class="stat-label">
                <span>قيد الانتظار</span>
                <i class="fa-solid fa-bars-staggered"></i>
            </div>
          <div class="stat-subtext">
    @if($isClient) مشاريع: {{ $projectPendingCount }} @else مشاريع: {{ $projectPendingCount }} | مهام: {{ $taskPendingCount }} @endif
</div>
        </div>
    </div>
    
    <!-- متوقف مؤقتاً -->
    <div class="col">
        <div class="stat-card text-end">
            <div class="stat-number">{{$isClient ? $projectPausedCount : $projectPausedCount + $taskPausedCount }}</div>
            <div class="stat-label">
                <span>متوقف مؤقتاً</span>
                <i class="fa-regular fa-circle-stop"></i>
            </div>
            <div class="stat-subtext">
    @if($isClient) مشاريع: {{ $projectPausedCount }} @else مشاريع: {{ $projectPausedCount }} | مهام: {{ $taskPausedCount }} @endif
</div>
        </div>
    </div>
</div>

@if(!$isClient)
<!-- قسم المشاريع الأخيرة -->
<div class="recent-projects-section mt-4" dir="rtl">
    <h2 class="task-page-title mb-3">المشاريع الأخيرة</h2>
    
    <div class="recent-projects-container p-3 p-md-4">
        <div class="d-flex flex-column gap-3 recent-projects-scroll">
           @forelse($recentProjects as $project)
    @php
       $isClient = auth()->user()->isClient();
$totalProjectTasks = ($project->tasks && !$isClient) ? $project->tasks->count() : 0;
$completedProjectTasks = ($project->tasks && !$isClient) ? $project->tasks->where('status', 'مكتملة')->count() : 0;
        // أخذ نسبة الإنجاز مباشرة من المشروع تماماً مثل صفحة المشاريع
        $progressPercentage = $project->progress ?? 0;
        
        // جلب الحالة من عمود status مباشرة مع القيمة الافتراضية
        $currentStatus = trim($project->status ?? 'قيد التنفيذ');
        if ($currentStatus === '') {
            $currentStatus = 'قيد التنفيذ';
        }
        
        // تحديد الأيقونة والكلاس المتوافق مع تنسيق الحالات في الـ CSS
        $statusIcon = match($currentStatus) {
            'مكتملة' => 'fa-regular fa-circle-check text-success',
            'قيد المراجعة' => 'fa-regular fa-clipboard',
            'قيد التنفيذ' => 'fa-solid fa-users-gear',
            'قيد الانتظار' => 'fa-solid fa-bars-staggered',
            'متوقف مؤقتاً', 'متوقف مؤقتا' => 'fa-regular fa-circle-stop',
            default => 'fa-solid fa-users-gear'
        };
    @endphp
                <div class="project-item-card p-3">
                    
                    <!-- السطر الأول: اسم المشروع وتحته اسم الشركة باتجاه اليمين + الحالة والأيقونة يسار -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="text-end">
                            <h3 class="fw-bold fs-6 mb-1 text-dark">{{ $project->project_name }}</h3>
                            <div class="text-muted small text-end">{{ $project->company_name ?? 'مشروع خاص' }}</div>
                        </div>
                        <div class="status-header d-flex align-items-center gap-2">
                            <span class="fw-medium" style="font-size: 0.825rem; color: #334155;">{{ $currentStatus }}</span>
                            <i class="{{ $statusIcon }} status-icon"></i>
                        </div>
                    </div>

                    <!-- السطر الثاني: المهام والتاريخ يمين + النسبة وشريط التقدم يسار -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-2">
                        <!-- جهة اليمين: المهام وتاريخ الانتهاء -->
                        <div class="d-flex align-items-center gap-3">
                            @if(!$isClient)
                            <div class="fw-bold small text-dark">
                                المهام {{ $completedProjectTasks }}/{{ $totalProjectTasks }}
                            </div>
                            @endif
                            <div class="text-muted small">
                                تاريخ الانتهاء : {{ $project->end_project ? \Carbon\Carbon::parse($project->end_project)->locale('ar')->translatedFormat('d F Y') : 'غير محدد' }}
                            </div>
                        </div>

                        <!-- جهة اليسار: النسبة المئوية وشريط التقدم -->
                        <div class="d-flex align-items-center gap-2" style="width: 180px;">
                            <span class="small text-muted fw-semibold">{{ $progressPercentage }}%</span>
                            <div class="progress custom-progress flex-grow-1" style="height: 7px;" dir="ltr">
                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ $progressPercentage }}" 
                                     class="progress-bar custom-bg-purple" role="progressbar" 
                                     style="width: {{ $progressPercentage }}%;"></div>
                            </div>
                        </div>
                    </div>

                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-folder-open fa-2x mb-2 text-secondary" style="color: #8A84AD;"></i>
                    <p class="fw-semibold fs-6 mb-0">لا توجد مشاريع مضافة حديثاً</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endif
@endsection