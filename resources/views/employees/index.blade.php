@extends('layouts.app')
@section('title', 'الموظفين')
@section('content-class', 'p-4 flex-grow-1')

@section('content')
@php
    $canManage = auth()->user()->isAdmin();
@endphp

<!-- هيدر قسم الفريق -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="task-page-title m-0">الفريق</h2>
    @if($canManage)
        <button type="button" class="btn btn-add-project px-4 py-2" onclick="prepareAddEmployeeModal('{{ route('users.store') }}')">
            موظف جديد +
        </button>
    @endif
</div>

<!-- شريط البحث (يفلتر التبويب النشط) -->
<div class="search-filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
    <input type="text" id="teamSearchInput" class="form-control custom-input text-end" style="max-width: 320px;" placeholder="بحث بالاسم أو البريد الإلكتروني...">
</div>

<!-- تبويبات: الموظفين / المدراء -->
<ul class="nav nav-tabs" id="teamTabs" role="tablist">
    <li class="nav-item" role="presentation">
               <button class="nav-link active" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees-pane" type="button" role="tab" aria-controls="employees-pane" aria-selected="true">
            الموظفين <span class="tab-count-badge">{{ $employees->count() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
                <button class="nav-link" id="managers-tab" data-bs-toggle="tab" data-bs-target="#managers-pane" type="button" role="tab" aria-controls="managers-pane" aria-selected="false">
            المدراء <span class="tab-count-badge">{{ $managers->count() }}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="teamTabsContent">

    <!-- تبويب الموظفين -->
    <div class="tab-pane fade show active" id="employees-pane" role="tabpanel" aria-labelledby="employees-tab" tabindex="0">
        <div class="table-responsive">
            <table class="table align-middle users-table">
                <thead>
                    <tr>
                        <th class="text-end">اسم الموظف</th>
                        <th class="text-end">البريد الإلكتروني</th>
                        <th class="text-center">المهام</th>
                        <th class="text-center">المشاريع</th>
                        <th class="text-end">تاريخ الإضافة</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="employeesTableBody">
                    @forelse($employees as $employee)
                        <tr class="paginate-item"
                            data-filter-match="1"
                            data-search-text="{{ strtolower($employee->name . ' ' . $employee->email) }}"
                            data-employee-id="{{ $employee->employee_id ?? $employee->id }}"
                            data-employee-name="{{ $employee->name }}"
                            data-department="{{ $employee->department }}"
                            data-employee-email="{{ $employee->email }}"
                            data-employee-phone="{{ $employee->phone }}">
                            <td class="text-end"><span class="user-name">{{ $employee->name }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $employee->email }}</span></td>
                            <td class="text-center">
                                @php $taskCount = $employee->tasks_count ?? 0; @endphp
                                <span class="{{ $taskCount > 0 ? 'fw-bold text-danger' : 'text-muted' }}">{{ $taskCount }}</span>
                            </td>
                            <td class="text-center">
                                @php $projCount = $employee->projects->count(); @endphp
                                @if($projCount > 0)
                                    <span class="badge-project-status" title="{{ $employee->projects->pluck('project_name')->implode(' · ') }}">{{ $projCount }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end"><span class="text-muted" dir="ltr">{{ $employee->created_at?->format('Y-m-d') ?? '-' }}</span></td>
                            <td class="text-center">
                                @if($canManage)
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="تعديل" onclick="openEditEmployeeModal(this, '{{ route('employees.update', $employee) }}')">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف" onclick="openDeleteEmployeeModal(this, '{{ route('employees.destroy', $employee) }}')">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">لا يوجد موظفين حالياً</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="employeesPagination" class="pagination-controls"></div>
    </div>

    <!-- تبويب المدراء -->
    <div class="tab-pane fade" id="managers-pane" role="tabpanel" aria-labelledby="managers-tab" tabindex="0">
        <div class="table-responsive">
            <table class="table align-middle users-table">
                <thead>
                    <tr>
                        <th class="text-end">اسم المدير</th>
                        <th class="text-end">البريد الإلكتروني</th>
                        <th class="text-center">المشاريع المُدارة</th>
                        <th class="text-end">تاريخ الإضافة</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="managersTableBody">
                    @forelse($managers as $manager)
                                                <tr class="paginate-item"
                            data-filter-match="1"
                            data-search-text="{{ strtolower($manager->username . ' ' . $manager->email) }}"
                            data-manager-id="{{ $manager->user_id }}"
                            data-manager-username="{{ $manager->username }}"
                            data-manager-email="{{ $manager->email }}"
                            data-manager-phone="{{ $manager->phone }}">
                            <td class="text-end"><span class="user-name">{{ $manager->username }}</span></td>
                            <td class="text-end"><span class="text-muted">{{ $manager->email }}</span></td>
                            <td class="text-center">
                                @if(($manager->managed_projects_count ?? 0) > 0)
                                    <span class="badge-project-status">{{ $manager->managed_projects_count }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end"><span class="text-muted" dir="ltr">{{ $manager->created_at?->format('Y-m-d') ?? '-' }}</span></td>
                                                        <td class="text-center">
                                @if($canManage)
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="تعديل" onclick="openEditManagerModal(this, '{{ route('users.update', $manager) }}')">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف" onclick="openDeleteManagerModal(this, '{{ route('users.destroy', $manager) }}')">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">لا يوجد مدراء حالياً</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="managersPagination" class="pagination-controls"></div>
    </div>

</div>

<div id="employeesPagination" class="pagination-controls"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const employeesPaginator = createListPaginator({
        gridSelector: '#employeesTableBody',
        itemSelector: 'tr.paginate-item',
        controlsId:   'employeesPagination',
        perPage:      8,
    });
    employeesPaginator.render();

    const managersPaginator = createListPaginator({
        gridSelector: '#managersTableBody',
        itemSelector: 'tr.paginate-item',
        controlsId:   'managersPagination',
        perPage:      8,
    });
    managersPaginator.render();

    function filterTeam() {
        const term = (document.getElementById('teamSearchInput')?.value || '').trim().toLowerCase();

        document.querySelectorAll('#employeesTableBody tr.paginate-item').forEach(row => {
            const haystack = row.getAttribute('data-search-text') || '';
            row.setAttribute('data-filter-match', (!term || haystack.includes(term)) ? '1' : '0');
        });

        document.querySelectorAll('#managersTableBody tr.paginate-item').forEach(row => {
            const haystack = row.getAttribute('data-search-text') || '';
            row.setAttribute('data-filter-match', (!term || haystack.includes(term)) ? '1' : '0');
        });

        employeesPaginator.reset();
        managersPaginator.reset();
    }

    document.getElementById('teamSearchInput')?.addEventListener('input', filterTeam);
});
</script>
@endsection

@push('modals')
@if($canManage)
<!-- 1. مودال إضافة وتعديل موظف -->
<div aria-hidden="true" class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="modal-title m-0" id="employeeModalTitle" style="font-size: 18px; font-weight: 700;">إضافة موظف جديد</h3>
                <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body p-0">
                <form id="employeeForm" method="POST" action="{{ route('employees.store') }}">
    @csrf
    <input type="hidden" name="_method" id="employeeFormMethod" value="POST">
    <input type="hidden" name="role" id="employeeRoleInput" value="employee" disabled>

    <div class="mb-3 text-end">
        <label class="custom-label mb-1">اسم الموظف <span class="text-danger">*</span></label>
        <input class="form-control custom-input text-end" id="employeeNameInput" name="name" required type="text"/>
    </div>

    <div class="mb-3 text-end d-none" id="employeePasswordGroup">
        <label class="custom-label mb-1">كلمة المرور <span class="text-danger">*</span></label>
        <input class="form-control custom-input text-end" id="employeePasswordInput" name="password" type="password" minlength="8" disabled/>
    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">القسم <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="departmentInput" name="department" required type="text"/>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="employeeEmailInput" name="email" required type="email"/>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">رقم الهاتف <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="employeePhoneInput" name="phone" pattern="^05[0-9]{8}$" required title="يرجى إدخال رقم هاتف سعودي صحيح يبدأ بـ 05 ومكون من 10 أرقام" type="tel"/>
                    </div>
                    <div class="text-center pt-2">
                        <button class="btn btn-save" type="submit">حفظ الموظف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 2. مودال تأكيد الحذف للموظف -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="delete-text mb-4" id="deleteEmployeeModalText">هل تريد حذف هذا الموظف؟</p>
                <form id="deleteEmployeeForm" method="POST" action="">
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

<!-- Manager edit modal -->
<div class="modal fade" id="managerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="modal-title m-0" id="managerModalTitle" style="font-size: 18px; font-weight: 700;">تعديل بيانات المدير</h3>
                <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body p-0">
                <form id="managerForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="role" value="manager">

                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">اسم المدير <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="managerNameInput" name="username" required type="text"/>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="managerEmailInput" name="email" required type="email"/>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">كلمة مرور جديدة (اتركه فارغاً لعدم التغيير)</label>
                        <input class="form-control custom-input text-end" id="managerPasswordInput" name="password" type="password" minlength="8"/>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">رقم الهاتف</label>
                        <input class="form-control custom-input text-end" id="managerPhoneInput" name="phone" type="text"/>
                    </div>

                    <div class="text-center pt-2">
                        <button class="btn btn-save" type="submit">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Manager delete modal -->
<div class="modal fade" id="deleteManagerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="delete-text mb-4" id="deleteManagerModalText">هل تريد حذف هذا المدير؟</p>
                <form id="deleteManagerForm" method="POST" action="">
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
@endpush