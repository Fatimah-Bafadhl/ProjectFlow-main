<!-- Shared restore-confirmation modal (mirrors confirm-delete-modal) -->
<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal p-4 text-center">
            <div class="modal-body p-0">
                <p class="mb-4" id="confirmRestoreModalText"></p>
                <form id="confirmRestoreForm" method="POST" action="">
                    @csrf
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-save">تأكيد الاستعادة</button>
                        <button type="button" class="btn btn-delete-cancel" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>