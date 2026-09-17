@php
    $user      = auth()->user();
    $isAdmin   = $user && $user->isAdmin();
    $isManager = $user && $user->isManager();
    $canUse    = $isAdmin || $isManager;

          // Defensive: partial may be included from views that don't pass these.
    $projects  = collect($projects ?? []);
    $employees = collect($employees ?? []);

    // Self-heal: on pages that don't pass $employees (e.g. projects/show),
    // load them here so the "مسند إلى" dropdown is never empty for admin/manager.
    if ($employees->isEmpty() && ($isAdmin || $isManager)) {
        $employees = \App\Models\Employee::all();
    }

    // Fallback: on pages with a single $project context (projects/show),
    // ensure the current project is in the dropdown so preselect can work.
    if (isset($project) && $project instanceof \App\Models\Project) {
        if (! $projects->contains('project_id', $project->project_id)) {
            $projects = collect([$project])->concat($projects);
        }
    }
@endphp

@if($canUse)
<div class="offcanvas offcanvas-end task-panel" tabindex="-1" id="taskPanel" aria-labelledby="taskPanelTitle">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title task-panel-title" id="taskPanelTitle">إضافة مهمة</h5>
        <button type="button" class="btn-close m-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="taskForm" action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="taskFormMethod" value="POST">

            <div class="mb-3 text-end">
                <label class="custom-label mb-1">اسم المهمة <span class="text-danger">*</span></label>
                <input class="form-control custom-input text-end" id="taskNameInput" name="task_title" required type="text" placeholder="أدخل اسم المهمة"/>
            </div>

            <div class="mb-3 text-end">
                <label class="custom-label mb-1">اسم المشروع <span class="text-danger">*</span></label>
                <select class="form-select custom-input text-center" id="projectIdInput" name="project_id" onchange="updateProjectDatesLimits(); updateStageOptions();" required>
                    <option value="">اختر المشروع</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->project_id }}"
                                data-start="{{ $project->start_project }}"
                                data-end="{{ $project->end_project }}"
                                data-company="{{ $project->company_name }}"
                                data-stages="{{ $project->stages->map(fn($s) => ['id' => $s->project_stage_id, 'label' => $s->stage_key->label()])->toJson() }}">
                            {{ $project->project_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3 text-end">
                <label class="custom-label mb-1">المرحلة</label>
                <select class="form-select custom-input text-center" id="stageIdInput" name="stage_id">
                    <option value="">اختر مشروعاً أولاً</option>
                </select>
            </div>

          <div class="mb-3 text-end">
    <label class="custom-label mb-2">مسند إلى <span class="text-danger">*</span></label>
    <input type="text" class="form-control custom-input assignment-search" placeholder="بحث عن موظف..." data-target="taskAssignedToList">
    <div class="assignment-list" id="taskAssignedToList">
        @foreach($employees as $employee)
            <div class="form-check form-check-reverse text-start">
                <input class="form-check-input task-assignee-checkbox" type="checkbox" name="assigned_to[]" value="{{ $employee->employee_id ?? $employee->id }}" id="task_assignee_{{ $employee->employee_id ?? $employee->id }}">
                <label class="form-check-label" for="task_assignee_{{ $employee->employee_id ?? $employee->id }}">
                    {{ $employee->name }} {{ isset($employee->department) ? '('.$employee->department.')' : '' }}
                </label>
            </div>
        @endforeach
    </div>
</div>

            <div class="mb-3 text-end">
                <label class="custom-label mb-1">الوصف <span class="text-danger">*</span></label>
                <textarea class="form-control custom-input text-end" id="descriptionInput" name="task_description" required rows="3" placeholder="أدخل وصف المهمة"></textarea>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 text-end">
                    <label class="custom-label mb-1">تاريخ البدء <span class="text-danger">*</span></label>
                    <input class="form-control custom-date-btn text-center" id="startDateInput" name="start_task" required type="date"/>
                </div>
                <div class="col-6 text-end">
                    <label class="custom-label mb-1">تاريخ الانتهاء <span class="text-danger">*</span></label>
                    <input class="form-control custom-date-btn text-center" id="endDateInput" name="end_task" required type="date"/>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 text-end">
                    <label class="custom-label mb-1">الحالة <span class="text-danger">*</span></label>
                    <select class="form-select custom-input text-center" id="statusSelect" name="status" required>
                        <option value="قيد التنفيذ">قيد التنفيذ</option>
                        <option value="قيد المراجعة">قيد المراجعة</option>
                        <option value="مكتملة">مكتملة</option>
                        <option value="متوقف مؤقتاً">متوقف مؤقتاً</option>
                        <option value="قيد الانتظار">قيد الانتظار</option>
                    </select>
                </div>
                <div class="col-6 text-end">
                    <label class="custom-label mb-1">الأولوية <span class="text-danger">*</span></label>
                    <select class="form-select custom-input text-center" id="prioritySelect" name="priority" required>
                        <option value="منخفض">منخفض</option>
                        <option value="متوسط" selected>متوسط</option>
                        <option value="عالي">عالي</option>
                    </select>
                </div>
            </div>

            {{-- Existing attachments (edit-mode only; populated by JS from data-attachments) --}}
            <div class="mb-3 text-end d-none" id="existingAttachmentsBlock">
                <label class="custom-label mb-1">المرفقات الحالية</label>
                <div id="existingAttachmentsList" class="d-flex flex-column gap-2"></div>
            </div>

            {{-- Add new files (add-mode and edit-mode) --}}
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">إضافة مرفقات جديدة</label>
                <input class="d-none" id="taskAttachmentsInput" name="attachments[]" type="file" multiple onchange="showTaskAttachmentsPreview(this)" />
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn task-attach-btn" onclick="document.getElementById('taskAttachmentsInput').click();">
                        <i class="fa-solid fa-paperclip me-1"></i> اختيار ملفات
                    </button>
                </div>
                <div id="taskAttachmentsPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
            </div>

            <div class="text-center pt-3 border-top">
                <button class="btn btn-save" type="submit">حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Hidden delete form for existing task attachments --}}
<form id="deleteTaskAttachmentForm" method="POST" action="" class="d-none">
    @csrf
    @method('DELETE')
</form>

{{-- Confirm delete attachment modal --}}
<div class="modal fade" id="deleteTaskAttachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="delete-text mb-4" id="deleteTaskAttachmentText">هل تريد حذف هذا المرفق؟</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-delete-confirm" onclick="submitDeleteTaskAttachment()">حذف</button>
                    <button type="button" class="btn btn-delete-cancel" data-bs-dismiss="modal">إلغاء</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif