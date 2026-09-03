@extends('layouts.app')
@section('title', 'تفاصيل المهمة - ' . $task->task_title)
@section('content-class', 'p-4 flex-grow-1')
@section('topbar-extra-class', 'border-bottom')

@section('content')

@php
    $user = auth()->user();
    $isClient = $user && $user->isClient();
@endphp
<!-- Navigation / Breadcrumb قابل للنقر -->
<div class="mb-2 text-start">
    <a class="text-muted small text-decoration-none me-1 hover-link" href="{{ route('tasks.index') }}">المهام</a>
    <span class="text-muted small me-1">&lt;</span>
    <span class="fw-bold small text-dark">{{ $task->task_title }}</span>
</div>

<!-- عنوان الصفحة -->
<h3 class="task-page-title">المهام</h3>

<!-- Task Details Main Header Card -->
<div class="card custom-task-card p-4 mb-4 bg-white">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="text-start">
            <div class="mb-2">
                <h3 class="project-card-title m-0">{{ $task->task_title }}</h3>
            </div>
            <p class="text-muted small m-0">{{ $task->task_description ?? 'لا يوجد وصف للمهمة.' }}</p>
        </div>
        <div class="d-inline-flex align-items-center gap-2 p-0 border-0 bg-transparent flex-shrink-0">
            <span class="fw-normal text-secondary small">{{ $task->status }}</span>
            <i class="fa-regular fa-id-badge text-muted" style="font-size: 1rem;"></i>
        </div>
    </div>

    <div class="d-flex align-content-center justify-content-between text-muted small pt-2 border-top-0">
        <div class="text-start text-nowrap">
            <span>
                تاريخ البداية: {{ $task->start_task ? \Carbon\Carbon::parse($task->start_task)->locale('ar')->translatedFormat('d F Y') : 'غير محدد' }} 
                ← تاريخ الانتهاء: {{ $task->end_task ? \Carbon\Carbon::parse($task->end_task)->locale('ar')->translatedFormat('d F Y') : 'غير محدد' }}
            </span>
        </div>
        <div class="text-center px-4">
            <span><strong>مُسند:</strong> {{ optional($task->assignedUser)->name ?? 'غير مسند' }}</span>
        </div>
        <div></div>
    </div>
</div>

<!-- Comments Box Container -->
<div class="card border border-light-subtle rounded-4 p-4 shadow-sm">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="fa-regular fa-comment fs-4" style="color: #8A84AD;"></i>
        <h5 class="task-page-title">التعليقات</h5>
        <span class="badge rounded-circle text-dark bg-light border ms-1" id="commentsCountBadge">
            {{ isset($task->comments) ? $task->comments->count() : 0 }}
        </span>
    </div>

    <!-- Scrollable Comments Body -->
    <div class="comments-scroll-area pe-2 mb-4" id="commentsContainer" style="max-height: 380px; overflow-y: auto;">
        @php
            $currentUserId = auth()->id();
        @endphp

       @php
    $visibleComments = $isClient
        ? $task->comments->where('visible_to_client', true)->values()
        : $task->comments;
