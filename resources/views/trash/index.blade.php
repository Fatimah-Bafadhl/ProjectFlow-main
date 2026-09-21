@extends('layouts.app')
@section('title', 'المحذوفات')

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
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="task-page-title m-0">المحذوفات</h2>
</div>

<ul class="nav nav-tabs mb-4" id="trashTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-projects" type="button">المشاريع ({{ $projects->count() }})</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button">المهام ({{ $tasks->count() }})</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-employees" type="button">الموظفين ({{ $employees->count() }})</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-clients" type="button">العملاء ({{ $clients->count() }})</button>
    </li>
</ul>

<div class="tab-content">

    <div class="tab-pane fade show active" id="tab-projects">
        @forelse($projects as $item)
            <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                <div>
                    <strong>{{ $item->project_name }}</strong>
                    <div class="text-muted small">حُذف في: {{ $item->deleted_at }}</div>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('trash.restore', ['type' => 'project', 'id' => $item->project_id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">استعادة</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmForceDelete('{{ route('trash.forceDelete', ['type' => 'project', 'id' => $item->project_id]) }}', '{{ $item->project_name }}')">حذف نهائي</button>
                </div>
            </div>
        @empty
            <p class="text-muted">لا توجد مشاريع محذوفة</p>
        @endforelse
    </div>

    <div class="tab-pane fade" id="tab-tasks">
        @forelse($tasks as $item)
            <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                <div>
                    <strong>{{ $item->task_title }}</strong>
                    <div class="text-muted small">حُذف في: {{ $item->deleted_at }}</div>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('trash.restore', ['type' => 'task', 'id' => $item->task_id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">استعادة</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmForceDelete('{{ route('trash.forceDelete', ['type' => 'task', 'id' => $item->task_id]) }}', '{{ $item->task_title }}')">حذف نهائي</button>
                </div>
            </div>
        @empty
            <p class="text-muted">لا توجد مهام محذوفة</p>
        @endforelse
    </div>

    <div class="tab-pane fade" id="tab-employees">
        @forelse($employees as $item)
            <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                <div>
                    <strong>{{ $item->name }}</strong>
                    <div class="text-muted small">حُذف في: {{ $item->deleted_at }}</div>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('trash.restore', ['type' => 'employee', 'id' => $item->employee_id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">استعادة</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmForceDelete('{{ route('trash.forceDelete', ['type' => 'employee', 'id' => $item->employee_id]) }}', '{{ $item->name }}')">حذف نهائي</button>
                </div>
            </div>
        @empty
            <p class="text-muted">لا يوجد موظفين محذوفين</p>
        @endforelse
    </div>

    <div class="tab-pane fade" id="tab-clients">
        @forelse($clients as $item)
            <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                <div>
                    <strong>{{ $item->name }}</strong>
                    <div class="text-muted small">حُذف في: {{ $item->deleted_at }}</div>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('trash.restore', ['type' => 'client', 'id' => $item->client_id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">استعادة</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmForceDelete('{{ route('trash.forceDelete', ['type' => 'client', 'id' => $item->client_id]) }}', '{{ $item->name }}')">حذف نهائي</button>
                </div>
            </div>
        @empty
            <p class="text-muted">لا يوجد عملاء محذوفين</p>
        @endforelse
    </div>

</div>

<!-- Force delete confirmation modal -->
<div class="modal fade" id="forceDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="text-danger fw-bold mb-2">تحذير: هذا الإجراء نهائي ولا يمكن التراجع عنه</p>
                <p class="mb-4" id="forceDeleteModalText"></p>
                <form id="forceDeleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-delete-confirm">تأكيد الحذف النهائي</button>
                        <button type="button" class="btn btn-delete-cancel" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmForceDelete(url, name) {
    document.getElementById('forceDeleteForm').action = url;
    document.getElementById('forceDeleteModalText').innerText = 'هل تريد حذف "' + name + '" نهائياً؟';
    new bootstrap.Modal(document.getElementById('forceDeleteModal')).show();
}
</script>
@endpush