<aside class="navigation-sidebar d-flex flex-column justify-content-between p-3 border-end">
    <div>
        <div class="sidebar-header text-center mb-3">
           <img src="{{ asset('FVSLogo.jpg') }}" alt="لوجو الشركة" class="img-fluid">
        </div>

        <nav class="nav flex-column gap-2">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                <i class="fa-solid fa-house icon-style"></i>
                <span>الصفحة الرئيسية</span>
            </a>
           
                        <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                <i class="fa-solid fa-chart-line icon-style"></i>
                <span>المشاريع</span>
            </a>

            @if (auth()->check() && auth()->user()->isClient())
            <a href="{{ route('communications.index') }}" class="nav-link {{ request()->routeIs('communications.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                <i class="fa-regular fa-comments icon-style"></i>
                <span>التواصل</span>
            </a>
            @endif

            @if (auth()->check() && !auth()->user()->isClient())
            <a href="{{ route('tasks.index') }}" class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                <i class="fa-regular fa-file-lines icon-style"></i>
                <span>المهام</span>
            </a>
            @endif

            @if (auth()->check() && auth()->user()->isAdmin())
            <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                <i class="fa-solid fa-users icon-style"></i>
                <span>العملاء</span>
            </a>
            @endif


@if (auth()->check() && auth()->user()->isAdmin())
           <a href="{{ route('employees.index') }}" class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
    <i class="bi bi-person-workspace icon-style"></i>
    <span>الموظفين</span>
</a>
@endif
                        @if (auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                    <i class="fa-solid fa-user-shield icon-style"></i>
                    <span>إدارة المستخدمين</span>
                </a>
                <a href="{{ route('trash.index') }}" class="nav-link {{ request()->routeIs('trash.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                    <i class="fa-solid fa-trash-can icon-style"></i>
                    <span>المحذوفات</span>
                </a>
            @endif

            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }} d-flex align-items-center justify-content-start gap-3">
                <i class="fa-solid fa-gear icon-style"></i>
                <span>الإعدادات</span>
            </a>
        </nav>
    </div>

    <div class="mt-auto pt-3 text-center">
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-logout w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>تسجيل خروج</span>
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-logout w-100 text-decoration-none d-flex align-items-center justify-content-center gap-2">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>تسجيل الدخول</span>
            </a>
        @endauth
    </div>
</aside>
