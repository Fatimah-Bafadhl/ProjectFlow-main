@extends('layouts.app')
@section('title', 'المهام')
@section('content-class', 'p-4 flex-grow-1 d-flex flex-column overflow-hidden')

@php
    $user = auth()->user();
    $isClient = $user && $user->isClient();
    $isAdmin = $user && $user->isAdmin();
    $isManager = $user && $user->isManager();
    $isEmployee = $user && $user->isEmployee();
@endphp

@section('content')
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="task-page-title m-0">المهام</h2>
              @if($isAdmin || $isManager)
    <button class="btn btn-add-task d-flex align-items-center gap-2" data-bs-target="#taskPanel" data-bs-toggle="offcanvas" onclick="prepareAddModal()">
        <span>إضافة مهمة +</span>
    </button>
    @endif
</div>

<div class="d-flex flex-row flex-nowrap gap-3 overflow-x-auto pb-3 Task-Style flex-grow-1 align-items-start">
  @php
    $statuses = [
        'قيد التنفيذ'  => ['icon' => 'fa-regular fa-id-badge', 'class' => ''],
        'قيد المراجعة' => ['icon' => 'fa-regular fa-clipboard', 'class' => ''],
        'مكتملة'       => ['icon' => 'fa-regular fa-circle-check', 'class' => 'text-success'],
        'متوقف مؤقتاً' => ['icon' => 'fa-regular fa-circle-stop', 'class' => ''], 
        'قيد الانتظار' => ['icon' => 'fa-solid fa-list-check', 'class' => '']
    ];
  @endphp

    @foreach($statuses as $statusName => $statusMeta)
        <div class="status-card-column p-3 rounded-3 bg-light" style="min-width: 300px; max-width: 320px;">
            <div class="status-header d-flex align-items-center justify-content-start gap-2 mb-3">
                <span class="status-title">{{ $statusName }}</span>
                <i class="{{ $statusMeta['icon'] }} status-icon status-success-icon {{ $statusMeta['class'] }} ms-auto"></i>
            </div>
            
            <div class="task-list d-flex flex-column gap-2 overflow-y-auto px-1" style="max-height: 70vh;">
                @php
                    $filteredTasks = $tasks->where('status', $statusName);
                @endphp

                @forelse($filteredTasks as $task)
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
                        <div class="task-card p-3 rounded-3 bg-white border" 
                         data-task-id="{{ $task->task_id }}" 
                         data-task-title="{{ $task->task_title }}"
                         data-project-id="{{ $task->project_id }}"
                         data-stage-id="{{ $task->stage_id }}"
                         data-company="{{ optional($task->project)->company_name }}"
                         data-assigned-to="{{ $task->assigned_to }}"
                         data-description="{{ $task->task_description }}"
                         data-start-date="{{ $task->start_task }}"
                         data-end-date="{{ $task->end_task }}"
                                                  data-status="{{ $task->status }}"
                         data-priority="{{ $task->priority ?? 'متوسط' }}"
                         data-attachments="{{ json_encode($attachmentsJson, JSON_UNESCAPED_UNICODE) }}">
                        
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h4 class="task-name m-0" style="font-size: 14px; font-weight: 600;">
                                <a class="text-decoration-none text-dark" href="{{ route('tasks.show', $task->task_id) }}">
                                    {{ $task->task_title }}
                                </a>
                            </h4>
                            
                            @if(!$isClient)
                            <div class="task-actions d-flex align-items-center gap-2">
                                                                @if($isAdmin || $isManager)
                                                                       <button class="btn-icon text-muted border-0 bg-transparent p-0" onclick="openEditModal(this)"><i class="fa-regular fa-pen-to-square"></i></button>
                                    <button class="btn-icon text-muted border-0 bg-transparent p-0" onclick="openDeleteModal(this)"><i class="fa-regular fa-trash-can"></i></button>
                                @elseif($isEmployee)
                                    <button class="btn-icon text-muted border-0 bg-transparent p-0" title="تعديل الحالة" onclick="openEmployeeTaskStatusModal('{{ $task->task_id }}', '{{ $task->status }}', '{{ route('tasks.update', $task->task_id) }}')">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                @endif
                            </div>
                            @endif
                        </div>

                        <p class="project-name mb-1 text-muted" style="font-size: 12px;">
                            اسم المشروع : {{ $task->project ? $task->project->project_name : 'غير محدد' }}
                        </p>

                                                <div class="d-flex justify-content-between align-items-center" style="font-size: 11px;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="end-date text-muted">
                                    تاريخ الانتهاء : {{ $task->end_task ? \Carbon\Carbon::parse($task->end_task)->format('Y/m/d') : 'غير محدد' }}
                                </span>
                                <span class="badge-task-priority {{ $task->priority_class }}">{{ $task->priority_label }}</span>
                            </div>
                            <div class="comments-count d-flex align-items-center gap-1 text-muted">
                                <i class="fa-regular fa-comment comment-icon"></i>
                                <span class="comment-num">{{ $task->comments_count ?? ($task->comments ? $task->comments->count() : 0) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3 extra-small" style="font-size: 12px;">
                        لا توجد مهام {{ $statusName }}
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('modals')
@if(!$isClient)

    {{-- لوحة إضافة/تعديل المهمة (Offcanvas) --}}
    @include('partials.task-panel')

    @if($isAdmin)
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal p-4 text-center">
                <div class="modal-body p-0">
                    <p class="delete-text mb-4" id="deleteModalText">هل تريد حذف المهمة؟</p>
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
    </div>
    @endif

    @if($isEmployee)
    <div aria-hidden="true" class="modal fade" id="employeeTaskStatusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="modal-title m-0" style="font-size: 18px; font-weight: 700;">تعديل حالة المهمة</h3>
                    <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="employeeTaskStatusForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4 text-end">
                            <label class="custom-label mb-1">حالة المهمة <span class="text-danger">*</span></label>
                            <select class="form-select custom-input text-center" id="employeeTaskStatusSelect" name="status" required>
                                <option value="قيد التنفيذ">قيد التنفيذ</option>
                                <option value="قيد المراجعة">قيد المراجعة</option>
                                <option value="مكتملة">مكتملة</option>
                                <option value="متوقف مؤقتاً">متوقف مؤقتاً</option>
                                <option value="قيد الانتظار">قيد الانتظار</option>
                            </select>
                        </div>
                        <div class="text-center pt-2">
                            <button class="btn btn-save" type="submit">تحديث الحالة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

@endif
@endpush