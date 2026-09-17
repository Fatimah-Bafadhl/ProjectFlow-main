@extends('layouts.app')
@section('title', 'المهام')
@section('content-class', 'p-4 flex-grow-1')

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

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="task-page-title m-0">المهام <span class="tab-count-badge">{{ $tasks->count() }}</span></h2>
    @if($isAdmin || $isManager)
        <button class="btn btn-add-task d-flex align-items-center gap-2"
                data-bs-target="#taskPanel"
                data-bs-toggle="offcanvas"
                onclick="prepareAddModal(this)">
            <span>إضافة مهمة +</span>
        </button>
    @endif
</div>

{{-- Filter bar --}}
<div class="search-filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
    <input type="text" id="taskSearchInput"
           class="form-control custom-input text-end"
           style="max-width: 260px;"
           placeholder="بحث باسم المهمة أو المشروع...">

    <select id="taskProjectFilter" class="form-select custom-input text-center" style="max-width: 220px;">
        <option value="">كل المشاريع</option>
        @foreach($projects as $project)
            <option value="{{ $project->project_id }}">{{ $project->project_name }}</option>
        @endforeach
    </select>

    <select id="taskStatusFilter" class="form-select custom-input text-center" style="max-width: 180px;">
        <option value="">كل الحالات</option>
        <option value="قيد الانتظار">قيد الانتظار</option>
        <option value="قيد التنفيذ">قيد التنفيذ</option>
        <option value="قيد المراجعة">قيد المراجعة</option>
        <option value="مكتملة">مكتملة</option>
        <option value="متوقف مؤقتاً">متوقف مؤقتاً</option>
    </select>

    <select id="taskPriorityFilter" class="form-select custom-input text-center" style="max-width: 180px;">
        <option value="">كل الأولويات</option>
        <option value="منخفض">منخفض</option>
        <option value="متوسط">متوسط</option>
        <option value="عالي">عالي</option>
    </select>
</div>

