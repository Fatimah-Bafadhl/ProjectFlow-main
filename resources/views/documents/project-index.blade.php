@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">مستندات المشروع: {{ $project->project_name }}</h4>
        <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-outline-secondary">
            العودة للمشروع
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(auth()->user()->isAdmin() || auth()->user()->isManager())
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="mb-3">إضافة مستند جديد</h6>
            <form action="{{ route('documents.store', $project->project_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">النوع</label>
                        <select name="type" id="doc_type" class="form-select" required>
                            <option value="link">رابط</option>
                            <option value="file">ملف</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">العنوان</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-4" id="url_field">
                        <label class="form-label">الرابط (Git / Repo / أي رابط)</label>
                        <input type="url" name="url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="col-md-4 d-none" id="file_field">
                        <label class="form-label">الملف</label>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">إضافة</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($documents->isEmpty())
                <p class="text-muted mb-0">لا توجد مستندات بعد.</p>
            @else
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>النوع</th>
                            <th>الرابط / الملف</th>
                            <th>أضيف بواسطة</th>
                            <th>التاريخ</th>
                            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                            <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->type === 'link' ? 'رابط' : 'ملف' }}</td>
                            <td>
                                @if($doc->type === 'link')
                                    <a href="{{ $doc->url }}" target="_blank">{{ $doc->url }}</a>
                                @else
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank">{{ $doc->original_filename }}</a>
                                @endif
                            </td>
                            <td>{{ $doc->added_by_name }}</td>
                            <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                            <td>
                                <form action="{{ route('documents.destroy', $doc->project_document_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>

<script>
document.getElementById('doc_type')?.addEventListener('change', function () {
    const isLink = this.value === 'link';
    document.getElementById('url_field').classList.toggle('d-none', !isLink);
    document.getElementById('file_field').classList.toggle('d-none', isLink);
});
</script>
@endsection