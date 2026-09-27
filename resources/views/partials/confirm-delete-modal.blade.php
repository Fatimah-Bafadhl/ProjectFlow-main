{{-- Shared soft-delete confirmation modal (M6).
     Any delete button anywhere in the app can trigger this instead of
     its own modal. Just give the button:
       data-delete-url="{{ route('....destroy', $x) }}"
       data-delete-name="اسم العنصر"
       data-delete-extra="نص اختياري إضافي، مثال: عدد المهام المرتبطة"
       onclick="openConfirmDeleteModal(this)"
--}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="delete-text mb-4" id="confirmDeleteModalText">سيتم نقله إلى المحذوفات ويمكن استعادته.</p>
                <form id="confirmDeleteForm" method="POST" action="">
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