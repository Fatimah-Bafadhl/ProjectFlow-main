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
    <button class="btn btn-add-task d-flex align-items-center gap-2" data-bs-target="#taskModal" data-bs-toggle="modal" onclick="prepareAddModal()">
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
                    <div class="task-card p-3 rounded-3 bg-white border" 
                         data-task-id="{{ $task->task_id }}" 
                         data-task-title="{{ $task->task_title }}"
                         data-project-id="{{ $task->project_id }}"
                         data-company="{{ optional($task->project)->company_name }}"
                         data-assigned-to="{{ $task->assigned_to }}"
                         data-description="{{ $task->task_description }}"
                         data-start-date="{{ $task->start_task }}"
                         data-end-date="{{ $task->end_task }}"
                         data-status="{{ $task->status }}">
                        
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
                                    @if($isAdmin)
                                        <button class="btn-icon text-muted border-0 bg-transparent p-0" onclick="openDeleteModal(this)"><i class="fa-regular fa-trash-can"></i></button>
                                    @endif
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
                            <span class="end-date text-muted">
                                تاريخ الانتهاء : {{ $task->end_task ? \Carbon\Carbon::parse($task->end_task)->format('Y/m/d') : 'غير محدد' }}
                            </span>
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
   
    <div aria-hidden="true" class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="modal-title m-0" id="taskModalTitle" style="font-size: 18px; font-weight: 700;">إضافة مهمة</h3>
                    <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
                </div>
                
                <div class="modal-body p-0">
                    <form id="taskForm" action="{{ route('tasks.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="taskFormMethod" value="POST">

                        <div class="mb-3 text-end">
                            <label class="custom-label mb-1">اسم المهمة <span class="text-danger">*</span></label>
                            <input class="form-control custom-input text-end" id="taskNameInput" name="task_title" required type="text" placeholder="أدخل اسم المهمة"/>
                        </div>

                        <div class="mb-3 text-end">
                            <label class="custom-label mb-1">اسم المشروع <span class="text-danger">*</span></label>
                            <select class="form-select custom-input text-center" id="projectIdInput" name="project_id" onchange="updateProjectDatesLimits()" required>
                                <option value="">اختر المشروع</option>
                                @foreach($projects ?? [] as $project)
                                    <option value="{{ $project->project_id }}" 
                                            data-start="{{ $project->start_project }}" 
                                            data-end="{{ $project->end_project }}"
                                            data-company="{{ $project->company_name }}">
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6 text-end">
                                <label class="custom-label mb-1">اسم الشركة <span class="text-danger">*</span></label>
                                <select class="form-select custom-input text-center" id="companyNameInput" name="company_name" required>
                                    <option value="">اختر الشركة</option>
                                    @foreach(collect($projects ?? [])->unique('company_name') as $project)
                                        <option value="{{ $project->company_name }}">{{ $project->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                         <div class="col-6">
    <label class="form-label custom-label">مسند إلى <span class="text-danger">*</span></label>
    <select class="form-select custom-input w-100" id="assignedToInput" name="assigned_to" required>
        <option value="">اختر الموظف</option>
        @foreach($employees ?? [] as $employee)
            <option value="{{ $employee->employee_id ?? $employee->id }}">
                {{ $employee->name }} {{ isset($employee->department) ? '('.$employee->department.')' : '' }}
            </option>
        @endforeach
    </select>
</div>
                        </div>

                        <div class="mb-3 text-end">
                            <label class="custom-label mb-1">الوصف <span class="text-danger">*</span></label>
                            <textarea class="form-control custom-input text-end" id="descriptionInput" name="task_description" required rows="2" placeholder="أدخل وصف المهمة"></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6 text-end">
                                <label class="custom-label mb-1">تاريخ البدء <span class="text-danger">*</span></label>
                                <input class="form-control custom-date-btn text-center" id="startDateInput" name="start_task" required type="date" onchange="document.getElementById('endDateInput').min = this.value;"/>
                            </div>
                            <div class="col-6 text-end">
                                <label class="custom-label mb-1">تاريخ الانتهاء <span class="text-danger">*</span></label>
                                <input class="form-control custom-date-btn text-center" id="endDateInput" name="end_task" required type="date"/>
                            </div>
                        </div>

                        <div class="mb-4 text-end">
                            <label class="custom-label mb-1">الحالة <span class="text-danger">*</span></label>
                            <select class="form-select custom-input text-center" id="statusSelect" name="status" required>
                                <option value="قيد التنفيذ">قيد التنفيذ</option>
                                <option value="قيد المراجعة">قيد المراجعة</option>
                                <option value="مكتملة">مكتملة</option>
                                <option value="متوقف مؤقتاً">متوقف مؤقتاً</option>
                                <option value="قيد الانتظار">قيد الانتظار</option>
                            </select>
                        </div>

                        <div class="text-center pt-2">
                            <button class="btn btn-save" type="submit">حفظ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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