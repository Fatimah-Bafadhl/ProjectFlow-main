@extends('layouts.app')
@section('title', 'الاعدادات')
@section('content-class', 'p-4 flex-grow-1')

@section('content')
<!-- عنوان الصفحة -->
<div class="mb-4" dir="rtl">
    <h2 class="task-page-title" data-i18n="settingsPageHeader">الاعدادات</h2>
    <p class="text-muted m-0" data-i18n="settingsPageSub" style="font-size: 14px;">إدارة تفضيلاتك وحسابك الشخصي</p>
</div>

<!-- الكارد الرئيسي للإعدادات -->
<div class="card custom-modal border p-3 mx-auto" style="max-width: 480px;" dir="rtl">
    <!-- 1. قسم الإشعارات الرئيسي -->
    <div class="d-flex align-items-center justify-content-between p-3 mb-3 border rounded-4" style="background-color: #FAF9FB;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: rgba(138, 132, 173, 0.12); color: #8A84AD;">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <h3 class="m-0 fw-bold" data-i18n="notificationsHeading" style="font-size: 15px; color: #000000;">الاشعارات</h3>
                <span class="text-muted" data-i18n="notificationsSub" style="font-size: 12px;">إشعارات البريد الإلكتروني</span>
            </div>
        </div>
    </div>

    <!-- 2. زر تفعيل إشعارات البريد -->
    <div class="d-flex align-items-center justify-content-between p-3 mb-3 border rounded-4" style="background-color: #FAF9FB;">
        <span class="fw-semibold" data-i18n="toggleEmailNotif" style="font-size: 14px; color: #000000;">تفعيل إشعارات البريد</span>
        <div class="form-check form-switch m-0">
            <input class="form-check-input custom-toggle" id="emailNotifToggle" role="switch" style="cursor: pointer; width: 45px; height: 22px;" type="checkbox" {{ auth()->user()->email_notifications ? 'checked' : '' }} />
        </div>
    </div>

    <!-- 3. قسم اللغة -->
    <div class="d-flex align-items-center justify-content-between p-3 mb-3 border rounded-4" style="background-color: #FAF9FB;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: rgba(138, 132, 173, 0.12); color: #8A84AD;">
                <i class="fa-solid fa-globe"></i>
            </div>
            <div>
                <h3 class="m-0 fw-bold" data-i18n="languagesHeading" style="font-size: 15px; color: #000000;">اللغات</h3>
                <span class="text-muted" data-i18n="languagesSub" style="font-size: 12px;">لغة الواجهة</span>
            </div>
        </div>
        <span class="text-secondary" id="currentLangBadge" style="font-size: 13px;">العربية</span>
    </div>

    <div class="d-flex flex-column gap-2 mb-4">
        <button class="btn text-start w-100 py-2 px-3 rounded-3 fw-semibold border" id="langArBtn" onclick="changeLanguage('ar')" style="font-size: 14px;" type="button">العربية</button>
        <button class="btn text-start w-100 py-2 px-3 rounded-3 fw-semibold border" id="langEnBtn" onclick="changeLanguage('en')" style="font-size: 14px;" type="button">English</button>
    </div>

    <!-- 4. قسم تغيير كلمة المرور -->
    <div class="d-flex align-items-center justify-content-between p-3 mb-3 border rounded-4" style="background-color: #FAF9FB;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: rgba(138, 132, 173, 0.12); color: #8A84AD;">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <h3 class="m-0 fw-bold" data-i18n="changePassHeading" style="font-size: 15px; color: #000000;">تغيير كلمة المرور</h3>
                <span class="text-muted" data-i18n="changePassSub" style="font-size: 12px;">تحديث بيانات تسجيل الدخول الخاصة بك</span>
            </div>
        </div>
    </div>

    <!-- نموذج تغيير كلمة المرور -->
    <form id="passwordChangeForm">
        @csrf
        <div class="d-flex flex-column gap-3">
            <div>
                <label class="form-label custom-label mb-1" data-i18n="currentPassLabel">كلمة المرور الحالية</label>
                <input class="form-control custom-input" id="currentPassInput" name="current_password" required type="password"/>
            </div>
            <div>
                <label class="form-label custom-label mb-1" data-i18n="newPassLabel">كلمة المرور الجديدة</label>
                <input class="form-control custom-input" id="newPassInput" name="new_password" required type="password"/>
            </div>
            <div>
                <label class="form-label custom-label mb-1" data-i18n="confirmPassLabel">تأكيد كلمة المرور الجديدة</label>
                <input class="form-control custom-input" id="confirmPassInput" name="new_password_confirmation" required type="password"/>
            </div>
        </div>
        <div class="mt-4 text-center">
            <button class="btn w-100 py-2 rounded-pill fw-semibold shadow-none" data-i18n="updatePassBtn" style="background-color: #8A84AD !important; color: #FFFFFF !important; font-size: 15px;" type="submit">
                تحديث كلمة المرور
            </button>
        </div>
    </form>
</div>
@endsection

@push('modals')
<div aria-hidden="true" class="modal fade" id="statusMessageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal text-center p-4">
            <div class="mb-3">
                <i class="fa-solid fa-circle-check status-success-icon text-success fs-1"></i>
            </div>
            <p class="delete-text mb-4 fw-bold" id="statusModalMessage">تم تحديث كلمة المرور بنجاح</p>
            <div class="d-flex justify-content-center">
                <button class="btn btn-status-ok px-4 py-2 rounded-pill text-white" style="background-color: #8A84AD;" data-bs-dismiss="modal" type="button">حسناً</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<!-- تمرير الراوتس المتغيرة كـ Global Variables لاستخدامها في الملف الخارجي -->
<script>
    window.settingsRoutes = {
        notifications: "{{ route('settings.notifications') }}",
        passwordUpdate: "{{ route('settings.password.update') }}" // ضع مسار راوت تحديث الباسورد لديك
    };
</script>
<!-- استدعاء ملف الجافاسكريفت الخارجي -->
<script src="{{ asset('js/main.js') }}"></script>
@endpush
