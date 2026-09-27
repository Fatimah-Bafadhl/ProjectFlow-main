<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdatePersonRequest;
use App\Services\PersonProfileService;

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
    public function update(UpdatePersonRequest $request, PersonProfileService $accounts)
    {
        $accounts->update(auth()->user(), $request->validated());

        return redirect()->back()->with('success', 'تم حفظ التعديلات بنجاح');
    }
}