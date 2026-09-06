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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="task-page-title m-0" id="pageMainTitle">المشاريع</h1>
    @if(!$isClient && !$isEmployee)
    <button class="btn btn-add-task d-flex align-items-center gap-2" data-bs-target="#taskModal" data-bs-toggle="modal" onclick="prepareAddModal()">
        <span>إضافة مهمة +</span>
    </button>
    @endif
</div>

@php
    $statusIconsMap = [
        'قيد التنفيذ'  => ['icon' => 'fa-regular fa-id-badge', 'class' => ''],
        'قيد المراجعة' => ['icon' => 'fa-regular fa-clipboard', 'class' => ''],
        'مكتمل'        => ['icon' => 'fa-regular fa-circle-check', 'class' => 'text-success'],
        'مكتملة'       => ['icon' => 'fa-regular fa-circle-check', 'class' => 'text-success'],
        'متوقف مؤقتا'  => ['icon' => 'fa-regular fa-circle-stop', 'class' => ''],
        'متوقف مؤقتاً' => ['icon' => 'fa-regular fa-circle-stop', 'class' => ''],
        'قيد الانتظار' => ['icon' => 'fa-solid fa-list-check', 'class' => '']
    ];

    $projectStatus = $project->status ?? 'قيد التنفيذ';
    $projectStatusMeta = $statusIconsMap[$projectStatus] ?? ['icon' => 'fa-regular fa-id-badge', 'class' => ''];
@endphp

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white" style="border: 1px solid #EFEEF3 !important;">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2">
                <h3 class="project-card-title m-0" id="cardProjTitle">{{ $project->project_name }}</h3>
                <span class="text-muted" id="companyName" style="font-size: 13px;">{{ $project->company_name ?? 'غير محدد' }}</span>
            </div>
            <p class="text-secondary mb-3" id="projDesc" style="font-size: 13px; line-height: 1.6;">
                {{ $project->project_description ?? 'لا يوجد وصف متاح لهذا المشروع.' }}
            </p>
            <div class="d-flex gap-4 text-muted" style="font-size: 12px;">
                @php
                    \Carbon\Carbon::setLocale('ar');
                @endphp
                <span id="startDateText">تاريخ البداية : {{ $project->start_project ? \Carbon\Carbon::parse($project->start_project)->translatedFormat('d F Y') : 'غير محدد' }}</span>
                <span><i class="fa-solid fa-arrow-left-long mx-1"></i> <span id="endDateText">تاريخ الانتهاء : {{ $project->end_project ? \Carbon\Carbon::parse($project->end_project)->translatedFormat('d F Y') : 'غير محدد' }}</span></span>
            </div>
        </div>
        
        @php
            $progressPercentage = $project->progress ?? 0;
        @endphp

        <div class="col-lg-4 mt-3 mt-lg-0 text-lg-end">
            <div class="d-flex align-items-center justify-content-lg-end gap-2 mb-2">
                <div class="d-flex align-items-center gap-2" style="color: #000000; font-size: 14px; font-weight: 400;">
                    <span id="statusInProgress">{{ $projectStatus }}</span>
                    <i class="{{ $projectStatusMeta['icon'] }} {{ $projectStatusMeta['class'] }}" style="font-size: 16px; color: #8A84AD;"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 12px;">
                    <span class="text-muted" id="progressLabel">نسبة الإنجاز</span>
                    <span class="fw-bold" style="color: #8A84AD;">{{ $progressPercentage }}%</span>
                </div>
                <div class="progress" style="height: 6px; background-color: #EFEEF3;">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ $progressPercentage }}" class="progress-bar rounded-pill" role="progressbar" style="width: {{ $progressPercentage }}%; background-color: #8A84AD;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $statuses = [
        'قيد التنفيذ'  => ['icon' => 'fa-regular fa-id-badge', 'class' => ''],
        'قيد المراجعة' => ['icon' => 'fa-regular fa-clipboard', 'class' => ''],
        'مكتمل'        => ['icon' => 'fa-regular fa-circle-check', 'class' => 'text-success'],
        'مكتملة'       => ['icon' => 'fa-regular fa-circle-check', 'class' => 'text-success'],
        'متوقف مؤقتا'  => ['icon' => 'fa-regular fa-circle-stop', 'class' => ''],
        'قيد الانتظار' => ['icon' => 'fa-solid fa-list-check', 'class' => '']
    ];
