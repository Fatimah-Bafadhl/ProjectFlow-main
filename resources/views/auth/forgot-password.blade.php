@extends('layouts.auth')
@section('title', 'إعادة تعيين كلمة المرور - نظرة الحلول المستقبل')
@section('body-class', 'd-flex align-items-center justify-content-center min-vh-100')

@section('content')
<main class="login-wrapper text-center w-100 px-3">
    <div class="logo-container mb-4">
        <img alt="Future Vision Solution Logo" class="login-logo img-fluid" src="{{ asset('FVSLogo.jpg') }}"/>
    </div>
    
    <div class="login-card bg-white mx-auto p-4 p-sm-5 rounded-4 shadow-sm">
        
        <div class="text-start mb-3">
            <a class="forgot-password-link text-decoration-none d-inline-flex align-items-center gap-1" href="{{ route('login') }}" style="font-size: 13px;">
                <svg class="bi bi-chevron-right" fill="currentColor" height="14" viewbox="0 0 16 16" width="14" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" fill-rule="evenodd"></path>
                </svg>
                العودة لتسجيل الدخول
            </a>
        </div>

        <div id="emailSection">
            <h4 class="login-title mb-2 fs-6">إعادة تعيين كلمة المرور</h4>
            <p class="login-subtitle mb-4">أدخل بريدك الإلكتروني للتحقق من وجوده في النظام</p>
            
            <form id="checkEmailForm" onsubmit="return false;">
                <div class="text-start text-rtl mb-3">
                    <input class="form-control custom-input text-center" dir="ltr" id="emailInput" name="email" placeholder="البريد الإلكتروني" type="text"/>
                    <small class="error-message text-danger d-block text-start d-none mt-1" id="emailError" style="font-size: 12px;"></small>
                </div>
                <button class="btn btn-login w-100 py-2 fw-semibold" id="checkEmailBtn" type="button" onclick="verifyEmailExists()">التالي</button>
            </form>
        </div>

        <div id="newPasswordSection" class="d-none">
            <h4 class="login-title mb-2 fs-6">إنشاء كلمة مرور جديدة</h4>
            <p class="login-subtitle mb-4">أدخل كلمة المرور الجديدة لحسابك</p>

            <form id="updatePasswordForm" onsubmit="return false;">
                <input type="hidden" id="verifiedEmail" name="email">

                <div class="text-start text-rtl mb-3">
                    <label class="form-label text-muted" style="font-size: 13px;">كلمة المرور الجديدة</label>
                    <input type="password" class="form-control custom-input" id="passwordInput" name="password" placeholder="8 خانات على الأقل">
                    <small class="error-message text-danger d-block text-start d-none mt-1" id="passwordError" style="font-size: 12px;"></small>
                </div>

                <div class="text-start text-rtl mb-4">
                    <label class="form-label text-muted" style="font-size: 13px;">تأكيد كلمة المرور</label>
                    <input type="password" class="form-control custom-input" id="passwordConfirmationInput" name="password_confirmation" placeholder="أعد إدخال كلمة المرور">
                </div>

                <button class="btn btn-login w-100 py-2 fw-semibold" id="updatePasswordBtn" type="button" onclick="submitNewPassword()">حفظ كلمة المرور</button>
            </form>
        </div>

        <div class="d-none text-center py-2" id="successState">
            <div class="success-icon-bg mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 56px; height: 56px; background-color: #E8F5E9;">
                <svg class="bi bi-check-lg" fill="#2E7D32" height="28" viewbox="0 0 16 16" width="28" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"></path>
                </svg>
            </div>
            <h4 class="fw-bold fs-6 mb-2">تم تغيير كلمة المرور بنجاح</h4>
            <p class="login-subtitle mb-0" style="font-size: 13px; line-height: 1.6;">
                جاري توجيهك إلى صفحة تسجيل الدخول...
            </p>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script>
    // دالة الخطوة الأولى: التحقق من الإيميل
    function verifyEmailExists() {
        const emailInput = document.getElementById('emailInput');
        const emailError = document.getElementById('emailError');
        const checkBtn = document.getElementById('checkEmailBtn');

        emailError.classList.add('d-none');
        checkBtn.disabled = true;
        checkBtn.innerText = 'جاري التحقق...';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/forgot-password/process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: emailInput.value })
        })
        .then(async response => {
            const data = await response.json();
            checkBtn.disabled = false;
            checkBtn.innerText = 'التالي';

            if (response.ok && data.status === 'success') {
                // حفظ الإيميل في الحقل المخفي للخطوة التالية
                document.getElementById('verifiedEmail').value = emailInput.value;

                // إخفاء قسم الإيميل وإظهار قسم كلمة المرور الجديدة في نفس الصفحة
                document.getElementById('emailSection').classList.add('d-none');
                document.getElementById('newPasswordSection').classList.remove('d-none');
            } else {
                if (data.errors && data.errors.email) {
                    emailError.innerText = data.errors.email[0];
                    emailError.classList.remove('d-none');
                }
            }
        })
        .catch(error => {
            checkBtn.disabled = false;
            checkBtn.innerText = 'التالي';
            console.error('Error:', error);
        });
    }

    // دالة الخطوة الثانية: إرسال كلمة المرور الجديدة وتحديثها
    function submitNewPassword() {
        const email = document.getElementById('verifiedEmail').value;
        const password = document.getElementById('passwordInput').value;
        const passwordConfirmation = document.getElementById('passwordConfirmationInput').value;
        const passwordError = document.getElementById('passwordError');
        const updateBtn = document.getElementById('updatePasswordBtn');

        passwordError.classList.add('d-none');
        updateBtn.disabled = true;
        updateBtn.innerText = 'جاري الحفظ...';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/reset-password/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                password: password,
                password_confirmation: passwordConfirmation
            })
        })
        .then(async response => {
            const data = await response.json();
            updateBtn.disabled = false;
            updateBtn.innerText = 'حفظ كلمة المرور';

            if (response.ok) {
                // إخفاء قسم كلمة المرور وإظهار رسالة النجاح
                document.getElementById('newPasswordSection').classList.add('d-none');
                document.getElementById('successState').classList.remove('d-none');

                // التوجيه التلقائي لصفحة تسجيل الدخول بعد ثانيتين
                setTimeout(() => {
                    window.location.href = "{{ route('login') }}";
                }, 2000);
            } else {
                if (data.errors && data.errors.password) {
                    passwordError.innerText = data.errors.password[0];
                    passwordError.classList.remove('d-none');
                }
            }
        })
        .catch(error => {
            updateBtn.disabled = false;
            updateBtn.innerText = 'حفظ كلمة المرور';
            console.error('Error:', error);
        });
    }
</script>
@endpush