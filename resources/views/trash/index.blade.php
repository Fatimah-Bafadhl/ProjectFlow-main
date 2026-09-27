@extends('layouts.app')
@section('title', 'المحذوفات')

@section('content')

@include('partials.form-errors')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="task-page-title m-0">المحذوفات</h2>
</div>

<div class="search-filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
    <input type="text" id="trashSearchInput" class="form-control custom-input text-end" style="max-width: 320px;" placeholder="بحث بالاسم...">
</div>

<ul class="nav nav-tabs mb-4" id="trashTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="projects-tab" data-bs-toggle="tab" data-bs-target="#tab-projects" type="button" role="tab">المشاريع <span class="tab-count-badge">{{ $projects->count() }}</span></button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button" role="tab">المهام <span class="tab-count-badge">{{ $tasks->count() }}</span></button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="employees-tab" data-bs-toggle="tab" data-bs-target="#tab-employees" type="button" role="tab">الموظفين <span class="tab-count-badge">{{ $employees->count() }}</span></button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#tab-clients" type="button" role="tab">العملاء <span class="tab-count-badge">{{ $clients->count() }}</span></button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#tab-users" type="button" role="tab">المستخدمون <span class="tab-count-badge">{{ $users->count() }}</span></button>
    </li>
</ul>

