@extends('layouts.app')
@section('title', 'المشاريع')
@section('content-class', 'p-4 flex-grow-1')

@php
    $user = auth()->user();
   $isClient = $user && $user->isClient();
$isEmployee = $user && $user->isEmployee();
$isAdmin = $user && $user->isAdmin();
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

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <h2 class="task-page-title m-0">المشاريع</h2>
    </div>

    @if(!$isClient && !$isEmployee)
    <a href="{{ route('projects.create') }}" class="btn btn-add-project px-4 py-2">
    مشروع جديد +
</a>
    @endif
</div>

<div class="search-filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
    <input type="text" id="projectSearchInput" class="form-control custom-input text-end" style="max-width: 260px;" placeholder="بحث باسم المشروع أو الشركة...">

    <select id="projectStatusFilter" class="form-select custom-input text-center" style="max-width: 200px;">
        <option value="">كل الحالات</option>
        @foreach(\App\Enums\ProjectStageName::cases() as $stageCase)
            <option value="{{ $stageCase->label() }}">{{ $stageCase->label() }}</option>
        @endforeach
        <option value="مكتملة">مكتملة</option>
    </select>

    <select id="projectTypeFilter" class="form-select custom-input text-center" style="max-width: 200px;">
        <option value="">كل الأنواع</option>
        @foreach($projectTypes as $type)
            <option value="{{ $type->value }}">{{ $type->label() }}</option>
        @endforeach
    </select>
</div>

<div class="projects-scroll-container projects-list-unscrolled">
    <div class="d-flex flex-column gap-2" id="projectsGrid">
        @forelse($projects as $project)
            @php
                $totalTasks = $project->tasks ? $project->tasks->count() : 0;
                $progress = $project->progress ?? 0;
                $stageColor = $project->stageColor();
                $openTickets = $project->open_tickets_count ?? 0;
                $projectManagers = $project->managers ?? collect();
                $managersCount = $projectManagers->count();
            @endphp
            <div class="project-row project-card-wrapper d-flex align-items-center justify-content-between gap-3 p-3"
                 data-project-id="{{ $project->project_id }}"
                 data-project-name="{{ $project->project_name }}"
                 data-company-name="{{ $project->company_name }}"
                 data-project-desc="{{ $project->project_description }}"
                 data-start-date="{{ $project->start_project }}"
                 data-end-date="{{ $project->end_project }}"
                 data-status="{{ $project->status }}"
                 data-project-type="{{ $project->project_type->value }}"
                                  data-manager-ids="{{ $projectManagers->pluck('user_id')->implode(',') }}"
                 data-employee-ids="{{ $project->employees->pluck('employee_id')->implode(',') }}"
                 data-client-ids="{{ $project->clients->pluck('client_id')->implode(',') }}"
                 data-task-count="{{ $totalTasks }}"
                 data-comment-count="{{ $project->comments_count ?? 0 }}"
                 data-open-ticket-count="{{ $openTickets }}">

                <div class="d-flex flex-column align-items-start text-end" style="min-width: 160px;">
                    <a class="text-decoration-none text-dark project-card-title" href="{{ route('projects.show', $project->project_id) }}">
                        {{ $project->project_name }}
                    </a>
                    <span class="project-card-desc">{{ $project->company_name ?: 'غير محدد' }}</span>
                </div>

                @if($isAdmin)
                <div class="d-flex flex-column align-items-start text-end extra-small text-muted" style="min-width: 100px;">
                    <span>المدير:</span>
                    <span class="fw-bold text-dark">
                        @if($managersCount > 0)
                            {{ $projectManagers->first()->username }}
                            @if($managersCount > 1)
                                <span class="badge-project-status">+{{ $managersCount - 1 }}</span>
                            @endif
                        @else
                            غير محدد
                        @endif
                    </span>
                </div>
                @endif

                               <span class="badge-project-status stage-badge d-inline-flex align-items-center justify-content-center" style="background-color: {{ $stageColor }}1A; color: {{ $stageColor }};">
                    {{ $project->status }}
                </span>

                <div style="min-width: 140px;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark" style="font-size: 13px;">{{ $progress }}%</span>
                        <span class="text-muted extra-small">الإنجاز الكلي</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: #f0f0f5; border-radius: 3px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%; background-color: #8A84AD; border-radius: 3px;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-1 text-muted extra-small">
                    <i class="fa-solid fa-list-check"></i>
                    <span>{{ $totalTasks }} مهام</span>
                </div>

                               <div class="d-flex align-items-center gap-1 extra-small {{ $openTickets > 0 ? 'text-danger' : 'text-muted' }}">
                    <i class="fa-solid fa-ticket"></i>
                    <span>{{ $openTickets }}</span>
                </div>

                <div class="d-flex align-items-center gap-1 text-muted extra-small">
                    <i class="fa-regular fa-calendar-check"></i>
                    <span>{{ $project->end_project ?? 'غير محدد' }}</span>
                </div>

                @if(!$isClient && !$isEmployee)
                <div class="d-flex align-items-center gap-2">
                    <button class="btn-icon text-muted border-0 bg-transparent p-0" title="تعديل" onclick="openEditProjectModal(this, '{{ route('projects.update', $project->project_id) }}')">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </button>
                    <button class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف" onclick="openDeleteProjectModal(this, '{{ route('projects.destroy', $project->project_id) }}')">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
                @endif
            </div>
               @empty
            <div class="text-center py-5">
                <p class="text-muted">لا توجد مشاريع مضافة حالياً.</p>
            </div>
        @endforelse
    </div>