@endphp
@forelse($visibleComments as $index => $comment)
            @php
                $commentUser = $comment->user;
                $userName = $commentUser ? ($commentUser->name ?? $commentUser->username ?? 'مستخدم') : 'مستخدم';
                $initials = mb_substr($userName, 0, 2);

                $commentEmail = $commentUser->email ?? '';
                
                if ($commentUser && ($commentUser->isClient() || \App\Models\Client::where('email', $commentEmail)->exists())) {
    $roleLabel = 'عميل';
} elseif ($commentUser && $commentUser->isEmployee()) {
    $roleLabel = 'موظف';
} else {
    $roleLabel = 'مدير';
}

                $isOwner = ($currentUserId && $comment->user_id === $currentUserId);

               $previousComment = $index > 0 ? $visibleComments[$index - 1] : null;
                $prevUser = $previousComment?->user;
                $prevUserId = $prevUser?->id ?? null;
                
                $isSameUser = false;
                if ($previousComment) {
                    if ($currentUserId && $prevUserId) {
                        $isSameUser = ($currentUserId === $prevUserId);
                    } else {
                        $prevUserName = $prevUser ? ($prevUser->name ?? $prevUser->username ?? 'مستخدم') : 'مستخدم';
                        $isSameUser = ($userName === $prevUserName);
                    }
                }
            @endphp

            @if($isSameUser)
                <div class="comment-item ms-5 ps-2 mb-3 position-relative" data-comment-text="{{ $comment->comment_text }}" data-attachment="{{ $comment->attachment ?? '' }}">
                   <div class="d-flex justify-content-between align-items-center mb-1">
                       <div class="text-muted extra-small">
                            {{ $comment->created_at->timezone('Asia/Riyadh')->locale('ar')->translatedFormat('h:i a') }}
                       </div>
                       @if($isOwner)
                            <div class="d-flex align-items-center gap-3">
                                <button type="button" class="btn btn-sm btn-link p-0 text-muted" style="color: #8A84AD !important;" onclick="openEditCommentModal(this, '{{ route('comments.update', $comment->id ?? $comment->comment_id) }}')" title="تعديل">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-link p-0 text-muted" style="color: #8A84AD !important;" onclick="openDeleteCommentModal(this, '{{ route('comments.destroy', $comment->id ?? $comment->comment_id) }}')" title="حذف">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                       @endif
                   </div>

                    @if($comment->comment_text)
                        <p class="mb-2 text-dark small">{{ $comment->comment_text }}</p>
                    @endif

                    @if($comment->attachment)
                        @php
                            $fileName = basename($comment->attachment);
                            $extension = pathinfo($comment->attachment, PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                        @endphp
                        
                        @if($isImage)
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $comment->attachment) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $comment->attachment) }}" alt="attachment" class="img-thumbnail rounded-3" style="max-height: 120px;">
                                </a>
                            </div>
                        @else
                            <div class="p-2 border rounded-3 bg-light d-inline-flex align-items-center gap-2 mt-2">
                                <i class="fa-regular fa-file text-muted"></i>
                                <a href="{{ asset('storage/' . $comment->attachment) }}" target="_blank" class="extra-small text-muted text-decoration-none">
                                    {{ $fileName }}
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            @else
                <div class="d-flex gap-3 mb-3 comment-item {{ $index > 0 ? 'border-top pt-3' : '' }}" data-comment-text="{{ $comment->comment_text }}" data-attachment="{{ $comment->attachment ?? '' }}">
                    <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 42px; height: 42px; background-color: #8A84AD; flex-shrink: 0;">
                        {{ $initials }}
                    </div>
                    <div class="w-100">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold comment-username">{{ $userName }}</span>
                                <span class="badge" style="background-color: #8A84AD;">{{ $roleLabel }}</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted extra-small">
                                {{ $comment->created_at->timezone('Asia/Riyadh')->locale('ar')->translatedFormat('d F Y, h:i a') }}
                            </div>
                            @if($isOwner)
                                <div class="d-flex align-items-center gap-3">
                                    <button type="button" class="btn btn-sm btn-link p-0 text-muted" style="color: #8A84AD !important;" onclick="openEditCommentModal(this, '{{ route('comments.update', $comment->id ?? $comment->comment_id) }}')" title="تعديل">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-muted" style="color: #8A84AD !important;" onclick="openDeleteCommentModal(this, '{{ route('comments.destroy', $comment->id ?? $comment->comment_id) }}')" title="حذف">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                        
                        @if($comment->comment_text)
                            <p class="mb-2 text-dark small">{{ $comment->comment_text }}</p>
                        @endif

                        @if($comment->attachment)
                            @php
                                $fileName = basename($comment->attachment);
                                $extension = pathinfo($comment->attachment, PATHINFO_EXTENSION);
                                $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                            @endphp
                            
                            @if($isImage)
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $comment->attachment) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $comment->attachment) }}" alt="attachment" class="img-thumbnail rounded-3" style="max-height: 120px;">
                                    </a>
                                </div>
                            @else
                                <div class="p-2 border rounded-3 bg-light d-inline-flex align-items-center gap-2 mt-2">
                                    <i class="fa-regular fa-file text-muted"></i>
                                    <a href="{{ asset('storage/' . $comment->attachment) }}" target="_blank" class="extra-small text-muted text-decoration-none">
                                        {{ $fileName }}
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        @empty
            <p class="text-muted fs-6 m-0 text-center" id="emptyStateMsg">لا توجد تعليقات بعد، اكتب أول تعليق.</p>
        @endforelse
    </div>
@if(!$isClient)
    <!-- Add Comment Form Area -->
    <form id="commentForm" action="{{ route('comments.store', $task->task_id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input class="d-none" id="attachmentInput" name="attachment" type="file" onchange="showFileName(this, 'filePreview', 'fileNameText')" />
        <input class="d-none" id="imageInput" name="image" type="file" accept="image/*" onchange="showFileName(this, 'imagePreview', 'imageNameText')" />
        
        <div id="filePreview" class="mb-2 d-none align-items-center gap-2 p-2 border rounded-3 bg-light">
            <i class="fa-solid fa-paperclip text-muted"></i>
            <span id="fileNameText" class="small text-dark fw-bold"></span>
            <button type="button" class="btn-close ms-auto btn-sm" onclick="removeFile('attachmentInput', 'filePreview')"></button>
        </div>

        <div id="imagePreview" class="mb-2 d-none align-items-center gap-2 p-2 border rounded-3 bg-light">
            <i class="fa-regular fa-image text-muted"></i>
            <span id="imageNameText" class="small text-dark fw-bold"></span>
            <button type="button" class="btn-close ms-auto btn-sm" onclick="removeFile('imageInput', 'imagePreview')"></button>
        </div>

        <div class="position-relative mb-3">
            <input class="form-control rounded-pill pe-4 ps-5 py-2 custom-input" id="commentTextInput" name="comment_text" placeholder="اكتب تعليق...... أو أرفق صور/ملفات" type="text"/>
            <button class="btn btn-link position-absolute start-0 top-50 translate-middle-y text-decoration-none border-0 p-0 ms-3" style="color: #8A84AD;" type="submit">
                <i class="fa-regular fa-paper-plane fs-5"></i>
            </button>
        </div>

        <div class="d-flex gap-2">
            <button class="btn rounded-pill text-white px-3 py-1 small" onclick="document.getElementById('attachmentInput').click();" style="background-color: #8A84AD;" type="button">
                <i class="fa-solid fa-paperclip me-1"></i> تحميل ملف
            </button>
            <button class="btn rounded-pill text-white px-3 py-1 small" onclick="document.getElementById('imageInput').click();" style="background-color: #8A84AD;" type="button">
                <i class="fa-regular fa-image me-1"></i> صورة
            </button>
        </div>
    </form>
    @endif