{{-- Tasks table --}}
<div class="table-responsive">
    <table class="table align-middle users-table">
        <thead>
            <tr>
                <th class="text-end">المهمة</th>
                <th class="text-end">المشروع</th>
                <th class="text-end">المسند إلى</th>
                <th class="text-center">الأولوية</th>
                <th class="text-center">الحالة</th>
                <th class="text-end">تاريخ الانتهاء</th>
                <th class="text-center">إجراءات</th>
            </tr>
        </thead>
        <tbody id="tasksTableBody">
            @forelse($tasks as $task)
                @php
                    $attachmentsJson = $task->attachments->map(function ($a) {
                        return [
                            'id'    => $a->task_attachment_id,
                            'title' => $a->title,
                            'type'  => $a->type,
                            'url'   => $a->type === 'link' ? $a->url : asset('storage/' . $a->file_path),
                        ];
                    })->values()->all();

                    $statusClass = match($task->status) {
                        'قيد الانتظار' => 'badge-status-waiting',
                        'قيد التنفيذ' => 'badge-status-progress',
                        'قيد المراجعة' => 'badge-status-review',
                        'مكتملة' => 'badge-status-done',
                        'متوقف مؤقتاً' => 'badge-status-paused',
                        default => 'badge-status-default',
                    };

                                                         $projectName  = optional($task->project)->project_name;
                    $assigneeName = $task->assignedEmployees->pluck('name')->implode('، ');
                    $searchText   = strtolower(trim($task->task_title . ' ' . ($projectName ?? '') . ' ' . $assigneeName));
                @endphp
                <tr class="paginate-item"
                    data-filter-match="1"
                    data-search-text="{{ $searchText }}"
                    data-task-id="{{ $task->task_id }}"
                    data-task-title="{{ $task->task_title }}"
                    data-project-id="{{ $task->project_id }}"
                    data-stage-id="{{ $task->stage_id }}"
                    data-assigned-to="{{ $task->assignedEmployees->pluck('employee_id') }}"
                    data-description="{{ $task->task_description }}"
                    data-start-date="{{ $task->start_task }}"
                    data-end-date="{{ $task->end_task }}"
                    data-status="{{ $task->status }}"
                    data-priority="{{ $task->priority ?? 'متوسط' }}"
                    data-attachments="{{ json_encode($attachmentsJson, JSON_UNESCAPED_UNICODE) }}">

                                        <td class="text-end">
                        <a class="user-name text-decoration-none" href="{{ route('tasks.show', $task->task_id) }}">
                            {{ $task->task_title }}
                        </a>
                    </td>

                    <td class="text-end">
                        @if($task->project)
                            <a href="{{ route('projects.show', $task->project->project_id) }}"
                               class="text-decoration-none text-muted"
                               style="font-size: 13px;">
                                {{ $projectName }}
                            </a>
                        @else
                            <span class="text-muted">غير محدد</span>
                        @endif
                    </td>

                    <td class="text-end">
                        <span class="text-muted">{{ $assigneeName !== '' ? $assigneeName : 'غير مسند' }}</span>

                </td>

                    <td class="text-center">
                        <span class="badge-task-priority {{ $task->priority_class }}">{{ $task->priority_label }}</span>
                    </td>

                    <td class="text-center">
                        <span class="badge-task-status {{ $statusClass }}">{{ $task->status }}</span>
                    </td>

                    <td class="text-end">
                        <span class="text-muted" dir="ltr">
                            {{ $task->end_task ? \Carbon\Carbon::parse($task->end_task)->format('Y-m-d') : '-' }}
                        </span>
                    </td>

                    <td class="text-center">
                        @if($isAdmin || $isManager)
                            <div class="d-inline-flex align-items-center gap-2">
                                <button type="button" class="btn-icon border-0 bg-transparent p-0" title="تعديل" onclick="openEditModal(this)">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn-icon border-0 bg-transparent p-0" title="حذف" onclick="openDeleteModal(this)">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        @elseif($isEmployee)
                            <button type="button" class="btn-icon border-0 bg-transparent p-0" title="تعديل الحالة"
                                    onclick="openEmployeeTaskStatusModal('{{ $task->task_id }}', '{{ $task->status }}', '{{ route('tasks.update', $task->task_id) }}')">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">لا توجد مهام حالياً</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="tasksPagination" class="pagination-controls"></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tasksPaginator = createListPaginator({
        gridSelector: '#tasksTableBody',
        itemSelector: 'tr.paginate-item',
        controlsId:   'tasksPagination',
        perPage:      8,
    });
    tasksPaginator.render();

    function filterTasks() {
        const term     = (document.getElementById('taskSearchInput')?.value || '').trim().toLowerCase();
        const project  = document.getElementById('taskProjectFilter')?.value || '';
        const status   = document.getElementById('taskStatusFilter')?.value || '';
        const priority = document.getElementById('taskPriorityFilter')?.value || '';

        document.querySelectorAll('#tasksTableBody tr.paginate-item').forEach(row => {
            const haystack = row.getAttribute('data-search-text') || '';
            const rowProj  = row.getAttribute('data-project-id') || '';
            const rowStat  = row.getAttribute('data-status') || '';
            const rowPrio  = row.getAttribute('data-priority') || '';

            const matchesSearch   = !term     || haystack.includes(term);
            const matchesProject  = !project  || rowProj === project;
            const matchesStatus   = !status   || rowStat === status;
            const matchesPriority = !priority || rowPrio === priority;

            const shouldShow = matchesSearch && matchesProject && matchesStatus && matchesPriority;
            row.setAttribute('data-filter-match', shouldShow ? '1' : '0');
        });

        tasksPaginator.reset();
    }

    document.getElementById('taskSearchInput')?.addEventListener('input', filterTasks);
    document.getElementById('taskProjectFilter')?.addEventListener('change', filterTasks);
    document.getElementById('taskStatusFilter')?.addEventListener('change', filterTasks);
    document.getElementById('taskPriorityFilter')?.addEventListener('change', filterTasks);
});
</script>
@endpush
@endsection

@push('modals')
@if(!$isClient)

    {{-- لوحة إضافة/تعديل المهمة (Offcanvas) — مشتركة --}}
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