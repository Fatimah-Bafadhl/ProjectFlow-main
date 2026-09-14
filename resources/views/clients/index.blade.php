@extends('layouts.app')
@section('title', 'العملاء')
@section('content-class', 'p-4 flex-grow-1')

@section('content')
@php
    $user = auth()->user();
    $email = $user->email ?? '';
    $isClient = $user->role === \App\Enums\Role::Client;
    $isEmployee = $user->role === \App\Enums\Role::Employee;
    $canManage = $user->isAdmin();
@endphp
<!-- هيدر قسم العملاء -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="task-page-title m-0">العملاء <span class="tab-count-badge">{{ $clients->count() }}</span></h2>
    @if(auth()->user()->isAdmin())
        <button type="button" class="btn btn-add-project px-4 py-2" onclick="prepareAddClientModal('{{ route('users.store') }}')">
            عميل جديد +
        </button>
    @endif
</div>

<!-- شريط البحث -->
<div class="search-filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
    <input type="text" id="clientSearchInput" class="form-control custom-input text-end" style="max-width: 320px;" placeholder="بحث بالاسم أو البريد الإلكتروني أو الشركة...">
</div>

<!-- جدول العملاء -->
<div class="table-responsive">
    <table class="table align-middle users-table">
        <thead>
            <tr>
                <th class="text-end">اسم العميل</th>
                <th class="text-end">البريد الإلكتروني</th>
                <th class="text-end">الشركة</th>
                <th class="text-end">المشاريع</th>
                <th class="text-end">الهاتف</th>
                <th class="text-end">تاريخ الإضافة</th>
                <th class="text-center">إجراءات</th>
            </tr>
        </thead>
        <tbody id="clientsTableBody">
            @forelse($clients as $client)
                <tr class="paginate-item"
                    data-filter-match="1"
                    data-search-text="{{ strtolower($client->name . ' ' . $client->email . ' ' . $client->company_name) }}"
                    data-client-id="{{ $client->client_id ?? $client->id }}"
                    data-client-name="{{ $client->name }}"
                    data-company-name="{{ $client->company_name }}"
                    data-client-email="{{ $client->email }}"
                    data-client-phone="{{ $client->phone }}"
                    data-client-project="{{ $client->project_name }}"
                    data-client-project-ids="{{ $client->projects->pluck('project_id')->implode(',') }}">
                    <td class="text-end"><span class="user-name">{{ $client->name }}</span></td>
                    <td class="text-end"><span class="text-muted">{{ $client->email }}</span></td>
                    <td class="text-end">{{ $client->company_name ?? '-' }}</td>
                                        <td class="text-end">
                        @if($client->projects->count() > 0)
                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                @foreach($client->projects as $project)
                                    <span class="badge-project-status">{{ $project->project_name }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end"><span dir="ltr">{{ $client->phone ?? '-' }}</span></td>
                    <td class="text-end"><span class="text-muted" dir="ltr">{{ $client->created_at?->format('Y-m-d') ?? '-' }}</span></td>
                    <td class="text-center">
                        @if($canManage)
                            <div class="d-inline-flex align-items-center gap-2">
                                <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="تعديل" onclick="openEditClientModal(this, '{{ route('clients.update', $client) }}')">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn-icon text-muted border-0 bg-transparent p-0" title="حذف" onclick="openDeleteClientModal(this, '{{ route('clients.destroy', $client) }}')">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">لا يوجد عملاء حالياً</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="clientsPagination" class="pagination-controls"></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const paginator = createListPaginator({
        gridSelector: '#clientsTableBody',
        itemSelector: 'tr.paginate-item',
        controlsId:   'clientsPagination',
        perPage:      8,
    });
    paginator.render();

    function filterClients() {
        const term = (document.getElementById('clientSearchInput')?.value || '').trim().toLowerCase();
        document.querySelectorAll('#clientsTableBody tr.paginate-item').forEach(row => {
            const haystack = row.getAttribute('data-search-text') || '';
            row.setAttribute('data-filter-match', (!term || haystack.includes(term)) ? '1' : '0');
        });
        paginator.reset();
    }

    document.getElementById('clientSearchInput')?.addEventListener('input', filterClients);
});
</script>
@endpush
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
    <input type="hidden" name="role" id="clientRoleInput" value="client" disabled>

    <div class="mb-3 text-end">
        <label class="custom-label mb-1">اسم العميل <span class="text-danger">*</span></label>
        <input class="form-control custom-input text-end" id="clientNameInput" name="name" required type="text"/>
    </div>

    <div class="mb-3 text-end d-none" id="clientPasswordGroup">
        <label class="custom-label mb-1">كلمة المرور <span class="text-danger">*</span></label>
        <input class="form-control custom-input text-end" id="clientPasswordInput" name="password" type="password" minlength="8" disabled/>
    </div>

                                        <div class="mb-3 text-end">
                        <label class="custom-label mb-1">اسم الشركة <span class="text-danger">*</span></label>
                        <input class="form-control custom-input text-end" id="companyNameInput" name="company_name" required type="text"/>
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