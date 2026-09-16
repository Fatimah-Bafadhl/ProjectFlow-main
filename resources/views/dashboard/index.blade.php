@extends('layouts.app')
@section('title', 'الصفحة الرئيسية')
@section('content-class', 'p-4 flex-grow-1 bg-white')

@section('content')

@php
    $isClient = auth()->user()->isClient();
@endphp

@if(auth()->user()->isAdmin())
<!-- شريط التنبيهات -->
<div class="row g-3 mb-4" dir="rtl">
    <div class="col-md-3">
        <a href="{{ route('projects.index', ['filter' => 'overdue']) }}" class="alert-card tone-red text-decoration-none">
            <div class="alert-icon"><i class="fa-solid fa-briefcase"></i></div>
            <div class="alert-content">
                <span class="alert-title">مشاريع متأخرة</span>
                <span class="alert-num">{{ $overdueProjectsCount }}</span>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('tasks.index', ['filter' => 'overdue']) }}" class="alert-card tone-red text-decoration-none">
            <div class="alert-icon"><i class="fa-solid fa-list-check"></i></div>
            <div class="alert-content">
                <span class="alert-title">مهام متأخرة</span>
                <span class="alert-num">{{ $overdueTasksCount }}</span>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        {{-- TODO: Change href to route('tickets.index') once a global tickets list page is created --}}
        <a href="#" class="alert-card tone-amber text-decoration-none">
            <div class="alert-icon"><i class="fa-solid fa-ticket"></i></div>
            <div class="alert-content">
                <span class="alert-title">تذاكر مفتوحة</span>
                <span class="alert-num">{{ $openTicketsCount }}</span>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('tasks.index', ['filter' => 'unassigned']) }}" class="alert-card tone-violet text-decoration-none">
            <div class="alert-icon"><i class="fa-solid fa-user-slash"></i></div>
            <div class="alert-content">
                <span class="alert-title">مهام غير معينة</span>
                <span class="alert-num">{{ $unassignedTasksCount }}</span>
            </div>
        </a>
    </div>
</div>
@endif

@if(auth()->user()->isAdmin())
<!-- KPI Row (Admin Only) -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-5" dir="rtl">
    <div class="col">
        <div class="kpi-card">
            <div class="kpi-icon violet"><i class="fa-solid fa-briefcase"></i></div>
            <div>
                <div class="kpi-num">{{ $activeProjectsCount ?? 0 }}</div>
                <div class="kpi-label">مشاريع نشطة</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="kpi-card">
            <div class="kpi-icon amber"><i class="fa-solid fa-list-check"></i></div>
            <div>
                <div class="kpi-num">{{ $activeTasksCount ?? 0 }}</div>
                <div class="kpi-label">مهام نشطة</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="kpi-card">
            <div class="kpi-icon red"><i class="fa-solid fa-ticket"></i></div>
            <div>
                <div class="kpi-num">{{ $openTicketsCount ?? 0 }}</div>
                <div class="kpi-label">تذاكر مفتوحة</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="kpi-num">{{ $totalClientsCount ?? 0 }}</div>
                <div class="kpi-label">إجمالي العملاء</div>
            </div>
        </div>
    </div>
</div>

<!-- Pipeline Distribution Chart + Team Composition -->
@php
    $totalPipelineProjects = collect($pipelineDistribution ?? [])->sum('count');
    $staffPct = $totalStaffCount > 0 ? round(($nonManagerEmployeesCount / $totalStaffCount) * 100) : 0;
    $mgrPct = $totalStaffCount > 0 ? 100 - $staffPct : 0;
