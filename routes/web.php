<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\UserController;



Route::middleware('guest')->group(function () {
    
    // 1. عرض صفحة إدخال البريد الإلكتروني الأساسية
    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');

    // 2. معالجة وفحص البريد الإلكتروني (المسار الذي كان ناقصاً وهو المسؤول عن تفعيل زر "التالي")
    Route::post('forgot-password/process', [ForgotPasswordController::class, 'processDirectReset']);

    // 3. عرض صفحة كلمة المرور الجديدة (التي أرسلتها أنت)
    Route::get('reset-password-page', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.page');

    // 4. استقبال بيانات كلمة المرور الجديدة وتحديثها في القاعدة (التي أرسلتها أنت)
    Route::post('reset-password/update', [ForgotPasswordController::class, 'updatePassword'])->name('password.update.action');
    
});


// 1. الصفحة الرئيسية
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// تحويل روابط لوحة التحكم القديمة لتجنب خطأ 404
Route::redirect('/admin/dashboard', '/dashboard');
Route::redirect('/admin', '/dashboard');

// 2. مسارات تسجيل الدخول (للصيوف فقط)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('loginUser');
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
});

// 3. المسارات المحمية (تتطلب تسجيل دخول)
Route::middleware(['auth'])->group(function () {
    
    // تسجيل الخروج ولوحة التحكم
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // الملف الشخصي والإعدادات
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    // الموارد الأساسية (Projects, Tasks, Clients, Employees)
    // Projects: everyone authenticated can view; only Admin/Manager can create, edit, or delete
    Route::resource('projects', ProjectController::class)->only(['index', 'show']);
    Route::middleware('role:admin,manager')->group(function () {
        Route::resource('projects', ProjectController::class)->except(['index', 'show']);
    });

    // Tasks: everyone authenticated can view; Employees can only edit/update (their own, checked in controller);
    // only Admin/Manager can create or delete
    Route::middleware('role:admin,manager,employee')->group(function () {
        Route::resource('tasks', TaskController::class)->only(['index', 'show']); });
    Route::middleware('role:admin,manager,employee')->group(function () {
        Route::resource('tasks', TaskController::class)->only(['edit', 'update']);
    });
    Route::middleware('role:admin,manager')->group(function () {
        Route::resource('tasks', TaskController::class)->only(['create', 'store', 'destroy']);
    });

    
        // Clients management: Admin/Manager. Employees resource (HR-level record management): Admin only.
    Route::middleware('role:admin,manager')->group(function () {
        Route::resource('clients', ClientController::class);
    });
    Route::middleware('role:admin')->group(function () {
        Route::resource('employees', EmployeeController::class);
    });
    Route::middleware('role:admin')->group(function () {
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });

        Route::middleware('role:admin')->group(function () {
        Route::post('/projects/{project}/reassign-creator', [ProjectController::class, 'reassignCreator'])->name('projects.reassignCreator');
    });

    // مسارات تفاصيل المهام والتعليقات (إضافة، تعديل، حذف)
   // Route::get('/project-tasks/{task}', fn ($task) => view('tasks.project-show', compact('task')))->name('tasks.project-show');
    Route::post('/tasks/{taskId}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::middleware('role:admin,manager')->group(function () {
        Route::post('/projects/{project_id}/comments', [CommentController::class, 'storeForProject'])->name('comments.storeForProject');
    });

        Route::middleware('role:client')->group(function () {
        Route::post('/projects/{project_id}/tickets', [TicketController::class, 'store'])->name('tickets.store');
    });

    Route::middleware('role:admin,manager')->group(function () {
        Route::put('/tickets/{ticket_id}', [TicketController::class, 'update'])->name('tickets.update');
    });

    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // الإشعارات
    Route::get('/notifications/read', function () {
        if (auth()->check()) {
            auth()->user()->unreadNotifications->markAsRead();
        }
        return response()->json(['status' => 'success']);
    })->name('notifications.read');

});