@endphp

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    @foreach($statuses as $statusName => $statusMeta)
        @php
            $filteredTasks = $project->tasks->where('status', $statusName);
            $tasksCount = $filteredTasks->count();
        @endphp

        <div class="col">
            <div class="card border rounded-4 p-3 bg-white shadow-sm d-flex flex-column" style="border-color: #EFEEF3 !important; height: 440px;">
                <div class="status-header d-flex align-items-center justify-content-start gap-2 mb-3">
                    <span class="status-title">{{ $statusName }}</span>
                    <i class="{{ $statusMeta['icon'] }} status-icon status-success-icon {{ $statusMeta['class'] }} ms-auto" style="color: #8A84AD;"></i>
                </div>
                <div class="flex-grow-1 overflow-auto pe-1" style="max-height: 350px;">
                    <div class="d-flex flex-column gap-3">
                        @if(!$isClient)
                            @forelse($filteredTasks as $task)
                                <div class="card border rounded-3 p-3 bg-white task-card d-flex flex-column justify-content-between shadow-xs" 
                                       style="border-color: #EFEEF3 !important;"
                                       data-task-id="{{ $task->task_id }}" 
                                       data-task-title="{{ $task->task_title }}"
                                       data-project-id="{{ $task->project_id }}"
                                       data-assigned-to="{{ $task->assigned_to }}"
                                       data-description="{{ $task->task_description }}"
                                       data-start-date="{{ $task->start_task }}"
                                       data-end-date="{{ $task->end_task }}"
                                       data-status="{{ $task->status }}"
                                       data-company-name="{{ optional($task->project)->company_name }}">
                                    
                                    <div>
                                        <a class="fw-bold task-name text-decoration-none text-dark" href="{{ route('tasks.show', $task->task_id) }}" style="font-size: 14px;">
                                            {{ $task->task_title }}
                                        </a>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top">
                                        <div class="text-muted end-date" style="font-size: 11px;">
                                            {{ $task->end_task ? \Carbon\Carbon::parse($task->end_task)->translatedFormat('d F Y') : 'غير محدد' }}
                                        </div>

                                        <div class="task-actions d-flex align-items-center gap-2" style="font-size: 14px;">
                                            @if($isAdmin)
                                                <button class="btn-icon border-0 bg-transparent p-0" onclick="openEditModal(this)" style="color: #8A84AD;"><i class="fa-regular fa-pen-to-square"></i></button>
                                                <button class="btn-icon border-0 bg-transparent p-0" onclick="openDeleteModal(this)" style="color: #8A84AD;"><i class="fa-regular fa-trash-can"></i></button>
                                            @endif

                                            <div class="d-flex align-items-center gap-1" style="color: #8A84AD;">
                                                <i class="fa-regular fa-comment"></i>
                                                <span style="font-size: 12px;">{{ $task->comments_count ?? ($task->comments ? $task->comments->count() : 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4 small" style="font-size: 12px;">
                                    لا توجد مهام {{ $statusName }}
                                </div>
                            @endforelse
                        @else
                            <div class="text-center text-muted py-4 small" style="font-size: 13px;">
                                @if($tasksCount > 0)
                                    <i class="fa-regular fa-clipboard mb-2 d-block" style="font-size: 20px; color: #8A84AD;"></i>
                                    {{ $tasksCount }} {{ $tasksCount == 1 ? 'مهمة' : 'مهام' }} بحالة "{{ $statusName }}"
                                @else
                                    لا توجد مهام {{ $statusName }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                
                
            </div>
        </div>
    @endforeach
</div>

@if($isAdmin || $isManager || $isClient)
<div class="card border-0 shadow-sm rounded-4 p-4 mt-4 bg-white" style="border: 1px solid #EFEEF3 !important;">
    <h4 class="mb-3" style="font-size: 16px; font-weight: 700;" >  تحديثات المشروع للعميل </h4>

    <div class="d-flex flex-column gap-3 mb-4">
        @forelse($project->comments()->with('user')->latest()->get() as $comment)
            <div class="border rounded-3 p-3" style="border-color: #EFEEF3 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
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
            <div class="text-center text-muted py-3 small" style="font-size: 12px;">
                لا توجد تحديثات على هذا المشروع بعد.
            </div>
        @endforelse
    </div>

    @if($isAdmin || $isAssignedManager)
        <form action="{{ route('comments.storeForProject', $project->project_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-2">
                <textarea class="form-control custom-input w-100" name="comment_text" rows="2" placeholder="اكتب تحديثاً للعميل..."></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <input type="file" name="attachment" class="form-control form-control-sm w-auto" accept=".pdf,.doc,.docx,.zip,.fig,.jpg,.jpeg,.png,.gif">
                <button type="submit" class="btn btn-save px-4">نشر</button>
            </div>
        </form>
    @endif
</div>
@endif

@if($isClient || $isAdmin || $isAssignedManager)
<div class="card border-0 shadow-sm rounded-4 p-4 mt-4 bg-white" style="border: 1px solid #EFEEF3 !important;">
    <h4 class="mb-3" style="font-size: 16px; font-weight: 700;">طلبات العميل</h4>

       @if($isClient)
        <form action="{{ route('tickets.store', $project->project_id) }}" method="POST">
            @csrf
            <div class="mb-2">
                <textarea class="form-control custom-input w-100" name="message" rows="2" placeholder="اكتب طلبك أو استفسارك هنا..." required></textarea>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-save px-4">إرسال الطلب</button>
            </div>
        </form>

        @php
            $client = auth()->user()->client;
            $myTickets = $client
                ? $project->tickets()->where('client_id', $client->client_id)->latest()->get()
                : collect();
        @endphp

        <div class="d-flex flex-column gap-3 mt-4">
            @forelse($myTickets as $ticket)
                <div class="border rounded-3 p-3" style="border-color: #EFEEF3 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge {{ $ticket->status === \App\Enums\TicketStatus::Handled ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size: 10px;">
                            {{ $ticket->status->label() }}
                        </span>
                        <span class="text-muted" style="font-size: 11px;">{{ $ticket->created_at->translatedFormat('d F Y - h:i A') }}</span>
                    </div>
                    <p class="mb-0" style="font-size: 13px;">{{ $ticket->message }}</p>
                </div>
            @empty
                <div class="text-center text-muted py-3 small" style="font-size: 12px;">
                    لم تقم بإرسال أي طلبات على هذا المشروع بعد.
                </div>
            @endforelse
        </div>
    @endif

    @if($isAdmin || $isAssignedManager)
        <div class="d-flex flex-column gap-3 {{ $isClient ? 'mt-4' : '' }}">
            @forelse($project->tickets()->with('client.user')->latest()->get() as $ticket)
                <div class="border rounded-3 p-3 d-flex justify-content-between align-items-start" style="border-color: #EFEEF3 !important;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold" style="font-size: 13px;">{{ optional(optional($ticket->client)->user)->username ?? $ticket->client_name ?? 'عميل محذوف' }}</span>
                        <span class="badge {{ $ticket->status === \App\Enums\TicketStatus::Handled ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size: 10px;">
                                {{ $ticket->status->label() }}
                            </span>
                        </div>
                        <p class="mb-1" style="font-size: 13px;">{{ $ticket->message }}</p>
                        <span class="text-muted" style="font-size: 11px;">{{ $ticket->created_at->translatedFormat('d F Y - h:i A') }}</span>
                    </div>
                    @if($ticket->status !== \App\Enums\TicketStatus::Handled)
                        <form action="{{ route('tickets.update', $ticket->ticket_id) }}" method="POST" class="ms-2">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm btn-outline-secondary">تحديد كمكتمل</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-3 small" style="font-size: 12px;">
                    لا توجد طلبات من العميل حتى الآن.
                </div>
            @endforelse
        </div>
    @endif
</div>
@endif
@endsection

@push('modals')
@if(!$isClient && !$isEmployee)
    <div aria-hidden="true" class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="modal-title m-0" id="taskModalTitle" style="font-size: 18px; font-weight: 700;">إضافة مهمة</h3>
                    <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
                </div>
                
                <form id="taskForm" action="{{ route('tasks.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="taskFormMethod" value="POST">

                    <div class="mb-3">
                        <label class="form-label custom-label">اسم المهمة <span class="text-danger">*</span></label>
                        <input class="form-control custom-input w-100" id="taskNameInput" name="task_title" required type="text" placeholder="أدخل اسم المهمة"/>
                    </div>

                    <div class="mb-3">
                        <label class="form-label custom-label">اسم المشروع <span class="text-danger">*</span></label>
                        <select class="form-select custom-input w-100" id="projectIdInput" name="project_id" required>
                            <option value="{{ $project->project_id }}" selected>{{ $project->project_name }}</option>
                            @foreach($projects ?? [] as $p)
                                @if($p->project_id != $project->project_id)
                                    <option value="{{ $p->project_id }}">{{ $p->project_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label custom-label">اسم الشركة <span class="text-danger">*</span></label>
                            <select class="form-select custom-input w-100" id="companyNameInput" required>
                                <option value="{{ $project->company_name }}" selected>{{ $project->company_name ?? 'اختر الشركة' }}</option>
                            </select>
                        </div>
                        
                        <div class="col-6">
                            <label class="form-label custom-label">مسند إلى</label>
                            <select class="form-select custom-input w-100" id="assignedToInput" name="assigned_to">
                                <option value="">اختر الموظف</option>
                                @php
                                    $allEmployees = isset($employees) && count($employees) > 0 
                                        ? $employees 
                                        : (\class_exists(\App\Models\Employee::class) ? \App\Models\Employee::all() : \App\Models\User::all());
                                @endphp
                                @foreach($allEmployees as $employee)
                                    <option value="{{ $employee->employee_id ?? $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label custom-label">الوصف <span class="text-danger">*</span></label>
                        <textarea class="form-control custom-input w-100" id="descriptionInput" name="task_description" required rows="2" placeholder="أدخل وصف المهمة"></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label custom-label">تاريخ البدء <span class="text-danger">*</span></label>
                            <input class="form-control custom-date-btn w-100" id="startDateInput" name="start_task" required type="date" min="{{ $project->start_project }}" max="{{ $project->end_project }}" onchange="document.getElementById('endDateInput').min = this.value;"/>
                        </div>
                        <div class="col-6">
                            <label class="form-label custom-label">تاريخ الانتهاء <span class="text-danger">*</span></label>
                            <input class="form-control custom-date-btn w-100" id="endDateInput" name="end_task" required type="date" min="{{ $project->start_project }}" max="{{ $project->end_project }}"/>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label custom-label">الحالة <span class="text-danger">*</span></label>
                        <select class="form-select custom-input w-100" id="statusSelect" name="status" required>
                            <option value="قيد التنفيذ">قيد التنفيذ</option>
                            <option value="قيد المراجعة">قيد المراجعة</option>
                            <option value="مكتملة">مكتملة</option>
                            <option value="متوقف مؤقتاً">متوقف مؤقتاً</option>
                            <option value="قيد الانتظار">قيد الانتظار</option>
                        </select>
                        @error('status')
                            <div class="text-danger mt-1 text-end" style="color: red; font-size: 0.85rem; font-weight: bold;">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="text-center">
                        <button class="btn btn-save px-5" type="submit">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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