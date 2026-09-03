<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    // عرض صفحة الإعدادات مع تمرير بيانات المستخدم (مثل حالة الإشعارات)
    public function index()
    {
        return view('settings.index');
    }

    // تحديث حالة الإشعارات (تفعيل/إيقاف) عبر الـ AJAX
    public function updateNotifications(Request $request)
    {
        $user = auth()->user();
        $user->email_notifications = $request->boolean('email_notifications');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث تفضيلات الإشعارات بنجاح'
        ]);
    }

    // تغيير كلمة المرور والتحقق من الحالية
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'], // يتطلب حقل confirmation باسم new_password_confirmation
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'new_password.min' => 'يجب أن تكون كلمة المرور الجديدة 8 أحرف على الأقل.',
            'new_password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ]);

        $user = auth()->user();

        // التحقق من صحة كلمة المرور الحالية
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة.'
            ], 422);
        }

        // تحديث كلمة المرور
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث كلمة المرور بنجاح'
        ]);
    }
}