@extends('layouts.app')

@section('title', 'إدارة المستخدمين')

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
    <h2 class="task-page-title m-0">إدارة المستخدمين</h2>
        <button type="button" class="btn btn-add-project px-4 py-2" data-bs-toggle="offcanvas" data-bs-target="#addUserOffcanvas" aria-controls="addUserOffcanvas" onclick="prepareAddUserPanel()">
        مستخدم جديد +
    </button>
</div>

<div class="search-filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
    <input type="text" id="userSearchInput" class="form-control custom-input text-end" style="max-width: 260px;" placeholder="بحث بالاسم أو البريد الإلكتروني...">
    <select id="userRoleFilter" class="form-select custom-input text-center" style="max-width: 200px;">
        <option value="">كل الصلاحيات</option>
        <option value="admin">admin</option>
        <option value="manager">manager</option>
        <option value="employee">employee</option>
        <option value="client">client</option>
    </select>
</div>

<div class="table-responsive">
    <table class="table align-middle users-table">
        <thead>
            <tr>
                <th class="text-end">اسم المستخدم</th>
                <th class="text-end">البريد الإلكتروني</th>
                <th class="text-center">الصلاحية</th>
                <th class="text-end">الهاتف</th>
                <th class="text-end">تاريخ الإضافة</th>
                <th class="text-center">إجراءات</th>
            </tr>
        </thead>
                <tbody id="usersTableBody">
            @forelse ($users as $user)
                <tr class="paginate-item"
                    data-filter-match="1"
                    data-username="{{ $user->username }}"
                    data-email="{{ $user->email }}"
                    data-role="{{ $user->role->value }}">
                    <td class="text-end">
                        <span class="user-name">{{ $user->username }}</span>
                    </td>
                    <td class="text-end">
                        <span class="text-muted">{{ $user->email }}</span>
                    </td>
                    <td class="text-center">
                        <span class="role-badge role-badge-{{ $user->role->value }}">{{ $user->role->value }}</span>
                    </td>
                                        <td class="text-end">
                        <span dir="ltr">{{ $user->phone ?? '-' }}</span>
                    </td>
                    <td class="text-end">
                        <span class="text-muted" dir="ltr">{{ $user->created_at?->format('Y-m-d') ?? '-' }}</span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center gap-2">
                                                        <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0"
                                title="تعديل"
                                data-user-id="{{ $user->user_id }}"
                                data-username="{{ $user->username }}"
                                data-email="{{ $user->email }}"
                                data-phone="{{ $user->phone }}"
                                data-company-name="{{ $user->company_name }}"
                                data-role="{{ $user->role->value }}"
                                data-department="{{ optional(\App\Models\Employee::withTrashed()->where('user_id', $user->user_id)->first())->department }}"
                                data-project-ids="{{ implode(',', $clientProjectIds[$user->user_id] ?? []) }}"
                                data-update-url="{{ route('users.update', $user) }}"
                                onclick="openEditUserPanel(this)">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>

                                                        @if ($user->user_id !== auth()->id())
                                <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0"
                                    title="حذف"
                                    onclick="openDeleteUserModal('{{ route('users.destroy', $user) }}', '{{ $user->username }}')">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                        @empty
                <tr><td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمون</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="usersPagination" class="pagination-controls"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ---- Add User modal: role-conditional fields ---- */
    const roleSelect   = document.getElementById('addUserRole');
    const deptField    = document.getElementById('addEmployeeDeptField');
    const deptInput    = document.getElementById('addDepartmentInput');
    const projectField = document.getElementById('addClientProjectField');

    function toggleAddUserFields() {
        if (!roleSelect) return;
        const role = roleSelect.value;

        deptField.classList.toggle('d-none', role !== 'employee');
        deptInput.required = (role === 'employee');
        if (role !== 'employee') deptInput.value = '';

        projectField.classList.toggle('d-none', role !== 'client');
        if (role !== 'client') {
            projectField.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleAddUserFields);
        toggleAddUserFields();
    }

    /* ---- Edit User offcanvas: role-conditional fields (dept + projects) ---- */
    const editRoleSelect = document.getElementById('editRoleSelect');
    if (editRoleSelect) {
        editRoleSelect.addEventListener('change', function () {
            toggleEditUserFields(this.value);
        });
    }

    /* ---- Search + role filter + paginator ---- */
    const paginator = createListPaginator({
        gridSelector: '#usersTableBody',
        itemSelector: 'tr.paginate-item',
        controlsId:   'usersPagination',
        perPage:      8,
    });
    paginator.render();

    function filterUsers() {
        const searchTerm = (document.getElementById('userSearchInput')?.value || '').trim().toLowerCase();
        const roleValue  = document.getElementById('userRoleFilter')?.value || '';

        document.querySelectorAll('#usersTableBody tr.paginate-item').forEach(row => {
            const username = (row.getAttribute('data-username') || '').toLowerCase();
            const email    = (row.getAttribute('data-email') || '').toLowerCase();
            const role     = row.getAttribute('data-role') || '';

            const matchesSearch = !searchTerm || username.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole   = !roleValue || role === roleValue;

            row.setAttribute('data-filter-match', (matchesSearch && matchesRole) ? '1' : '0');
        });

        paginator.reset();
    }

    document.getElementById('userSearchInput')?.addEventListener('input', filterUsers);
    document.getElementById('userRoleFilter')?.addEventListener('change', filterUsers);
});
</script>
@endsection

