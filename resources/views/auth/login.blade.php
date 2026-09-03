@extends('layouts.auth')
@section('title', 'تسجيل الدخول - نظرة الحلول المستقبل')
@section('body-class', 'login-body d-flex align-items-center justify-content-center min-vh-100')

@section('content')
<main class="login-wrapper text-center w-100 px-3">
    <!-- شعار الشركة العلوي -->
    <div class="logo-container mb-4">
        <img alt="Future Vision Solution Logo" class="login-logo img-fluid" src="{{ asset('FVSLogo.jpg') }}"/>
    </div>
    
    <!-- كارد صندوق تسجيل الدخول -->
    <div class="login-card bg-white mx-auto p-4 p-sm-5 rounded-4 shadow-sm">
        <h1 class="login-title mb-2">تسجيل الدخول</h1>
        <p class="login-subtitle mb-4">اهلا وسهلا بك في نظرة الحلول والمستقبل</p>

        <!-- Form تسجيل الدخول بعد التعديل -->
        <form id="loginForm" action="{{ route('login') }}" method="POST">
            @csrf

            <!-- اسم المستخدم / البريد الإلكتروني -->
            <div class="text-start text-rtl mb-3">
                <label class="form-label custom-label" for="usernameInput">اسم المستخدم</label>
                <input class="form-control custom-input text-start @error('email') is-invalid @enderror" 
                       name="email" 
                       value="{{ old('email') }}" 
                       dir="ltr" 
                       id="usernameInput" 
                       pattern="^[a-zA-Z0-9._%+-]+@fvs\.com\.sa$" 
                       placeholder="name@fvs.com.sa" 
                       required 
                       type="email"/>
                @error('email')
                    <small class="error-message text-danger mt-1 d-block">{{ $message }}</small>
                @enderror
            </div>

            <!-- كلمة المرور -->
            <div class="text-start text-rtl mb-3">
                <label class="form-label custom-label" for="passwordInput">كلمة المرور</label>
                <input class="form-control custom-input text-start @error('password') is-invalid @enderror" 
                       name="password" 
                       dir="ltr" 
                       id="passwordInput" 
                       placeholder="*******" 
                       required 
                       type="password"/>
                @error('password')
                    <small class="error-message text-danger mt-1 d-block">{{ $message }}</small>
                @enderror
            </div>

            <!-- خيار تذكرني ورابط نسيت كلمة المرور -->
            <div class="d-flex justify-content-between align-items-center mb-4 text-rtl">
                <div class="form-check">
                    <input class="form-check-input" name="remember" id="rememberMe" type="checkbox"/>
                    <label class="form-check-label custom-label" for="rememberMe" style="font-size: 14px; cursor: pointer;">تذكرني</label>
                </div>
                <div>
                    <a class="forgot-password-link" href="{{ route('password.request') }}">هل نسيت كلمة المرور ؟</a>
                </div>
            </div>

            <!-- زر تسجيل الدخول -->
            <button class="btn btn-login w-100 py-2 fw-semibold" type="submit">تسجيل الدخول</button>
        </form>
    </div>
</main>
@endsection