</div>

<!-- Modal تعديل التعليق (محدث ليدعم التعليقات بملفات أو بدون ملفات) -->
<div class="modal fade" id="editCommentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="editCommentForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body text-start">
                    <label class="custom-label mb-2">تعديل التعليق</label>
                    <textarea class="form-control custom-input mb-3" id="editCommentInput" name="comment_text" rows="3" required></textarea>
                    
                    <!-- حقل الملف المخفي -->
                    <input class="d-none" id="editAttachmentInput" name="attachment" type="file" onchange="showEditPreview(this)" />
                    <input type="hidden" id="removeAttachmentFlag" name="remove_attachment" value="0">

                    <!-- قسم إدارة المرفقات في التعديل -->
                    <div class="mb-3">
                        <label class="custom-label mb-2" id="editAttachmentLabel">المرفق:</label>
                        
                        <!-- معاينة الملف الحالي -->
                        <div id="editFilePreviewBox" class="d-none align-items-center gap-2 p-2 border rounded-3 bg-light mb-2" 
                             style="cursor: pointer;" onclick="document.getElementById('editAttachmentInput').click()" title="اضغط لتغيير الملف">
                            <i id="editPreviewIcon" class="fa-regular fa-file text-muted"></i>
                            <span id="editFileNameText" class="small text-dark fw-bold"></span>
                        </div>

                        <!-- معاينة الصورة الحالية -->
                        <div id="editImagePreviewBox" class="d-none mb-2" 
                             style="cursor: pointer;" onclick="document.getElementById('editAttachmentInput').click()" title="اضغط لتغيير الصورة">
                            <div class="position-relative d-inline-block">
                                <img id="editImageThumbnail" src="" alt="attachment" class="img-thumbnail rounded-3" style="max-height: 120px;">
                                <div class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-50 text-white text-center small p-1">
                                    اضغط لتغيير الصورة
                                </div>
                            </div>
                        </div>

                        <!-- زر إضافة ملف إذا لم يكن هناك مرفق سابق -->
                        <div id="noAttachmentAddBox">
                            <button type="button" class="btn rounded-pill w-100 py-2 shadow-none d-flex align-items-center justify-content-center" 
                                    style="background-color: #ffffff !important; border: 1px solid #ced4da; color: #6c757d !important;"
                                    onclick="document.getElementById('editAttachmentInput').click()">
                                <i class="fa-solid fa-paperclip me-2 text-secondary" style="font-size: 0.85rem;"></i>
                                <span id="addOrChangeBtnText" class="small" style="color: #6c757d;">إضافة ملف أو صورة مع التعديل</span>
                            </button>
                        </div>

                        <!-- زر إزالة المرفق الحالي -->
                        <div id="removeAttachmentActionBox" class="mt-1 d-none">
                            <button type="button" class="btn btn-link text-danger btn-sm p-0 text-decoration-none small" onclick="removeCurrentAttachment()">
                                <i class="fa-regular fa-trash-can me-1"></i> إزالة المرفق الحالي
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <button type="submit" class="btn btn-save btn-sm px-3 py-1">حفظ التعديلات</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تأكيد حذف التعليق -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="deleteCommentForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center">
                    <p class="delete-text mb-4">هل أنت متأكد من حذف هذا التعليق؟</p>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-delete-cancel" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-delete-confirm">حذف</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    function openEditCommentModal(button, updateUrl) {
        let item = button.closest('.comment-item');
        let text = item.getAttribute('data-comment-text');
        
        document.getElementById('editCommentInput').value = text;
        document.getElementById('editCommentForm').action = updateUrl;
        
        let modal = new bootstrap.Modal(document.getElementById('editCommentModal'));
        modal.show();
    }

    function openDeleteCommentModal(button, deleteUrl) {
        document.getElementById('deleteCommentForm').action = deleteUrl;
        let modal = new bootstrap.Modal(document.getElementById('deleteCommentModal'));
        modal.show();
    }

    function showFileName(input, previewId, textId) {
        if (input.files && input.files[0]) {
            document.getElementById(textId).innerText = input.files[0].name;
            document.getElementById(previewId).classList.remove('d-none');
            document.getElementById(previewId).classList.add('d-flex');
        }
    }

    function removeFile(inputId, previewId) {
        document.getElementById(inputId).value = '';
        document.getElementById(previewId).classList.add('d-none');
        document.getElementById(previewId).classList.remove('d-flex');
    }
</script>
@endpush
@endsection