</div>

<div id="projectsPagination" class="pagination-controls"></div>
@endsection

@push('modals')
@if(!$isClient)
    @if(!$isEmployee)
    <div aria-hidden="true" class="modal fade" id="projectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="modal-title m-0" id="projectModalTitle" style="font-size: 18px; font-weight: 700;">إضافة مشروع جديد</h3>
                    <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="projectForm" action="{{ route('projects.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="projectFormMethod" value="POST">
                        
                        <div class="mb-3 text-end">
                            <label class="custom-label mb-1">اسم المشروع <span class="text-danger">*</span></label>
                            <input class="form-control custom-input text-end" id="projectNameInput" name="project_name" required type="text"/>
                        </div>

                        <div class="mb-3 text-end">
                            <label class="custom-label mb-1">اسم الشركة <span class="text-danger">*</span></label>
                            <input class="form-control custom-input text-end" id="projectCompanyNameInput" name="company_name" required type="text" placeholder="أدخلي اسم الشركة أو العميل"/>
                        </div>

                        <div class="mb-3 text-end">
                            <label class="custom-label mb-1">الوصف <span class="text-danger">*</span></label>
                            <textarea class="form-control custom-input text-end" id="projectDescInput" name="project_description" rows="2" required></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6 text-end">
                                <label class="custom-label mb-1">تاريخ البدء <span class="text-danger">*</span></label>
                                <input class="form-control custom-date-btn text-center" id="projectStartDateInput" name="start_project" required type="date" onchange="document.getElementById('projectEndDateInput').min = this.value;"/>
                            </div>
                            <div class="col-6 text-end">
                                <label class="custom-label mb-1">تاريخ الانتهاء <span class="text-danger">*</span></label>
                                <input class="form-control custom-date-btn text-center" id="projectEndDateInput" name="end_project" required type="date"/>
                            </div>
                        </div>

                        

                        <div class="mb-4 text-end">
                            <label class="custom-label mb-1">نوع المشروع <span class="text-danger">*</span></label>
                            <select class="form-select custom-input text-center" id="projectTypeSelect" name="project_type" required>
                                @foreach($projectTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                                                                              @if($isAdmin)
<div class="mb-3 text-end">
    <label class="custom-label mb-1">المدراء المسؤولون</label>
    <div class="border rounded-3 p-2" style="max-height: 150px; overflow-y: auto;">
        @foreach($managers as $manager)
            <div class="form-check text-end">
                <input class="form-check-input project-manager-checkbox" type="checkbox" name="manager_ids[]" value="{{ $manager->user_id }}" id="manager_{{ $manager->user_id }}">
                <label class="form-check-label" for="manager_{{ $manager->user_id }}">{{ $manager->username }}</label>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="mb-4 text-end">
    <label class="custom-label mb-1">فريق العمل (الموظفون)</label>
    <div class="border rounded-3 p-2" style="max-height: 150px; overflow-y: auto;">
        @foreach($employees as $employee)
            <div class="form-check text-end">
                <input class="form-check-input project-employee-checkbox" type="checkbox" name="employee_ids[]" value="{{ $employee->employee_id }}" id="employee_{{ $employee->employee_id }}">
                <label class="form-check-label" for="employee_{{ $employee->employee_id }}">{{ $employee->name }}</label>
            </div>
        @endforeach
    </div>
</div>

<div class="mb-4 text-end">
    <label class="custom-label mb-1">العميل</label>
    <div class="border rounded-3 p-2" style="max-height: 150px; overflow-y: auto;">
        @forelse($clients as $client)
            <div class="form-check text-end">
                <input class="form-check-input project-client-checkbox" type="checkbox" name="client_ids[]" value="{{ $client->client_id }}" id="modal_client_{{ $client->client_id }}">
                <label class="form-check-label" for="modal_client_{{ $client->client_id }}">{{ $client->name }} ({{ $client->company_name }})</label>
            </div>
        @empty
            <p class="text-muted small mb-0">لا يوجد عملاء بعد.</p>
        @endforelse
    </div>
</div>
                        <div class="text-center pt-2">
                            <button class="btn btn-save" type="submit">حفظ المشروع</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal p-4 text-center">
                <div class="modal-body p-0">
                    <p class="delete-text mb-4" id="deleteProjectModalText">هل تريد حذف هذا المشروع؟</p>
                    <form id="deleteProjectForm" method="POST" action="">
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
@endif
 
@endpush

@push('scripts')
<script>
            function prepareAddProjectModal(actionUrl) {
        document.getElementById('projectForm').action = actionUrl;
        document.getElementById('projectFormMethod').value = 'POST';
        document.getElementById('projectModalTitle').innerText = 'إضافة مشروع جديد';
        document.getElementById('projectNameInput').value = '';
        document.getElementById('projectCompanyNameInput').value = '';
        document.getElementById('projectDescInput').value = '';
                document.querySelectorAll('.project-manager-checkbox, .project-employee-checkbox, .project-client-checkbox').forEach(cb => cb.checked = false);
        // عند الإضافة: يقبل من تاريخ اليوم فصاعداً ولا يقبل تواريخ ماضية
        const today = new Date().toISOString().split('T')[0];
        const startDateInput = document.getElementById('projectStartDateInput');
        startDateInput.min = today;
        startDateInput.value = today;
        
        const endDateInput = document.getElementById('projectEndDateInput');
        endDateInput.min = today;
        endDateInput.value = '';

      //  document.getElementById('projectStatusSelect').value = 'قيد التنفيذ';

        var myModal = new bootstrap.Modal(document.getElementById('projectModal'));
        myModal.show();
    }

        function openEditProjectModal(button, actionUrl) {
        const card = button.closest('.project-card-wrapper');
        const form = document.getElementById('projectForm');
        form.action = actionUrl;
        document.getElementById('projectFormMethod').value = 'PUT';
        document.getElementById('projectModalTitle').innerText = 'تعديل المشروع';


        const startDate = card.getAttribute('data-start-date');
        const endDate = card.getAttribute('data-end-date');
        
        document.getElementById('projectNameInput').value = card.getAttribute('data-project-name');
        document.getElementById('projectCompanyNameInput').value = card.getAttribute('data-company-name');
        document.getElementById('projectDescInput').value = card.getAttribute('data-project-desc');
        document.getElementById('projectTypeSelect').value = card.getAttribute('data-project-type');
        
        // عند التعديل: يبقي تاريخ البدء كما هو، ويسمح بالبدء من تاريخ البدء الأصلي فصاعداً
        const startDateInput = document.getElementById('projectStartDateInput');
        startDateInput.min = startDate; 
        startDateInput.value = startDate;

        const endDateInput = document.getElementById('projectEndDateInput');
        endDateInput.min = startDate;
        endDateInput.value = endDate;

       // document.getElementById('projectStatusSelect').value = card.getAttribute('data-status');

        var myModal = new bootstrap.Modal(document.getElementById('projectModal'));
        const managerIds = (card.getAttribute('data-manager-ids') || '').split(',').filter(Boolean);
document.querySelectorAll('.project-manager-checkbox').forEach(cb => {
    cb.checked = managerIds.includes(cb.value);
});

const employeeIds = (card.getAttribute('data-employee-ids') || '').split(',').filter(Boolean);
document.querySelectorAll('.project-employee-checkbox').forEach(cb => {
    cb.checked = employeeIds.includes(cb.value);
});

const clientIds = (card.getAttribute('data-client-ids') || '').split(',').filter(Boolean);
document.querySelectorAll('.project-client-checkbox').forEach(cb => {
    cb.checked = clientIds.includes(cb.value);
});
        myModal.show();
    }

        function openDeleteProjectModal(button, actionUrl) {
        const card = button.closest('.project-card-wrapper');
        const projectName = card.getAttribute('data-project-name');
        const taskCount = card.getAttribute('data-task-count') || 0;
        const commentCount = card.getAttribute('data-comment-count') || 0;
        const openTicketCount = card.getAttribute('data-open-ticket-count') || 0;

        document.getElementById('deleteProjectModalText').innerText =
            `هل تريد حذف مشروع "${projectName}"؟ سيتم إخفاء ${taskCount} مهمة و ${commentCount} تعليق من العرض (لن يتم حذفها نهائياً)، ويوجد ${openTicketCount} تذكرة مفتوحة.`;

        const form = document.getElementById('deleteProjectForm');
        form.action = actionUrl;
        var myModal = new bootstrap.Modal(document.getElementById('deleteProjectModal'));
        myModal.show();
    }

           /* ==========================================
       Projects Pagination (client-side, 8 per page)
    ========================================== */
    const projectsPaginator = createListPaginator({
        gridSelector: '#projectsGrid',
        itemSelector: '.project-card-wrapper',
        controlsId:   'projectsPagination',
        perPage:      8,
    });
    projectsPaginator.render();

    function filterProjects() {
        const searchTerm = (document.getElementById('projectSearchInput').value || '').trim().toLowerCase();
        const statusValue = document.getElementById('projectStatusFilter').value;
        const typeValue = document.getElementById('projectTypeFilter').value;

        document.querySelectorAll('#projectsGrid .project-card-wrapper').forEach(row => {
            const name = (row.getAttribute('data-project-name') || '').toLowerCase();
            const company = (row.getAttribute('data-company-name') || '').toLowerCase();
            const status = row.getAttribute('data-status') || '';
            const type = row.getAttribute('data-project-type') || '';

            const matchesSearch = !searchTerm || name.includes(searchTerm) || company.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesType = !typeValue || type === typeValue;
            const shouldShow = matchesSearch && matchesStatus && matchesType;

            // Mark the row; the paginator will handle visibility.
            row.setAttribute('data-filter-match', shouldShow ? '1' : '0');
        });

        // Filter change => jump back to page 1.
        projectsPaginator.reset();
    }

    document.getElementById('projectSearchInput')?.addEventListener('input', filterProjects);
    document.getElementById('projectStatusFilter')?.addEventListener('change', filterProjects);
    document.getElementById('projectTypeFilter')?.addEventListener('change', filterProjects);
</script>
@endpush