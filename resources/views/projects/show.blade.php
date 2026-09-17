@extends('layouts.app')
@section('title', 'المشاريع - تفاصيل المشروع')
@section('content-class', 'p-4 flex-grow-1 bg-white')

@push('styles')
<style>
    .flex-grow-1.overflow-auto::-webkit-scrollbar {
        width: 6px;
    }
    .flex-grow-1.overflow-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .flex-grow-1.overflow-auto::-webkit-scrollbar-thumb {
        background: #8A84AD;
        border-radius: 10px;
    }
    .flex-grow-1.overflow-auto::-webkit-scrollbar-thumb:hover {
        background: #736d94;
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $isClient = $user && $user->isClient();
    $isEmployee = $user && $user->isEmployee();
    $isAdmin = $user && $user->isAdmin();
    $isManager = $user && $user->isManager();
    $isAssignedManager = $isManager && $project->managers()->where('users.user_id', $user->user_id)->exists();
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show text-start mb-3 rounded-3 shadow-sm py-2 px-3 small" role="alert">
        <i class="fa-regular fa-circle-check me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show text-start mb-3 rounded-3 shadow-sm py-2 px-3 small" role="alert">
        <i class="fa-regular fa-circle-xmark me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show text-start mb-3 rounded-3 shadow-sm py-2 px-3 small" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class="fa-regular fa-circle-xmark me-2"></i>
            <span class="fw-bold">تنبيه:</span>
        </div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size: 14px;">
        <li class="breadcrumb-item"><a class="text-decoration-none text-muted" href="{{ route('projects.index') }}" id="breadcrumbProj">المشاريع</a></li>
        <li aria-current="page" class="breadcrumb-item active fw-semibold text-dark" id="breadcrumbSub">{{ $project->project_name }}</li>
    </ol>
</nav>

@php
    \Carbon\Carbon::setLocale('ar');
    $progressPercentage = $project->progress ?? 0;
    $currentStageKey = $project->currentStageKey();
    $daysDiff = null;
    if ($project->end_project) {
        $endCarbon = \Carbon\Carbon::parse($project->end_project)->startOfDay();
        $daysDiff = \Carbon\Carbon::today()->diffInDays($endCarbon, false);
    }
    $completedStagesCount = $project->stages->where('status', \App\Enums\ProjectStageStatus::Done)->count();
    $totalStagesCount = $project->stages->count();
@endphp

<div class="d-flex justify-content-end gap-2 mb-3">
    @if(!$isClient)
        <a href="{{ route('documents.index', $project->project_id) }}" class="btn btn-add-task d-flex align-items-center gap-2">
        <span>المستندات</span>
    </a>
    @endif
        @if(!$isClient && !$isEmployee)
    <button class="btn btn-add-task d-flex align-items-center gap-2"
            data-bs-target="#taskPanel"
            data-bs-toggle="offcanvas"
            data-preselect-project="{{ $project->project_id }}"
            onclick="prepareAddModal(this)">
        <span>إضافة مهمة +</span>
    </button>
    @endif
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white" style="border: 1px solid #EFEEF3 !important;">
    <div class="row align-items-center">
        
                <div class="col-lg-8">
                                    <div class="d-flex align-items-center gap-3 mb-1 flex-wrap">
                <h3 class="project-card-title m-0">{{ $project->project_name }}</h3>
            </div>

            <div class="text-muted mb-2" style="font-size: 13px;">{{ $project->company_name ?? 'غير محدد' }}</div>

            <div class="mb-2">
                <span class="badge-project-status">{{ $project->project_type->label() }}</span>
            </div>

            <p class="text-secondary mb-3" style="font-size: 13px; line-height: 1.6;">
                {{ $project->project_description ?? 'لا يوجد وصف متاح لهذا المشروع.' }}
            </p>

            <div class="d-flex gap-4 flex-wrap text-muted align-items-center" style="font-size: 12px;">
                <span>تاريخ البداية : {{ $project->start_project ? \Carbon\Carbon::parse($project->start_project)->translatedFormat('d F Y') : 'غير محدد' }}</span>
                <span><i class="fa-solid fa-arrow-left-long mx-1"></i> تاريخ الانتهاء : {{ $project->end_project ? \Carbon\Carbon::parse($project->end_project)->translatedFormat('d F Y') : 'غير محدد' }}</span>
                @if($daysDiff !== null)
                    @if($daysDiff > 0)
                        <span class="badge-days-left"><i class="fa-regular fa-hourglass-half me-1"></i> متبقي {{ $daysDiff }} يوم</span>
                    @elseif($daysDiff === 0)
                        <span class="badge-days-overdue"><i class="fa-regular fa-hourglass-half me-1"></i> ينتهي اليوم</span>
                    @else
                        <span class="badge-days-overdue"><i class="fa-solid fa-triangle-exclamation me-1"></i> متأخر {{ abs($daysDiff) }} يوم</span>
                    @endif
                @endif
            </div>

            @if($lastActivityAt)
                <div class="text-muted mt-2" style="font-size: 12px;">
                    <i class="fa-regular fa-clock me-1"></i> آخر نشاط : {{ $lastActivityAt->diffForHumans() }}
                </div>
            @endif
        </div>
               <div class="col-lg-4 mt-3 mt-lg-0">
            @if($currentStageKey)
                <div class="mb-2 text-start">
                    <span class="badge-stage-current" style="background-color: {{ $currentStageKey->color() }}1A; color: {{ $currentStageKey->color() }};">
                        {{ $currentStageKey->label() }}
                    </span>
                </div>
            @else
                <div class="mb-2 text-start">
                    <span class="badge-stage-current badge-stage-done">مكتملة</span>
                </div>
            @endif
                      <div class="row g-2 text-center mb-3">
                @if(!$isClient)
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number">{{ $doneTasksCount }}/{{ $totalTasksCount }}</div>
                            <div class="stat-label">مهام مكتملة</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number {{ $openTicketsCount > 0 ? 'text-danger' : '' }}">{{ $openTicketsCount }}</div>
                            <div class="stat-label">تذاكر مفتوحة</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number">{{ $progressPercentage }}%</div>
                            <div class="stat-label">الإنجاز</div>
                        </div>
                    </div>
                @else
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number">{{ $completedStagesCount }}/{{ $totalStagesCount }}</div>
                            <div class="stat-label">المراحل المكتملة</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            @if($daysDiff !== null && $daysDiff > 0)
                                <div class="stat-number">{{ $daysDiff }}</div>
                                <div class="stat-label">أيام متبقية</div>
                            @elseif($daysDiff === 0)
                                <div class="stat-number">اليوم</div>
                                <div class="stat-label">موعد التسليم</div>
                            @elseif($daysDiff !== null)
                                <div class="stat-number text-danger">{{ abs($daysDiff) }}</div>
                                <div class="stat-label">أيام تأخير</div>
                            @else
                                <div class="stat-number">—</div>
                                <div class="stat-label">غير محدد</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-number">{{ $progressPercentage }}%</div>
                            <div class="stat-label">الإنجاز</div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="progress" style="height: 6px; background-color: #EFEEF3;">
                <div class="progress-bar rounded-pill" role="progressbar" style="width: {{ $progressPercentage }}%; background-color: #8A84AD;"></div>
            </div>
               </div>
    </div>
</div>

@if($isClient)
    @php $pm = $project->managers->first(); @endphp
    @if($pm)
        <div class="client-pm-card mb-4">
            <div class="client-pm-avatar">{{ mb_substr($pm->username, 0, 1) }}</div>
            <div class="client-pm-info">
                <div class="client-pm-label">مدير المشروع</div>
                <div class="client-pm-name">{{ $pm->username }}</div>
            </div>
                        <a href="{{ route('communications.index', ['project' => $project->project_id]) }}" class="btn btn-add-task client-pm-btn">
                <i class="fa-regular fa-comments me-1"></i>
                التواصل مع فريق المشروع
            </a>
        </div>
    @endif
@endif

<ul class="nav project-stage-tabs mb-3" id="projectStageTabs" role="tablist">
    @foreach($sortedStages as $stage)
        <li class="nav-item" role="presentation">
            <button class="nav-link stage-tab-link {{ $stage->project_stage_id === $activeStageId ? 'active' : '' }}"
                    data-bs-toggle="tab" data-bs-target="#stage-pane-{{ $stage->project_stage_id }}"
                    type="button" role="tab" style="--stage-color: {{ $stage->stage_key->color() }};">
                {{ $stage->stage_key->label() }}
            </button>
        </li>
        @endforeach

    @if(!$isClient)
        <li class="nav-item" role="presentation">
            <button class="nav-link stage-tab-link" data-bs-toggle="tab" data-bs-target="#comm-pane" type="button" role="tab" style="--stage-color:#8A84AD;">
                التذاكر والتواصل
                @if($openTicketsCount > 0)<span class="badge bg-danger ms-1">{{ $openTicketsCount }}</span>@endif
            </button>
        </li>
    @else
        <li class="nav-item" role="presentation">
            <button class="nav-link stage-tab-link" data-bs-toggle="tab" data-bs-target="#deliverables-pane" type="button" role="tab" style="--stage-color:#8A84AD;">
                التسليمات
            </button>
        </li>
    @endif
</ul>

<div class="tab-content" id="projectStageTabsContent">
   
                 @foreach($sortedStages as $stage)
        @php
            $stagePercent = match($stage->status) {
                \App\Enums\ProjectStageStatus::Done => 100,
                \App\Enums\ProjectStageStatus::InProgress => $stage->taskProgressPercent(),
                default => 0,
            };
        @endphp
        <div class="tab-pane fade {{ $stage->project_stage_id === $activeStageId ? 'show active' : '' }}" id="stage-pane-{{ $stage->project_stage_id }}" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="border: 1px solid #EFEEF3 !important;">

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="font-size: 12px;">{{ $stage->status->label() }} — {{ $stagePercent }}%</span>
                        <div class="progress" style="height: 6px; width: 140px; background-color: #EFEEF3;">
                            <div class="progress-bar rounded-pill" style="width: {{ $stagePercent }}%; background-color: {{ $stage->stage_key->color() }};"></div>
                        </div>
                    </div>

                    @if(!$isClient)
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <select class="form-select form-select-sm stage-filter" data-filter-type="status" data-stage-target="stage-tasks-{{ $stage->project_stage_id }}">
                                <option value="">كل الحالات</option>
                                <option value="قيد الانتظار">قيد الانتظار</option>
                                <option value="قيد التنفيذ">قيد التنفيذ</option>
                                <option value="قيد المراجعة">قيد المراجعة</option>
                                <option value="مكتملة">مكتملة</option>
                                <option value="متوقف مؤقتاً">متوقف مؤقتاً</option>
                            </select>
                            <select class="form-select form-select-sm stage-filter" data-filter-type="priority" data-stage-target="stage-tasks-{{ $stage->project_stage_id }}">
                                <option value="">كل الأولويات</option>
                                <option value="منخفض">منخفض</option>
                                <option value="متوسط">متوسط</option>
                                <option value="عالي">عالي</option>
                            </select>
                            @if($isAdmin || $isAssignedManager)
                                @if($stage->status !== \App\Enums\ProjectStageStatus::Done)
                                    <form action="{{ route('projects.stages.update', [$project->project_id, $stage->project_stage_id]) }}" method="POST" class="m-0">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="done">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">تحديد كمكتمل</button>
                                    </form>
                                @else
                                    <form action="{{ route('projects.stages.update', [$project->project_id, $stage->project_stage_id]) }}" method="POST" class="m-0">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="in_progress">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">التراجع عن الإكتمال</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @else
                                               <a href="{{ route('communications.index', ['project' => $project->project_id, 'stage' => $stage->project_stage_id]) }}" class="btn btn-doc-secondary">
                            <i class="fa-regular fa-comments me-1"></i>
                            التواصل بخصوص هذه المرحلة
                        </a>
                    @endif
                </div>

                @if(!$isClient)
                    <div class="d-flex flex-column gap-2" id="stage-tasks-{{ $stage->project_stage_id }}">

                        @forelse($stage->tasks as $task)

                            @php
                                $attachmentsJson = $task->attachments->map(function ($a) {
                                    return [
                                        'id'    => $a->task_attachment_id,
                                        'title' => $a->title,
                                        'type'  => $a->type,
                                        'url'   => $a->type === 'link' ? $a->url : asset('storage/' . $a->file_path),
                                    ];
                                })->values()->all();
                            @endphp
                            <div class="task-row-item d-flex justify-content-between align-items-center py-3 px-3 border rounded-3"
                                 style="border-color: #EFEEF3 !important;"
                                 data-status="{{ $task->status }}"
                                 data-task-id="{{ $task->task_id }}"
                                 data-task-title="{{ $task->task_title }}"
                                 data-project-id="{{ $task->project_id }}"
                                 data-stage-id="{{ $task->stage_id }}"
                                 data-assigned-to="{{ $task->assignedEmployees->pluck('employee_id') }}"
                                 data-description="{{ $task->task_description }}"
                                 data-start-date="{{ $task->start_task }}"
                                 data-end-date="{{ $task->end_task }}"
                                 data-company="{{ $project->company_name }}"
                                 data-priority="{{ $task->priority ?? 'متوسط' }}"
                                 data-attachments="{{ json_encode($attachmentsJson, JSON_UNESCAPED_UNICODE) }}">
                                <a class="fw-bold task-name text-decoration-none text-dark" href="{{ route('tasks.show', $task->task_id) }}" style="font-size: 14px;">
                                    {{ $task->task_title }}
                                </a>
                                <div class="text-muted" style="font-size: 11px;">
                                    {{ $task->assignedEmployees->pluck('name')->implode('، ') ?: 'غير مسند' }}
                                    · {{ $task->end_task ? \Carbon\Carbon::parse($task->end_task)->translatedFormat('d F Y') : 'غير محدد' }}
                                </div>
                                <div class="d-flex align-items-center gap-3" style="font-size: 13px;">
                                    @php
                                        $statusClass = match($task->status) {
                                            'قيد الانتظار' => 'badge-status-waiting',
                                            'قيد التنفيذ' => 'badge-status-progress',
                                            'قيد المراجعة' => 'badge-status-review',
                                            'مكتملة' => 'badge-status-done',
                                            'متوقف مؤقتاً' => 'badge-status-paused',
                                            default => 'badge-status-default',
                                        };
                                    @endphp
                                    <span class="badge-task-priority {{ $task->priority_class }}">{{ $task->priority_label }}</span>
                                    <span class="badge-task-status {{ $statusClass }}">{{ $task->status }}</span>
                                    <div class="d-flex align-items-center gap-1" style="color: #8A84AD;">
                                        <i class="fa-regular fa-comment"></i>
                                        <span style="font-size: 12px;">{{ $task->comments ? $task->comments->count() : 0 }}</span>
                                    </div>
                                    @if($isAdmin || ($isManager && $isAssignedManager))
                                        <button class="btn-icon border-0 bg-transparent p-0" onclick="openEditModal(this)" style="color: #8A84AD;"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="btn-icon border-0 bg-transparent p-0" onclick="openDeleteModal(this)" style="color: #8A84AD;"><i class="fa-regular fa-trash-can"></i></button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">لا توجد مهام في هذه المرحلة</div>
                        @endforelse
                    </div>
                @endif

            </div>
        </div>
    @endforeach

     @if(!$isClient)
      <div class="tab-pane fade" id="comm-pane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="border: 1px solid #EFEEF3 !important;">

            @if($project->clients->isNotEmpty())
                <div class="client-info-strip mb-4">
                                       <div class="comm-col-header">
                        <i class="fa-regular fa-user me-1" style="color: #8A84AD;"></i>
                        <span>العملاء</span>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach($project->clients as $client)
                            <div class="client-info-card">
                                <div class="client-info-name">{{ $client->name ?? optional($client->user)->username ?? 'عميل' }}</div>
                                <div class="client-info-meta-row">
                                    @if($client->company_name)
                                        <span><i class="fa-regular fa-building me-1"></i> {{ $client->company_name }}</span>
                                    @endif
                                    @if($client->email)
                                        <span><i class="fa-regular fa-envelope me-1"></i> {{ $client->email }}</span>
                                    @endif
                                    @if($client->phone)
                                        <span><i class="fa-solid fa-phone me-1"></i> {{ $client->phone }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="comm-col">
                        <div class="comm-col-header">
                            <i class="fa-regular fa-ticket me-1" style="color: #F59E0B;"></i>
                            <span>تذاكر العميل</span>
                        </div>

                        @if($isClient)
                            <form action="{{ route('tickets.store', $project->project_id) }}" method="POST" class="mb-3">
                                @csrf
                                <textarea class="form-control custom-input w-100 mb-2" name="message" rows="2" placeholder="اكتب طلبك أو استفسارك هنا..." required></textarea>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-save px-4">إرسال الطلب</button>
                                </div>
                            </form>
                        @endif

                        <div class="d-flex flex-column gap-3">
                            @forelse($project->tickets as $ticket)
                                <div class="comm-feed-item comm-feed-ticket">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="fw-bold" style="font-size: 13px;">{{ optional(optional($ticket->client)->user)->username ?? $ticket->client_name ?? 'عميل' }}</span>
                                                <span class="badge {{ $ticket->status === \App\Enums\TicketStatus::Handled ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size: 10px;">
                                                    {{ $ticket->status->label() }}
                                                </span>
                                            </div>
                                            <p class="mb-1" style="font-size: 13px;">{{ $ticket->message }}</p>
                                            <span class="text-muted" style="font-size: 11px;">{{ $ticket->created_at->translatedFormat('d F Y - h:i A') }}</span>
                                        </div>
                                        @if(($isAdmin || $isAssignedManager) && $ticket->status !== \App\Enums\TicketStatus::Handled)
                                            <form action="{{ route('tickets.update', $ticket->ticket_id) }}" method="POST" class="ms-2">
                                                @csrf @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">تحديد كمكتمل</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">لا توجد تذاكر على هذا المشروع بعد.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="comm-col">
                        <div class="comm-col-header">
                            <i class="fa-regular fa-comment-dots me-1" style="color: #8A84AD;"></i>
                            <span>تعليقات المدير</span>
                        </div>

                        @if($isAdmin || $isAssignedManager)
                            <form action="{{ route('comments.storeForProject', $project->project_id) }}" method="POST" enctype="multipart/form-data" class="mb-3">
                                @csrf
                                <textarea class="form-control custom-input w-100 mb-2" name="comment_text" rows="2" placeholder="اكتب تحديثاً للعميل..."></textarea>
                                <div class="d-flex justify-content-between align-items-center">
                                    <input type="file" name="attachment" class="form-control form-control-sm w-auto" accept=".pdf,.doc,.docx,.zip,.fig,.jpg,.jpeg,.png,.gif">
                                    <button type="submit" class="btn btn-save px-4">نشر</button>
                                </div>
                            </form>
                        @endif

                        <div class="d-flex flex-column gap-3">
                            @forelse($project->comments as $comment)
                                <div class="comm-feed-item comm-feed-comment">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold" style="font-size: 13px;">{{ optional($comment->user)->username ?? $comment->author_name ?? 'مستخدم محذوف' }}</span>
                                        <span class="text-muted" style="font-size: 11px;">{{ $comment->created_at->translatedFormat('d F Y - h:i A') }}</span>
                                    </div>
                                    @if($comment->comment_text)
                                        <p class="mb-0" style="font-size: 13px;">{{ $comment->comment_text }}</p>
                                    @endif
                                    @if($comment->attachment)
                                        <a href="{{ Storage::url($comment->attachment) }}" target="_blank" class="d-inline-block mt-2" style="font-size: 12px;">
                                            <i class="fa-regular fa-paperclip me-1"></i> مرفق
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">لا توجد تعليقات على هذا المشروع بعد.</div>
                            @endforelse
                        </div>
                    </div>
                      </div>
            </div>
        </div>
    </div>
    @endif

    @if($isClient)
        <div class="tab-pane fade" id="deliverables-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="border: 1px solid #EFEEF3 !important;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-regular fa-folder-open" style="color: #8A84AD;"></i>
                    <h5 class="task-page-title m-0">التسليمات</h5>
                    <span class="badge rounded-circle text-dark bg-light border ms-1">{{ $project->documents->count() }}</span>
                </div>

                @if($project->documents->isEmpty())
                    <div class="text-center py-5">
                        <i class="fa-regular fa-folder-open mb-2" style="font-size: 32px; color: #D0CBE3;"></i>
                        <p class="text-muted small mb-0">لا توجد تسليمات منشورة بعد.</p>
                    </div>
                @else
                    <div class="d-flex flex-column gap-2">
                        @foreach($project->documents as $doc)
                            <div class="task-existing-attachment">
                                <div class="file-info">
                                    <i class="{{ $doc->type === 'link' ? 'fa-solid fa-link' : 'fa-solid fa-paperclip' }} file-icon"></i>
                                    @if($doc->type === 'link')
                                        <a class="file-name" href="{{ $doc->url }}" target="_blank" rel="noopener">{{ $doc->title }}</a>
                                    @else
                                        <a class="file-name" href="{{ Storage::url($doc->file_path) }}" target="_blank" rel="noopener">{{ $doc->title }}</a>
                                    @endif
                                    <span class="text-muted small ms-1" style="font-size: 11px;">
                                        — {{ $doc->created_at->locale('ar')->translatedFormat('d F Y') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

     </div>          
@endsection

@push('modals')
@if(!$isClient && !$isEmployee)

    {{-- لوحة إضافة/تعديل المهمة (Offcanvas) — مشتركة مع صفحة المهام --}}
    @include('partials.task-panel')

    @if($isAdmin)
    <div aria-hidden="true" class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content custom-modal text-center p-4">
                <h4 class="delete-text mb-4 fw-bold" id="deleteModalText">هل تريد حذف المهمة؟</h4>
                <form id="deleteTaskForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-delete-confirm">حذف</button>
                        <button type="button" class="btn btn-delete-cancel" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endif
@endpush