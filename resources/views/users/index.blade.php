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
    <button type="button" class="btn btn-add-project px-4 py-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
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
                                data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->user_id }}">
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

    /* ---- Edit User modals: role-conditional dept field ---- */
    document.querySelectorAll('.edit-role-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const uid = this.getAttribute('data-user-id');
            const deptField = document.getElementById('editEmployeeDeptField' + uid);
            const deptInput = document.getElementById('editDepartmentInput' + uid);
            if (!deptField || !deptInput) return;
            const isEmployee = this.value === 'employee';

            deptField.classList.toggle('d-none', !isEmployee);
            deptInput.required = isEmployee;
        });
    });

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
<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="modal-title m-0" style="font-size: 18px; font-weight: 700;">إضافة مستخدم جديد</h3>
                <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body p-0">
                <form action="{{ route('users.store') }}" method="POST">
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
                        <div class="border rounded p-2 text-end" style="max-height: 160px; overflow-y: auto;">
                            @foreach ($projects as $project)
                                <div class="form-check">
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
    </div>
</div>

@foreach ($users as $user)
<div class="modal fade" id="editUserModal{{ $user->user_id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="modal-title m-0" style="font-size: 18px; font-weight: 700;">تعديل مستخدم</h3>
                <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body p-0">
                <form action="{{ route('users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control custom-input text-end" value="{{ $user->username }}" required>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control custom-input text-end" value="{{ $user->email }}" required>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">كلمة مرور جديدة (اتركه فارغاً لعدم التغيير)</label>
                        <input type="password" name="password" class="form-control custom-input text-end">
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">الصلاحية <span class="text-danger">*</span></label>
                        <select name="role" class="form-select custom-input text-center edit-role-select" data-user-id="{{ $user->user_id }}" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>
                                    {{ $role->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">الهاتف</label>
                        <input type="text" name="phone" class="form-control custom-input text-end" value="{{ $user->phone }}">
                    </div>
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">اسم الشركة</label>
                        <input type="text" name="company_name" class="form-control custom-input text-end" value="{{ $user->company_name }}">
                    </div>
                    <div class="mb-3 text-end employee-dept-field {{ $user->role->value !== 'employee' ? 'd-none' : '' }}" id="editEmployeeDeptField{{ $user->user_id }}">
                        <label class="custom-label mb-1">القسم</label>
                        <input type="text" name="department" class="form-control custom-input text-end" id="editDepartmentInput{{ $user->user_id }}" value="{{ optional(\App\Models\Employee::withTrashed()->where('user_id', $user->user_id)->first())->department }}" {{ $user->role->value === 'employee' ? 'required' : '' }}>
                    </div>
                    @if ($user->role === \App\Enums\Role::Client)
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">المشاريع</label>
                        <div class="border rounded p-2 text-end" style="max-height: 160px; overflow-y: auto;">
                            @foreach ($projects as $project)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="project_ids[]"
                                           id="editProject{{ $user->user_id }}_{{ $project->project_id }}"
                                           value="{{ $project->project_id }}"
                                           {{ in_array($project->project_id, $clientProjectIds[$user->user_id] ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="editProject{{ $user->user_id }}_{{ $project->project_id }}">
                                        {{ $project->project_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="text-center pt-2">
                        <button class="btn btn-save" type="submit">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

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