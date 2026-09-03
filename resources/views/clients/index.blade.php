@extends('layouts.app')
@section('title', 'العملاء')
@section('content-class', 'p-4 flex-grow-1')

@section('content')
@php
    $user = auth()->user();
    $email = $user->email ?? '';
    $isClient = $user->role === \App\Enums\Role::Client;
$isEmployee = $user->role === \App\Enums\Role::Employee;
    $canManage = !$isClient && !$isEmployee;
@endphp

<!-- هيدر قسم العملاء -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="task-page-title m-0">العملاء</h2>
    
</div>

<!-- كارد إجمالي العملاء -->
<div class="total-clients-card mb-4">
    <div class="count-number" id="totalClientsCount">{{ $clients->count() }}</div>
    <div class="label-text">
        <span>اجمالي العملاء</span>
        <i class="fa-solid fa-users-rectangle card-icon"></i>
    </div>
</div>

<!-- قائمة العملاء -->
<div class="clients-container-scroll px-1" style="max-height: 480px; overflow-y: auto;">
    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3" id="clientsList">
        @forelse($clients as $client)
                       <div class="col client-card-wrapper" 
                 data-client-id="{{ $client->client_id ?? $client->id }}"
                 data-client-name="{{ $client->name }}"
                 data-company-name="{{ $client->company_name }}"
                 data-client-email="{{ $client->email }}"
                 data-client-phone="{{ $client->phone }}"
                 data-client-project="{{ $client->project_name }}"
                 data-client-project-ids="{{ $client->projects->pluck('project_id')->implode(',') }}">
                 
                <div class="client-card p-3 rounded-3 w-100 border">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h3 class="client-name m-0">{{ $client->name }}</h3>
                            <div class="company-name">{{ $client->company_name }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="client-badge">عميل</span>
                            
                            @if($canManage)
                            <div class="task-actions">
                                <button type="button" class="btn-icon text-muted me-1 border-0 bg-transparent p-0" onclick="openEditClientModal(this, '{{ route('clients.update', $client) }}')">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" onclick="openDeleteClientModal(this, '{{ route('clients.destroy', $client) }}')">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    <hr class="my-2 text-muted"/>
                    <div class="client-details d-flex flex-column gap-1 text-muted" style="font-size: 13px;">
                        <div><i class="fa-regular fa-envelope me-2"></i><span class="client-email">{{ $client->email }}</span></div>
                        <div><i class="fa-solid fa-phone me-2"></i><span class="client-phone" dir="ltr">{{ $client->phone }}</span></div>
                        <div><i class="fa-solid fa-briefcase me-2"></i>اسم المشروع: <span class="client-project">{{ $client->project_name }}</span></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-4" id="noClientsMessage">
                <p class="text-muted">لا يوجد عملاء حالياً</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('modals')
@if($canManage)
<!-- 1. مودال إضافة وتعديل عميل -->
<div aria-hidden="true" class="modal fade" id="clientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="modal-title m-0" id="clientModalTitle" style="font-size: 18px; font-weight: 700;">إضافة عميل جديد</h3>
                <button aria-label="Close" class="btn-close m-0" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body p-0">
                <form id="clientForm" method="POST" action="{{ route('clients.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="clientFormMethod" value="POST">

                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">اسم العميل <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="clientNameInput" name="name" required type="text"/>
                    </div>

                    <!-- حقل اسم الشركة (قائمة منسدلة إلزامية مرتبطة بقاعدة البيانات) -->
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">اسم الشركة <span class="text-danger">*</span></label>
                        <select class="form-control custom-input text-end" id="companyNameInput" name="company_name" required>
                            <option value="" disabled selected>اختر الشركة المناسبة</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->company_name }}">{{ $company->company_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="clientEmailInput" name="email" required type="email"/>
                    </div>
                    
                    <div class="mb-3 text-end">
                        <label class="custom-label mb-1">رقم الهاتف <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="clientPhoneInput" name="phone" pattern="^05[0-9]{8}$" required title="يرجى إدخال رقم هاتف سعودي صحيح يبدأ بـ 05 ومكون من 10 أرقام" type="tel"/>
                    </div>
                    
                                        <div class="mb-3 text-end">
                        <label class="custom-label mb-1">المشاريع <span class="text-danger">*</span></label>
                        <div class="border rounded p-2 text-end" id="clientProjectsCheckboxes" style="max-height: 160px; overflow-y: auto;">
                            @foreach($projects as $project)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="project_ids[]"
                                           id="clientProject{{ $project->project_id }}" value="{{ $project->project_id }}">
                                    <label class="form-check-label" for="clientProject{{ $project->project_id }}">
                                        {{ $project->project_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="text-center pt-2">
                        <button class="btn btn-save" type="submit">حفظ العميل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 2. مودال تأكيد الحذف للعميل -->
<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="delete-text mb-4" id="deleteClientModalText">هل تريد حذف هذا العميل؟</p>
                <form id="deleteClientForm" method="POST" action="">
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