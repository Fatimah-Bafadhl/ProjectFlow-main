<!DOCTYPE html>
<html id="htmlRoot" lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظرة الحلول المستقبل')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link id="bootstrapCSS" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- ربط مباشر لـ Style.css -->
    <link rel="stylesheet" href="{{ asset('Style.css') }}">
    @stack('styles')
</head>
<body>
    <div class="d-flex min-vh-100">
        @include('partials.sidebar')

        <main class="flex-grow-1 d-flex flex-column bg-white">
            @include('partials.topbar')

            <section class="@yield('content-class', 'p-4 flex-grow-1')">
                @yield('content')
            </section>
        </main>
    </div>

    @stack('modals')

   <!-- نافذة النجاح المنبثقة الموحدة -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-sm text-center p-4" style="background-color: #ffffff;">
            <div class="mb-2">
                <div class="success-icon-wrapper mx-auto d-flex align-items-center justify-content-center rounded-circle">
                    <i class="fa-solid fa-check text-white"></i>
                </div>
            </div>
            <p class="delete-text my-3" id="successModalText"></p>
            <div class="d-flex justify-content-center">
                <button type="button" class="btn btn-status-ok" data-bs-dismiss="modal">حسناً</button>
            </div>
        </div>
    </div>
</div>
    <script>
        window.FVS_ROUTES = {
            dashboard: @json(route('dashboard'))
        };

        // تمرير رسالة النجاح من لارافيل إلى الجافاسكريفت تلقائياً
        @if(session('success'))
            window.sessionSuccessMessage = @json(session('success'));
        @endif
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- ربط مباشر لـ main.js -->
    <script src="{{ asset('main.js') }}"></script>
    @stack('scripts')
</body>
</html>