@php
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
@endphp

<x-layouts.admin title="الملف الشخصي" breadcrumbs="الحساب / الملف الشخصي">

    <div class="mx-auto max-w-3xl space-y-6">

        <x-admin.page-head
            title="الملف الشخصي"
            lede="بيانات حسابك وكلمة المرور." />

        {{-- ═══ البيانات ═══ --}}
        <form method="POST" action="{{ route('admin.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
                <h3 class="text-ui font-bold text-ink">البيانات الأساسية</h3>

                <div>
                    <label for="full_name" class="{{ $labelClass }}">الاسم الكامل</label>
                    {{-- 🔴 الاسم يعيش على سجلّ المجال (admins.full_name) لا على
                         users — لا عمود name هناك. --}}
                    <input type="text" id="full_name" name="full_name"
                           value="{{ old('full_name', $user->userable?->full_name) }}"
                           class="{{ $inputClass }}" required>
                </div>

                <div>
                    <label for="email" class="{{ $labelClass }}">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" dir="ltr"
                           value="{{ old('email', $user->email) }}"
                           class="{{ $inputClass }}" required>
                    <p class="mt-1 text-micro text-stone">هو معرّف الدخول — تغييره يغيّر بريد تسجيل الدخول.</p>
                </div>

                <div>
                    <label for="locale" class="{{ $labelClass }}">لغة الواجهة</label>
                    <select id="locale" name="locale" class="{{ $inputClass }}">
                        <option value="ar" @selected(old('locale', $user->locale) === 'ar')>العربية</option>
                        <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                    </select>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <x-admin.btn variant="primary">حفظ البيانات</x-admin.btn>
            </div>
        </form>

        {{-- ═══ كلمة المرور ═══ --}}
        <form method="POST" action="{{ route('admin.profile.password') }}">
            @csrf
            @method('PUT')

            <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
                <div>
                    <h3 class="text-ui font-bold text-ink">كلمة المرور</h3>
                    <p class="mt-1 text-caption text-stone">
                        تُطلب كلمة المرور الحالية للتأكيد — لا يكفي أن تكون الجلسة مفتوحة.
                    </p>
                </div>

                <div>
                    <label for="current_password" class="{{ $labelClass }}">كلمة المرور الحالية</label>
                    <input type="password" id="current_password" name="current_password"
                           autocomplete="current-password" class="{{ $inputClass }}">
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="{{ $labelClass }}">كلمة المرور الجديدة</label>
                        <input type="password" id="password" name="password"
                               autocomplete="new-password" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="password_confirmation" class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               autocomplete="new-password" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <x-admin.btn variant="primary">تحديث كلمة المرور</x-admin.btn>
            </div>
        </form>
    </div>

</x-layouts.admin>