@push('modals')
<!-- Add User Offcanvas -->
<div class="offcanvas offcanvas-end user-panel" tabindex="-1" id="addUserOffcanvas" aria-labelledby="addUserOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="addUserOffcanvasLabel" style="font-size: 18px; font-weight: 700;">إضافة مستخدم جديد</h5>
        <button type="button" class="btn-close m-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form action="{{ route('users.store') }}" method="POST" id="addUserForm">
            @csrf

            <div class="mb-3 text-end">
                <label class="custom-label mb-1">اسم المستخدم <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control custom-input text-end" required>
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">البريد الإلكتروني <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control custom-input text-end" required>
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">كلمة المرور <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control custom-input text-end" required>
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">الصلاحية <span class="text-danger">*</span></label>
                <select name="role" id="addUserRole" class="form-select custom-input text-center" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">الهاتف</label>
                <input type="text" name="phone" class="form-control custom-input text-end">
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">اسم الشركة</label>
                <input type="text" name="company_name" class="form-control custom-input text-end">
            </div>

            <div class="mb-3 text-end d-none" id="addEmployeeDeptField">
                <label class="custom-label mb-1">القسم</label>
                <input type="text" name="department" id="addDepartmentInput" class="form-control custom-input text-end">
            </div>

                        <div class="mb-3 text-end d-none" id="addClientProjectField">
                <label class="custom-label mb-1">المشاريع</label>
                <input type="text" class="form-control custom-input assignment-search" placeholder="بحث عن مشروع..." data-target="addClientProjectsList">
                <div class="assignment-list" id="addClientProjectsList">
                    @foreach ($projects as $project)
                        <div class="form-check form-check-reverse text-start">
                            <input class="form-check-input" type="checkbox" name="project_ids[]"
                                   id="addProject{{ $project->project_id }}" value="{{ $project->project_id }}">
                            <label class="form-check-label" for="addProject{{ $project->project_id }}">
                                {{ $project->project_name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="text-center pt-2">
                <button class="btn btn-save" type="submit">إضافة</button>
            </div>
        </form>
    </div>
</div>
<!-- Edit User Offcanvas (shared, populated via data-* attrs on the edit button) -->
<div class="offcanvas offcanvas-end user-panel" tabindex="-1" id="editUserOffcanvas" aria-labelledby="editUserOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="editUserOffcanvasLabel" style="font-size: 18px; font-weight: 700;">تعديل مستخدم</h5>
        <button type="button" class="btn-close m-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form action="" method="POST" id="editUserForm">
            @csrf
            @method('PUT')

            <div class="mb-3 text-end">
                <label class="custom-label mb-1">اسم المستخدم <span class="text-danger">*</span></label>
                <input type="text" name="username" id="editUsernameInput" class="form-control custom-input text-end" required>
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">البريد الإلكتروني <span class="text-danger">*</span></label>
                <input type="email" name="email" id="editEmailInput" class="form-control custom-input text-end" required>
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">كلمة مرور جديدة (اتركه فارغاً لعدم التغيير)</label>
                <input type="password" name="password" id="editPasswordInput" class="form-control custom-input text-end">
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">الصلاحية <span class="text-danger">*</span></label>
                <select name="role" id="editRoleSelect" class="form-select custom-input text-center edit-role-select" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">الهاتف</label>
                <input type="text" name="phone" id="editPhoneInput" class="form-control custom-input text-end">
            </div>
            <div class="mb-3 text-end">
                <label class="custom-label mb-1">اسم الشركة</label>
                <input type="text" name="company_name" id="editCompanyInput" class="form-control custom-input text-end">
            </div>

            <div class="mb-3 text-end d-none" id="editEmployeeDeptField">
                <label class="custom-label mb-1">القسم</label>
                <input type="text" name="department" id="editDepartmentInput" class="form-control custom-input text-end">
            </div>

            <div class="mb-3 text-end d-none" id="editClientProjectField">
                <label class="custom-label mb-1">المشاريع</label>
                <input type="text" class="form-control custom-input assignment-search" placeholder="بحث عن مشروع..." data-target="editClientProjectsList">
                <div class="assignment-list" id="editClientProjectsList">
                    @foreach ($projects as $project)
                        <div class="form-check form-check-reverse text-start">
                            <input class="form-check-input" type="checkbox" name="project_ids[]"
                                   id="editProjectCheckbox{{ $project->project_id }}" value="{{ $project->project_id }}">
                            <label class="form-check-label" for="editProjectCheckbox{{ $project->project_id }}">
                                {{ $project->project_name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="text-center pt-2">
                <button class="btn btn-save" type="submit">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>
<!-- Delete User Modal (shared) -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="delete-text mb-4" id="deleteUserModalText">هل تريد حذف هذا المستخدم؟</p>
                <form id="deleteUserForm" method="POST" action="">
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
@endpush