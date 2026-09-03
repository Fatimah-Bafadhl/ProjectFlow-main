@extends('layouts.app')
@section('title', 'الملف الشخصي')
@section('content-class', 'p-4 flex-grow-1')

@section('content')
<!-- عنوان الصفحة -->
<div class="mb-3">
    <h2 class="task-page-title" >الملف الشخصي</h2>
    <p class="text-muted m-0" style="font-size: 14px;">معلوماتي الشخصية</p>
</div>

<!-- نموذج البيانات واقع مباشرة تحت جملة معلوماتي الشخصية -->
<div class="card custom-modal border p-4 mx-auto" style="max-width: 500px;">
    
   <!-- الهيدر الداخلي (الصورة الرمزية والاسم والبريد) -->
    <div class="d-flex align-items-center gap-3 text-end mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center" id="profileCardAvatar" style="width: 48px; height: 48px; background-color: rgba(138, 132, 173, 0.12); color: #8A84AD; font-weight: 600; font-size: 14px;">
            {{ mb_substr($user->username, 0, 2) }}
        </div>
        <div>
            <h3 class="m-0 fw-bold" id="profileCardNameDisplay" style="font-size: 16px; color: #000000;">{{ $user->username }}</h3>
            <span class="text-muted" id="profileCardEmailDisplay" style="font-size: 12px;">{{ $user->email }}</span>
            <div class="mt-1">
                <span class="client-badge" style="font-size: 11px;">
                    @if($user->role === 'client')
                        عميل
                    @elseif($user->role === 'employee')
                        موظف
                    @else
                        مدير
                    @endif
                </span>
            </div>
        </div>
    </div>
    <!-- نموذج التعديل المباشر المربوط بـ Laravel -->
    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="d-flex flex-column align-items-center gap-3">
            <div class="w-75">
                <label class="form-label custom-label mb-1">الاسم الكامل <span class="text-danger">*</span></label>
                <input class="form-control custom-input @error('username') is-invalid @enderror" name="username" required type="text" value="{{ old('username', $user->username) }}"/>
                @error('username')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="w-75">
                <label class="form-label custom-label mb-1">البريد الالكتروني <span class="text-danger">*</span></label>
                <input class="form-control custom-input @error('email') is-invalid @enderror" name="email" required type="email" value="{{ old('email', $user->email) }}"/>
                @error('email')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="w-75">
                <label class="form-label custom-label mb-1">رقم الجوال</label>
                <input class="form-control custom-input @error('phone') is-invalid @enderror" dir="ltr" name="phone" pattern="^05[0-9]{8}$" placeholder="05xxxxxxxx" title="يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام" type="tel" value="{{ old('phone', $user->phone ?? '') }}"/>
                @error('phone')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="w-75">
                <label class="form-label custom-label mb-1">الشركة</label>
                <input class="form-control custom-input @error('company_name') is-invalid @enderror" name="company_name" type="text" value="{{ old('company_name', $user->company_name ?? '') }}"/>
                @error('company_name')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- أزرار الحفظ والحذف -->
        <div class="d-flex justify-content-between align-items-center mt-4 px-5">
            <button class="btn text-danger p-0 border-0 bg-transparent fw-semibold" onclick="openDeleteAccountModal()" style="font-size: 14px;" type="button">
                حذف الحساب
            </button>
            <button class="btn btn-save px-4 py-1" style="font-size: 14px;" type="submit">حفظ</button>
        </div>
    </form>
</div>
@endsection

@push('modals')
<!-- مودال تأكيد حذف الحساب -->
<div aria-hidden="true" class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal text-center p-4">
            <div class="modal-body p-0">
                <p class="delete-text mb-4">هل تريد حذف الحساب نهائياً؟</p>
                <form action="{{ route('profile.destroy') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="d-flex justify-content-center gap-3">
                        <button class="btn btn-delete-confirm" type="submit">حذف</button>
                        <button class="btn btn-delete-cancel" data-bs-dismiss="modal" type="button">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush