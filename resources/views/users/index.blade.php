@if ($errors->any())
    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@extends('layouts.app')

@section('title', 'إدارة المستخدمين')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">إدارة المستخدمين</h3>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fa-solid fa-plus me-1"></i> إضافة مستخدم
    </button>
</div>

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>البريد الإلكتروني</th>
                <th>الصلاحية</th>
                <th>الهاتف</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role->value }}</td>
                    <td>{{ $user->phone ?? '-' }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->user_id }}">
                            <i class="fa-solid fa-pen"></i>
                        </button>

                        @if ($user->user_id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editUserModal{{ $user->user_id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('users.update', $user) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">تعديل مستخدم</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">اسم المستخدم</label>
                                        <input type="text" name="username" class="form-control" value="{{ $user->username }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">البريد الإلكتروني</label>
                                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">كلمة مرور جديدة (اتركه فارغاً لعدم التغيير)</label>
                                        <input type="password" name="password" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">الصلاحية</label>
                                        <select name="role" class="form-select" required>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>
                                                    {{ $role->value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">الهاتف</label>
                                        <input type="text" name="phone" class="form-control" value="{{ $user->phone }}">
                                    </div>
                                                                        <div class="mb-3">
                                        <label class="form-label">اسم الشركة</label>
                                        <input type="text" name="company_name" class="form-control" value="{{ $user->company_name }}">
                                    </div>
                                    @if ($user->role === \App\Enums\Role::Client)
                                    <div class="mb-3">
                                        <label class="form-label">المشاريع</label>
                                        <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
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
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <tr><td colspan="5" class="text-center">لا يوجد مستخدمون</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مستخدم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الصلاحية</label>
                        <select name="role" id="addUserRole" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}">{{ $role->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم الشركة</label>
                        <input type="text" name="company_name" class="form-control">
                    </div>

                    <div class="mb-3 d-none" id="addEmployeeDeptField">
    <label class="form-label">القسم</label>
    <input type="text" name="department" id="addDepartmentInput" class="form-control">
</div>

<div class="mb-3 d-none" id="addClientProjectField">
    <label class="form-label">المشاريع</label>
    <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
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
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('addUserRole');
    const deptField = document.getElementById('addEmployeeDeptField');
    const deptInput = document.getElementById('addDepartmentInput');
    const projectField = document.getElementById('addClientProjectField');
 //   const projectSelect = document.getElementById('addProjectSelect');

        function toggleAddUserFields() {
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
});
</script>
@endsection