<div class="tab-content">

    {{-- المشاريع --}}
    <div class="tab-pane fade show active" id="tab-projects" role="tabpanel">
        <div class="table-responsive">
            <table class="table align-middle users-table">
                <thead>
                    <tr>
                        <th class="text-end">اسم المشروع</th>
                        <th class="text-end">الشركة</th>
                        <th class="text-end">تاريخ الحذف</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="projectsTableBody">
                    @forelse($projects as $item)
                        <tr class="paginate-item" data-filter-match="1" data-search-text="{{ strtolower($item->project_name) }}">
                            <td class="text-end"><span class="user-name">{{ $item->project_name }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $item->company_name ?? '-' }}</span></td>
                            <td class="text-end"><span class="text-muted" dir="ltr">{{ $item->deleted_at?->format('Y-m-d H:i') }}</span></td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="استعادة"
                                        data-restore-url="{{ route('trash.restore', ['type' => 'project', 'id' => $item->project_id]) }}"
                                        data-restore-name="{{ $item->project_name }}"
                                        onclick="openConfirmRestoreModal(this)">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف نهائي"
                                        data-delete-url="{{ route('trash.forceDelete', ['type' => 'project', 'id' => $item->project_id]) }}"
                                        data-delete-name="{{ $item->project_name }}"
                                        data-delete-permanent="1"
                                        onclick="openConfirmDeleteModal(this)">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد مشاريع محذوفة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="projectsPagination" class="pagination-controls"></div>
    </div>

    {{-- المهام --}}
    <div class="tab-pane fade" id="tab-tasks" role="tabpanel">
        <div class="table-responsive">
            <table class="table align-middle users-table">
                <thead>
                    <tr>
                        <th class="text-end">اسم المهمة</th>
                        <th class="text-end">المشروع</th>
                        <th class="text-end">المرحلة</th>
                        <th class="text-end">تاريخ الحذف</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="tasksTableBody">
                    @forelse($tasks as $item)
                        <tr class="paginate-item" data-filter-match="1" data-search-text="{{ strtolower($item->task_title) }}">
                            <td class="text-end"><span class="user-name">{{ $item->task_title }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $item->project->project_name ?? '-' }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $item->stage?->stage_key?->label() ?? '-' }}</span></td>
                            <td class="text-end"><span class="text-muted" dir="ltr">{{ $item->deleted_at?->format('Y-m-d H:i') }}</span></td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="استعادة"
                                        data-restore-url="{{ route('trash.restore', ['type' => 'task', 'id' => $item->task_id]) }}"
                                        data-restore-name="{{ $item->task_title }}"
                                        onclick="openConfirmRestoreModal(this)">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف نهائي"
                                        data-delete-url="{{ route('trash.forceDelete', ['type' => 'task', 'id' => $item->task_id]) }}"
                                        data-delete-name="{{ $item->task_title }}"
                                        data-delete-permanent="1"
                                        onclick="openConfirmDeleteModal(this)">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">لا توجد مهام محذوفة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="tasksPagination" class="pagination-controls"></div>
    </div>

    {{-- الموظفين --}}
    <div class="tab-pane fade" id="tab-employees" role="tabpanel">
        <div class="table-responsive">
            <table class="table align-middle users-table">
                <thead>
                    <tr>
                        <th class="text-end">اسم الموظف</th>
                        <th class="text-end">القسم</th>
                        <th class="text-end">تاريخ الحذف</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="employeesTrashTableBody">
                    @forelse($employees as $item)
                        <tr class="paginate-item" data-filter-match="1" data-search-text="{{ strtolower($item->name) }}">
                            <td class="text-end"><span class="user-name">{{ $item->name }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $item->department ?? '-' }}</span></td>
                            <td class="text-end"><span class="text-muted" dir="ltr">{{ $item->deleted_at?->format('Y-m-d H:i') }}</span></td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="استعادة"
                                        data-restore-url="{{ route('trash.restore', ['type' => 'employee', 'id' => $item->employee_id]) }}"
                                        data-restore-name="{{ $item->name }}"
                                        onclick="openConfirmRestoreModal(this)">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف نهائي"
                                        data-delete-url="{{ route('trash.forceDelete', ['type' => 'employee', 'id' => $item->employee_id]) }}"
                                        data-delete-name="{{ $item->name }}"
                                        data-delete-permanent="1"
                                        onclick="openConfirmDeleteModal(this)">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا يوجد موظفين محذوفين</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="employeesTrashPagination" class="pagination-controls"></div>
    </div>

    {{-- العملاء --}}
    <div class="tab-pane fade" id="tab-clients" role="tabpanel">
        <div class="table-responsive">
            <table class="table align-middle users-table">
                <thead>
                    <tr>
                        <th class="text-end">اسم العميل</th>
                        <th class="text-end">الشركة</th>
                        <th class="text-end">تاريخ الحذف</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="clientsTableBody">
                    @forelse($clients as $item)
                        <tr class="paginate-item" data-filter-match="1" data-search-text="{{ strtolower($item->name) }}">
                            <td class="text-end"><span class="user-name">{{ $item->name }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $item->company_name ?? '-' }}</span></td>
                            <td class="text-end"><span class="text-muted" dir="ltr">{{ $item->deleted_at?->format('Y-m-d H:i') }}</span></td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="استعادة"
                                        data-restore-url="{{ route('trash.restore', ['type' => 'client', 'id' => $item->client_id]) }}"
                                        data-restore-name="{{ $item->name }}"
                                        onclick="openConfirmRestoreModal(this)">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف نهائي"
                                        data-delete-url="{{ route('trash.forceDelete', ['type' => 'client', 'id' => $item->client_id]) }}"
                                        data-delete-name="{{ $item->name }}"
                                        data-delete-permanent="1"
                                        onclick="openConfirmDeleteModal(this)">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا يوجد عملاء محذوفين</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="clientsPagination" class="pagination-controls"></div>
    </div>

    {{-- المستخدمون (admin/manager) --}}
    <div class="tab-pane fade" id="tab-users" role="tabpanel">
        <div class="table-responsive">
            <table class="table align-middle users-table">
                <thead>
                    <tr>
                        <th class="text-end">اسم المستخدم</th>
                        <th class="text-end">الدور</th>
                        <th class="text-end">تاريخ الحذف</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="usersTrashTableBody">
                    @forelse($users as $item)
                        <tr class="paginate-item" data-filter-match="1" data-search-text="{{ strtolower($item->username) }}">
                            <td class="text-end"><span class="user-name">{{ $item->username }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $item->role->label() }}</span></td>
                            <td class="text-end"><span class="text-muted" dir="ltr">{{ $item->deleted_at?->format('Y-m-d H:i') }}</span></td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="استعادة"
                                        data-restore-url="{{ route('trash.restore', ['type' => 'user', 'id' => $item->user_id]) }}"
                                        data-restore-name="{{ $item->username }}"
                                        onclick="openConfirmRestoreModal(this)">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف نهائي"
                                        data-delete-url="{{ route('trash.forceDelete', ['type' => 'user', 'id' => $item->user_id]) }}"
                                        data-delete-name="{{ $item->username }}"
                                        data-delete-permanent="1"
                                        onclick="openConfirmDeleteModal(this)">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا يوجد مستخدمون محذوفون</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="usersTrashPagination" class="pagination-controls"></div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paginators = {
        projects: createListPaginator({ gridSelector: '#projectsTableBody', itemSelector: 'tr.paginate-item', controlsId: 'projectsPagination', perPage: 8 }),
        tasks: createListPaginator({ gridSelector: '#tasksTableBody', itemSelector: 'tr.paginate-item', controlsId: 'tasksPagination', perPage: 8 }),
        employees: createListPaginator({ gridSelector: '#employeesTrashTableBody', itemSelector: 'tr.paginate-item', controlsId: 'employeesTrashPagination', perPage: 8 }),
        clients: createListPaginator({ gridSelector: '#clientsTableBody', itemSelector: 'tr.paginate-item', controlsId: 'clientsPagination', perPage: 8 }),
        users: createListPaginator({ gridSelector: '#usersTrashTableBody', itemSelector: 'tr.paginate-item', controlsId: 'usersTrashPagination', perPage: 8 }),
    };
    Object.values(paginators).forEach(p => p.render());

    document.getElementById('trashSearchInput')?.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        document.querySelectorAll('.tab-pane tbody tr.paginate-item').forEach(row => {
            const haystack = row.getAttribute('data-search-text') || '';
            row.setAttribute('data-filter-match', (!term || haystack.includes(term)) ? '1' : '0');
        });
        Object.values(paginators).forEach(p => p.reset());
    });
});
</script>

@endsection

@push('modals')
@include('partials.confirm-delete-modal')
@include('partials.confirm-restore-modal')
@endpush