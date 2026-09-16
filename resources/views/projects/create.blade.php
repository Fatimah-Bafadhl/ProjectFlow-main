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

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb custom-breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">المشاريع</a></li>
        <li class="breadcrumb-item active" aria-current="page">إضافة مشروع جديد</li>
    </ol>
</nav>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white form-card-wide">
    <form id="projectForm" action="{{ route('projects.store') }}" method="POST">
        @csrf

        <!-- Section 1: Basic Info -->
        <h5 class="section-title mb-3">المعلومات الأساسية</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6 text-end">
                <label class="custom-label mb-1">اسم المشروع <span class="text-danger">*</span></label>
                <input class="form-control custom-input text-end" id="projectNameInput" name="project_name" required type="text" value="{{ old('project_name') }}"/>
            </div>
            <div class="col-md-6 text-end">
                <label class="custom-label mb-1">اسم الشركة <span class="text-danger">*</span></label>
                <input class="form-control custom-input text-end" id="projectCompanyNameInput" name="company_name" required type="text" placeholder="أدخلي اسم الشركة أو العميل" value="{{ old('company_name') }}"/>
            </div>
            <div class="col-12 text-end">
                <label class="custom-label mb-1">الوصف <span class="text-danger">*</span></label>
                <textarea class="form-control custom-input text-end" id="projectDescInput" name="project_description" rows="3" required>{{ old('project_description') }}</textarea>
            </div>
        </div>

        <!-- Section 2: Timeline & Type -->
        <h5 class="section-title mb-3">التفاصيل الزمنية والنوع</h5>
        <div class="row g-3 mb-4">
                       <div class="col-md-4 text-end">
                <label class="custom-label mb-1">نوع المشروع <span class="text-danger">*</span></label>
                <select class="form-select custom-input text-center" id="projectTypeSelect" name="project_type" required>
                    @foreach($projectTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('project_type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 text-end">
                <label class="custom-label mb-1">تاريخ البدء <span class="text-danger">*</span></label>
                <input class="form-control custom-date-btn text-center" id="projectStartDateInput" name="start_project" required type="date" value="{{ old('start_project') }}" onchange="document.getElementById('projectEndDateInput').min = this.value;"/>
            </div>
            <div class="col-md-4 text-end">
                <label class="custom-label mb-1">تاريخ الانتهاء <span class="text-danger">*</span></label>
                <input class="form-control custom-date-btn text-center" id="projectEndDateInput" name="end_project" required type="date" value="{{ old('end_project') }}"/>
            </div>
</div>
                    <!-- Section 3: Team Assignment -->
        <h5 class="section-title mb-3">فريق العمل والمسؤوليات</h5>
        <div class="row g-4 mb-4">
            @if(auth()->user()->isAdmin())
            <div class="col-md-4 text-end">
                <label class="custom-label mb-2">المدراء المسؤولون</label>
                <input type="text" class="form-control custom-input assignment-search" placeholder="بحث عن مدير..." data-target="managersList">
                            <div class="assignment-list" id="managersList">
                    @foreach($managers as $manager)
                        <div class="form-check form-check-reverse text-start">
                            <input class="form-check-input" type="checkbox" name="manager_ids[]" value="{{ $manager->user_id }}" id="manager_{{ $manager->user_id }}" @checked(in_array($manager->user_id, old('manager_ids', [])))>
                            <label class="form-check-label" for="manager_{{ $manager->user_id }}">{{ $manager->username }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="col-md-4 text-end">
                <label class="custom-label mb-2">فريق العمل (الموظفون)</label>
                <input type="text" class="form-control custom-input assignment-search" placeholder="بحث عن موظف..." data-target="employeesList">
                               <div class="assignment-list" id="employeesList">
                    @foreach($employees as $employee)
                        <div class="form-check form-check-reverse text-start">
                            <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $employee->employee_id }}" id="employee_{{ $employee->employee_id }}" @checked(in_array($employee->employee_id, old('employee_ids', [])))>
                            <label class="form-check-label" for="employee_{{ $employee->employee_id }}">{{ $employee->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-4 text-end">
                <label class="custom-label mb-2">العميل</label>
                <input type="text" class="form-control custom-input assignment-search" placeholder="بحث عن عميل..." data-target="clientsList">
                              <div class="assignment-list" id="clientsList">
                    @forelse($clients as $client)
                        <div class="form-check form-check-reverse text-start">
                            <input class="form-check-input" type="checkbox" name="client_ids[]" value="{{ $client->client_id }}" id="client_{{ $client->client_id }}" @checked(in_array($client->client_id, old('client_ids', [])))>
                            <label class="form-check-label" for="client_{{ $client->client_id }}">{{ $client->name }} ({{ $client->company_name }})</label>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">لا يوجد عملاء بعد. يمكنك إضافة عميل من صفحة المستخدمين ثم اختياره هنا.</p>
                    @endforelse
                </div>
            </div>
        </div>

                <div class="d-flex justify-content-start gap-3 pt-3 border-top">
            <button class="btn btn-save px-5" type="submit">حفظ المشروع</button>
            <a href="{{ route('projects.index') }}" class="btn btn-light px-4" style="border-radius: 25px; border: 1px solid #E5E5E5; color: #6C757D;">إلغاء</a>
        </div>
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