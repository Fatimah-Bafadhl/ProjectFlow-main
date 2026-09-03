<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * عرض صفحة الملف الشخصي للمستخدم الحالي
     */
    public function show()
    {
        $user = Auth::user();
        
        // لم نعد بحاجة لـ Client هنا لأن كل بيانات البروفايل أصبحت في جدول users
        return view('profile.show', compact('user'));
    }

    /**
     * تحديث بيانات الملف الشخصي
     */
public function update(Request $request)
{
    $request->validate([
        'username'     => 'required|string|max:255',
        // استبدلي كلمة 'user_id' في النهاية باسم عمود الـ ID الفعلي في جدول الـ users لديك
        'email'        => 'required|email|max:255|unique:users,email,' . auth()->id() . ',user_id',
        'phone'        => 'nullable|string|max:20',
        'company_name' => 'nullable|string|max:255',
    ]);

    auth()->user()->update([
        'username'     => $request->username,
        'email'        => $request->email,
        'phone'        => $phone = $request->phone, // أو الحقل الخاص بك
        'company_name' => $request->company_name,
    ]);

    return redirect()->back()->with('success', 'تم حفظ التعديلات بنجاح');
}

public function destroy(Request $request)
{
    $user = auth()->user();

    // تسجيل الخروج وحذف الحساب أو الـ Profile حسب رغبتك
    auth()->logout();

    $user->delete();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')->with('success', 'تم حذف الحساب بنجاح');
}
}