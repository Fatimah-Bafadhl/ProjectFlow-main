<!DOCTYPE html>
<html id="htmlRoot" lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظرة الحلول المستقبل')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link id="bootstrapCSS" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="{{ asset('Style.css') }}">
    @stack('styles')
</head>
<body class="@yield('body-class', 'login-body d-flex align-items-center justify-content-center min-vh-100')">
    @yield('content')

    <script>
        window.FVS_ROUTES = {
            dashboard: @json(route('dashboard'))
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('main.js') }}"></script>
    @stack('scripts')
</body>
</html>
