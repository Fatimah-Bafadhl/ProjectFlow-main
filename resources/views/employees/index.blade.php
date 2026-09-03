@extends('layouts.app')
@section('title', 'الموظفين')
@section('content-class', 'p-4 flex-grow-1')

@section('content')
@php
    $canManage = auth()->user()->isAdmin() || auth()->user()->isManager();
@endphp

<!-- هيدر قسم الموظفين -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="task-page-title m-0">الموظفين</h2>
    
</div>

<!-- كارد إجمالي الموظفين -->
<div class="total-clients-card mb-4">
    <div class="count-number" id="totalEmployeesCount">{{ $employees->count() }}</div>
    <div class="label-text">
        <span>اجمالي الموظفين</span>
        <i class="fa-solid fa-user-tie card-icon"></i>
    </div>
</div>

<!-- قائمة الموظفين -->
<div class="clients-container-scroll px-1" style="max-height: 480px; overflow-y: auto;">
    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3" id="employeesList">
        @forelse($employees as $employee)
            <div class="col employee-card-wrapper" 
                 data-employee-id="{{ $employee->employee_id ?? $employee->id }}"
                 data-employee-name="{{ $employee->name }}"
                 data-department="{{ $employee->department }}"
                 data-employee-email="{{ $employee->email }}"
                 data-employee-phone="{{ $employee->phone }}">
                 
                <div class="client-card p-3 rounded-3 w-100 border">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h3 class="client-name m-0">{{ $employee->name }}</h3>
                            <div class="company-name">{{ $employee->department }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="client-badge">موظف</span>
                            
                            @if($canManage)
                                <div class="task-actions">
                                    <button type="button" class="btn-icon text-muted me-1 border-0 bg-transparent p-0" onclick="openEditEmployeeModal(this, '{{ route('employees.update', $employee) }}')">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" onclick="openDeleteEmployeeModal(this, '{{ route('employees.destroy', $employee) }}')">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <hr class="my-2 text-muted"/>
                    <div class="client-details d-flex flex-column gap-1 text-muted" style="font-size: 13px;">
                        <div><i class="fa-regular fa-envelope me-2"></i><span class="employee-email">{{ $employee->email }}</span></div>
                        <div><i class="fa-solid fa-phone me-2"></i><span class="employee-phone" dir="ltr">{{ $employee->phone }}</span></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-4" id="noEmployeesMessage">
                <p class="text-muted">لا يوجد موظفين حالياً</p>
            </div>
        @endforelse
    </div>
</div>
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

                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">اسم الموظف <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="employeeNameInput" name="name" required type="text"/>
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
@endif
@endpush