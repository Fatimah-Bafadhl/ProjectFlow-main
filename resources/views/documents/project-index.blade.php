@extends('layouts.app')
@section('title', 'مستندات المشروع - ' . $project->project_name)
@section('content-class', 'p-4 flex-grow-1')

@section('content')

@php
    $canManage = auth()->user()->isAdmin() || auth()->user()->isManager();
    $linkDocs = $documents->where('type', 'link')->values();
    $fileDocs = $documents->where('type', 'file')->values();
@endphp

{{-- Breadcrumb: المشاريع / المشروع / المستندات --}}
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0" style="font-size: 14px;">
        <li class="breadcrumb-item">
            <a class="text-decoration-none text-muted" href="{{ route('projects.index') }}">المشاريع</a>
        </li>
        <li class="breadcrumb-item">
            <a class="text-decoration-none text-muted" href="{{ route('projects.show', $project->project_id) }}">
                {{ $project->project_name }}
            </a>
        </li>
        <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">المستندات</li>
    </ol>
</nav>

{{-- Errors (success handled by the global modal) --}}
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

{{-- Search + Add form (collapsed) --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="position-relative" style="flex: 1; min-width: 240px; max-width: 420px;">
        <input type="text" id="docSearchInput" class="form-control custom-input text-end ps-5"
               placeholder="بحث بعنوان المستند...">
        <i class="fa-solid fa-magnifying-glass position-absolute top-50 translate-middle-y start-0 ms-3 text-muted" style="font-size: 13px;"></i>
    </div>
</div>

@if($canManage)
<div class="collapse mb-3" id="addDocumentForm">
    <form action="{{ route('documents.store', $project->project_id) }}" method="POST" enctype="multipart/form-data"
          class="border rounded-3 p-3" style="background-color: #FAF9FB; border-color: #E5E5E5 !important;">
        @csrf
        <div class="row g-2 mb-2">
            <div class="col-md-4">
                <label class="custom-label mb-1">النوع</label>
                <select name="type" id="doc_type" class="form-select custom-input" onchange="toggleDocumentFields(this.value)">
                    <option value="link">رابط</option>
                    <option value="file">ملف</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="custom-label mb-1">العنوان <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control custom-input" required maxlength="255" placeholder="عنوان المستند">
            </div>
        </div>

        <div class="mb-2" id="url_field">
            <label class="custom-label mb-1">الرابط <span class="text-danger">*</span></label>
            <input type="url" name="url" id="url_input" class="form-control custom-input" placeholder="https://..." maxlength="2048" required>
        </div>

        <div class="mb-2 d-none" id="file_field">
            <label class="custom-label mb-1">الملف <span class="text-danger">*</span></label>
            <input type="file" name="file" id="file_input" class="form-control custom-input">
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-save px-4">حفظ المستند</button>
        </div>
    </form>
</div>
@endif

{{-- Two columns: Files | Links --}}
<div class="row g-3">

    {{-- Files column (right in RTL, first in DOM) --}}
    <div class="col-12 col-lg-6">
        <div class="card border border-light-subtle rounded-4 p-4 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-paperclip fs-4" style="color: #8A84AD;"></i>
                    <h5 class="task-page-title m-0">الملفات</h5>
                    <span class="badge rounded-circle text-dark bg-light border ms-1">{{ $fileDocs->count() }}</span>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-sm task-attach-btn" onclick="openAddDocumentForm('file')">
                        <i class="fa-solid fa-plus me-1"></i> إضافة ملف
                    </button>
                @endif
            </div>

            @if($fileDocs->isEmpty())
                <div class="text-center py-4">
                    <i class="fa-regular fa-folder-open mb-2" style="font-size: 32px; color: #D0CBE3;"></i>
                    <p class="text-muted small mb-0">لا توجد ملفات بعد.</p>
                </div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($fileDocs as $doc)
                        <div class="task-existing-attachment doc-item"
                             data-type="file"
                             data-title="{{ $doc->title }}">
                            <div class="file-info">
                                <i class="fa-solid fa-paperclip file-icon"></i>
                                <a class="file-name" href="{{ Storage::url($doc->file_path) }}" target="_blank" rel="noopener">{{ $doc->title }}</a>
                                <span class="text-muted small ms-1" style="font-size: 11px;">
                                    — {{ $doc->added_by_name ?? 'غير معروف' }} · {{ $doc->created_at->locale('ar')->translatedFormat('d F Y, h:i a') }}
                                </span>
                            </div>
                            @if($canManage)
                                <button type="button" class="delete-btn" title="حذف الملف"
                                        onclick="openDeleteDocumentConfirm('{{ route('documents.destroy', $doc->project_document_id) }}', @js($doc->title))">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="text-muted small m-0 text-center py-3 d-none" id="filesNoResults">لا توجد نتائج مطابقة للبحث.</p>
            @endif
        </div>
    </div>

    {{-- Links column (left in RTL, second in DOM) --}}
    <div class="col-12 col-lg-6">
        <div class="card border border-light-subtle rounded-4 p-4 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-link fs-4" style="color: #8A84AD;"></i>
                    <h5 class="task-page-title m-0">الروابط</h5>
                    <span class="badge rounded-circle text-dark bg-light border ms-1">{{ $linkDocs->count() }}</span>
                </div>
                @if($canManage)
                    <button type="button" class="btn btn-sm task-attach-btn" onclick="openAddDocumentForm('link')">
                        <i class="fa-solid fa-plus me-1"></i> إضافة رابط
                    </button>
                @endif
            </div>

            @if($linkDocs->isEmpty())
                <div class="text-center py-4">
                    <i class="fa-solid fa-link mb-2" style="font-size: 32px; color: #D0CBE3;"></i>
                    <p class="text-muted small mb-0">لا توجد روابط بعد.</p>
                </div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($linkDocs as $doc)
                        <div class="task-existing-attachment doc-item"
                             data-type="link"
                             data-title="{{ $doc->title }}">
                            <div class="file-info">
                                <i class="fa-solid fa-link file-icon"></i>
                                <a class="file-name" href="{{ $doc->url }}" target="_blank" rel="noopener">{{ $doc->title }}</a>
                                <span class="text-muted small ms-1" style="font-size: 11px;">
                                    — {{ $doc->added_by_name ?? 'غير معروف' }} · {{ $doc->created_at->locale('ar')->translatedFormat('d F Y, h:i a') }}
                                </span>
                            </div>
                            @if($canManage)
                                <button type="button" class="delete-btn" title="حذف الرابط"
                                        onclick="openDeleteDocumentConfirm('{{ route('documents.destroy', $doc->project_document_id) }}', @js($doc->title))">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="text-muted small m-0 text-center py-3 d-none" id="linksNoResults">لا توجد نتائج مطابقة للبحث.</p>
            @endif
        </div>
    </div>
</div>

{{-- Delete confirm modal --}}
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="deleteDocumentForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center">
                    <p class="delete-text mb-4" id="deleteDocumentText">هل أنت متأكد من حذف هذا المستند؟</p>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-delete-cancel" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-delete-confirm">حذف</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleDocumentFields(type) {
        const urlField  = document.getElementById('url_field');
        const fileField = document.getElementById('file_field');
        const urlInput  = document.getElementById('url_input');
        const fileInput = document.getElementById('file_input');
        if (!urlField || !fileField) return;

        if (type === 'file') {
            urlField.classList.add('d-none');
            fileField.classList.remove('d-none');
            if (urlInput)  { urlInput.value = ''; urlInput.removeAttribute('required'); }
            if (fileInput) { fileInput.setAttribute('required', 'required'); }
        } else {
            urlField.classList.remove('d-none');
            fileField.classList.add('d-none');
            if (urlInput)  { urlInput.setAttribute('required', 'required'); }
            if (fileInput) { fileInput.value = ''; fileInput.removeAttribute('required'); }
        }
    }

    // Opens the shared add form and pre-selects the type for the column it was called from.
    function openAddDocumentForm(type) {
        const form = document.getElementById('addDocumentForm');
        if (!form) return;

        const typeSelect = document.getElementById('doc_type');
        if (typeSelect) typeSelect.value = type;
        toggleDocumentFields(type);

        let collapse = bootstrap.Collapse.getInstance(form);
        if (!collapse) collapse = new bootstrap.Collapse(form, { toggle: false });
        collapse.show();

        setTimeout(function () {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 200);
    }

    function openDeleteDocumentConfirm(deleteUrl, title) {
        const form   = document.getElementById('deleteDocumentForm');
        const textEl = document.getElementById('deleteDocumentText');
        if (form && deleteUrl) form.action = deleteUrl;
        if (textEl) textEl.innerText = 'هل تريد حذف المستند "' + title + '"؟';

        const modalEl = document.getElementById('deleteDocumentModal');
        if (modalEl) {
            let instance = bootstrap.Modal.getInstance(modalEl);
            if (!instance) instance = new bootstrap.Modal(modalEl);
            instance.show();
        }
    }

    // Search: hides non-matching items in both columns and toggles "no results" placeholders.
    function filterDocuments() {
        const term = (document.getElementById('docSearchInput').value || '').trim().toLowerCase();
        let filesVisible = 0;
        let linksVisible = 0;

        document.querySelectorAll('.doc-item').forEach(function (row) {
            const title = (row.getAttribute('data-title') || '').toLowerCase();
            const type  = row.getAttribute('data-type');
            const matches = !term || title.includes(term);

            if (matches) {
                row.style.removeProperty('display');
                if (type === 'file') filesVisible++; else linksVisible++;
            } else {
                row.style.setProperty('display', 'none', 'important');
            }
        });

        const filesNoRes = document.getElementById('filesNoResults');
        const linksNoRes = document.getElementById('linksNoResults');
        if (filesNoRes) filesNoRes.classList.toggle('d-none', filesVisible > 0 || !term);
        if (linksNoRes) linksNoRes.classList.toggle('d-none', linksVisible > 0 || !term);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('doc_type')) {
            toggleDocumentFields('link');
        }
        document.getElementById('docSearchInput')?.addEventListener('input', filterDocuments);
    });
</script>
@endsection