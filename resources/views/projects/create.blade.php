@extends('layouts.app')
@section('title', 'إضافة مشروع جديد')
@section('content-class', 'p-4 flex-grow-1')

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
    <h2 class="task-page-title m-0">إضافة مشروع جديد</h2>
    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">العودة للمشاريع</a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="border: 1px solid #EFEEF3 !important; max-width: 700px;">
    <form id="projectForm" action="{{ route('projects.store') }}" method="POST">
        @csrf

        <div class="mb-3 text-end">
            <label class="custom-label mb-1">اسم المشروع <span class="text-danger">*</span></label>
            <input class="form-control custom-input text-end" id="projectNameInput" name="project_name" required type="text" value="{{ old('project_name') }}"/>
        </div>

        <div class="mb-3 text-end">
            <label class="custom-label mb-1">اسم الشركة <span class="text-danger">*</span></label>
            <input class="form-control custom-input text-end" id="projectCompanyNameInput" name="company_name" required type="text" placeholder="أدخلي اسم الشركة أو العميل" value="{{ old('company_name') }}"/>
        </div>

        <div class="mb-3 text-end">
            <label class="custom-label mb-1">الوصف <span class="text-danger">*</span></label>
            <textarea class="form-control custom-input text-end" id="projectDescInput" name="project_description" rows="2" required>{{ old('project_description') }}</textarea>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6 text-end">
                <label class="custom-label mb-1">تاريخ البدء <span class="text-danger">*</span></label>
                <input class="form-control custom-date-btn text-center" id="projectStartDateInput" name="start_project" required type="date" value="{{ old('start_project') }}" onchange="document.getElementById('projectEndDateInput').min = this.value;"/>
            </div>
            <div class="col-6 text-end">
                <label class="custom-label mb-1">تاريخ الانتهاء <span class="text-danger">*</span></label>
                <input class="form-control custom-date-btn text-center" id="projectEndDateInput" name="end_project" required type="date" value="{{ old('end_project') }}"/>
            </div>
        </div>

        <div class="mb-4 text-end">
            <label class="custom-label mb-1">نوع المشروع <span class="text-danger">*</span></label>
            <select class="form-select custom-input text-center" id="projectTypeSelect" name="project_type" required>
                @foreach($projectTypes as $type)
                    <option value="{{ $type->value }}" @selected(old('project_type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        @if(auth()->user()->isAdmin())
        <div class="mb-3 text-end">
            <label class="custom-label mb-1">المدراء المسؤولون</label>
            <div class="border rounded-3 p-2" style="max-height: 150px; overflow-y: auto;">
                @foreach($managers as $manager)
                    <div class="form-check text-end">
                        <input class="form-check-input" type="checkbox" name="manager_ids[]" value="{{ $manager->user_id }}" id="manager_{{ $manager->user_id }}" @checked(in_array($manager->user_id, old('manager_ids', [])))>
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
                        <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $employee->employee_id }}" id="employee_{{ $employee->employee_id }}" @checked(in_array($employee->employee_id, old('employee_ids', [])))>
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
                        <input class="form-check-input" type="checkbox" name="client_ids[]" value="{{ $client->client_id }}" id="client_{{ $client->client_id }}" @checked(in_array($client->client_id, old('client_ids', [])))>
                        <label class="form-check-label" for="client_{{ $client->client_id }}">{{ $client->name }} ({{ $client->company_name }})</label>
                    </div>
                @empty
                    <p class="text-muted small mb-0">لا يوجد عملاء بعد. يمكنك إضافة عميل من صفحة المستخدمين ثم اختياره هنا.</p>
                @endforelse
            </div>
        </div>

        <div class="text-center pt-2">
            <button class="btn btn-save px-5" type="submit">حفظ المشروع</button>
        </div>
    </form>
</div>

<script>
    // Default start date to today, matching old modal behavior
    document.addEventListener('DOMContentLoaded', function () {
        const startInput = document.getElementById('projectStartDateInput');
        const endInput = document.getElementById('projectEndDateInput');
        const today = new Date().toISOString().split('T')[0];
        if (!startInput.value) {
            startInput.min = today;
            startInput.value = today;
        }
        if (startInput.value) {
            endInput.min = startInput.value;
        }
    });
</script>
@endsection