@endphp
<div class="row g-4 mb-4" dir="rtl">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="section-title mb-1">توزيع المشاريع على مراحل التنفيذ</h5>
                    <p class="chart-subtitle mb-0">حالة سير العمل عبر 7 مراحل تشغيلية</p>
                </div>
                <span class="chart-total-badge">إجمالي {{ $totalPipelineProjects }} مشروعاً مسجلاً</span>
            </div>
            <div class="chart-wrapper">
                <canvas id="pipelineChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="section-title mb-3">تكوين الفريق</h5>
            <div class="team-donut-wrap">
                <div class="team-donut-holder">
                    <canvas id="teamDonut"></canvas>
                    <div class="team-donut-center">
                        <span class="num">{{ $totalStaffCount }}</span>
                        <span class="lbl">إجمالي الفريق</span>
                    </div>
                </div>
                <div class="team-donut-legend">
                    <div class="legend-row">
                        <span class="legend-dot" style="background:#8A84AD"></span>
                        <span class="legend-label">موظفون</span>
                        <span class="legend-val">{{ $nonManagerEmployeesCount }}</span>
                    </div>
                    <div class="legend-row">
                        <span class="legend-dot" style="background:#F59E0B"></span>
                        <span class="legend-label">مدراء</span>
                        <span class="legend-val">{{ $managersCount }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<!-- شبكة الكروت الإحصائية (Original for Non-Admin) -->
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
@endif

@if(auth()->user()->isAdmin())
<!-- Two-column: Deadlines / Team Load (Admin only) -->
<div class="row g-4 mb-4" dir="rtl">
    <!-- Deadlines this week -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="section-title mb-3">مواعيد التسليم القريبة</h5>
            @forelse($deadlinesThisWeek as $project)
                               @php
                    $daysLeft = \Carbon\Carbon::now()->startOfDay()
                        ->diffInDays(\Carbon\Carbon::parse($project->end_project)->startOfDay(), false);
                    if ($daysLeft <= 1) {
                        $urgency = 'urgent';
                        $urgencyLabel = $daysLeft <= 0 ? 'اليوم' : 'غداً';
                    } elseif ($daysLeft <= 5) {
                        $urgency = 'soon';
                        $urgencyLabel = 'خلال ' . $daysLeft . ' أيام';
                    } else {
                        $urgency = 'normal';
                        $urgencyLabel = \Carbon\Carbon::parse($project->end_project)->locale('ar')->translatedFormat('d F');
                    }
                @endphp
                <a href="{{ route('projects.show', $project->project_id) }}" class="deadline-row text-decoration-none">
                    <div class="deadline-icon tone-{{ $urgency }}">
                        <i class="fa-solid fa-flag"></i>
                    </div>
                    <div class="deadline-info">
                        <div class="deadline-name">{{ $project->project_name }}</div>
                        <div class="deadline-company">{{ $project->company_name }}</div>
                    </div>
                    <span class="deadline-chip tone-{{ $urgency }}">
                        <span class="chip-main">{{ $urgencyLabel }}</span>
                        @if($urgency === 'normal')
                            <span class="chip-sub">خلال {{ $daysLeft }} يوم</span>
                        @endif
                    </span>
                </a>
            @empty
                <p class="text-muted small mb-0">لا توجد مواعيد تسليم قريبة.</p>
            @endforelse
        </div>
    </div>

    <!-- Team load -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="section-title mb-3">حمل العمل على الفريق</h5>
            @php
                $maxTeamLoad = collect($teamLoad ?? [])->max('count') ?: 1;
            @endphp
                       <div class="team-load-scroll">
                @forelse($teamLoad as $member)
                    @php
                        $loadPct = round(($member['count'] / $maxTeamLoad) * 100);
                        $hue = 261 - ($loadPct / 100) * 257; // 261° violet at low load → ~4° red at max load
                        $initials = collect(preg_split('/\s+/', trim($member['name'])))
                            ->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                    @endphp
                    <div class="team-load-row">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div class="load-avatar">{{ $initials ?: '؟' }}</div>
                            <span class="team-load-name flex-grow-1">{{ $member['name'] }}</span>
                            <span class="team-load-count">{{ $member['count'] }} مهام</span>
                        </div>
                        <div class="team-load-track">
                            <div class="team-load-fill" style="width: {{ $loadPct }}%; background-color: hsl({{ $hue }}, 55%, 58%);"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">لا توجد مهام نشطة معينة لأي موظف.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isAdmin())
<!-- Activity Feed (Admin only) -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4" dir="rtl">
    <h5 class="section-title mb-3">آخر الأنشطة</h5>
    <div class="activity-timeline">
        @forelse($activityFeed as $item)
            <a href="{{ $item['url'] }}" class="activity-tl-item text-decoration-none">
                <span class="activity-tl-dot dot-{{ $item['type'] }}"></span>
                <div class="activity-tl-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="activity-tl-title">{{ $item['title'] }}</div>
                        <div class="activity-tl-time">{{ $item['created_at']->locale('ar')->diffForHumans() }}</div>
                    </div>
                    <div class="activity-tl-text">{{ $item['text'] }}</div>
                    <div class="activity-tl-author">{{ $item['author'] }}</div>
                </div>
            </a>
        @empty
            <p class="text-muted small mb-0">لا يوجد نشاط حديث.</p>
        @endforelse
    </div>
</div>
@endif

@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('pipelineChart');
        if (!ctx) return;

        const stageData = @json($pipelineDistribution ?? []);

        const labels = Object.values(stageData).map(item => item.label);
        const counts = Object.values(stageData).map(item => item.count);
        const colors = Object.values(stageData).map(item => item.color);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'عدد المشاريع',
                    data: counts,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6,
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal bar chart
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' المشاريع: ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 },
                        grid: { display: false }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Lama Sans', size: 13 },
                            color: '#1A1A3A'
                        }
                    }
                }
            }
        });
                const donutCtx = document.getElementById('teamDonut');
        if (donutCtx) {
            new Chart(donutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['موظفون', 'مدراء'],
                    datasets: [{
                        data: [{{ $nonManagerEmployeesCount ?? 0 }}, {{ $managersCount ?? 0 }}],
                        backgroundColor: ['#8A84AD', '#F59E0B'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
</script>
@endpush