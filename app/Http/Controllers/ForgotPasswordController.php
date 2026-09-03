<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ForgotPasswordController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function processDirectReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.email'    => 'صيغة البريد غير صحيحة',
            'email.exists'   => 'هذا البريد غير موجود في نظامنا'
        ]);

        // التوجيه المباشر لصفحة كلمة المرور الجديدة مع إرسال البريد الإلكتروني مع الرابط
        return response()->json([
            'status' => 'success',
            'redirect_url' => route('password.reset.page', ['email' => $request->email])
        ]);
    }

   public function updatePassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'password' => 'required|min:8|confirmed'
    ], [
        'email.required' => 'يرجى إدخال البريد الإلكتروني',
        'email.exists'   => 'هذا البريد غير مسجل لدينا',
        'password.required' => 'يرجى إدخال كلمة المرور الجديدة',
        'password.min' => 'كلمة المرور يجب أن لا تقل عن 8 خانات',
        'password.confirmed' => 'تأكيد كلمة المرور غير متطابق'
    ]);

    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->save();

    // إرجاع استجابة JSON ناجحة لكي تلتقطها الجافاسكريفت وتظهر علامة النجاح
    return response()->json([
        'status' => 'success',
        'message' => 'تم تغيير كلمة المرور بنجاح'
    ]);
}

public function showResetForm(Request $request)
{
    $email = $request->query('email');
    
    // التحقق من أن البريد موجود فعلاً في القاعدة كحماية إضافية
    if (!$email || !User::where('email', $email)->exists()) {
        return redirect()->route('password.request')->withErrors(['email' => 'الرابط غير صالح أو انتهت صلاحيته']);
    }

    return view('auth.reset-password', compact('email'));
}
}