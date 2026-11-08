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

        {{-- ═══ صورة الحساب ═══ --}}
        <div x-data="avatarEditor" class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">

            <h3 class="text-ui font-bold text-ink">صورة الحساب</h3>
            <p class="mt-1.5 text-micro text-stone">تظهر في الشريط العلوي وبجانب ما تنشره.</p>

            @error('avatar')
                <p class="mt-3 rounded-lg bg-error/8 px-3 py-2 text-micro text-error-deep">{{ $message }}</p>
            @enderror

            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">

                {{-- المعاينة الدائرية — تُفتح بالنقر أيضاً لا بالزرّ وحده --}}
                <button type="button" @click="view()"
                        @class([
                            'group relative shrink-0 rounded-full transition',
                            'cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/40',
                        ])
                        :disabled="! {{ $user->avatar_url ? 'true' : 'false' }}"
                        aria-label="معاينة الصورة">
                    <x-ui.avatar :src="$user->avatar_url" :name="$user->displayName()" size="lg"
                                 class="size-24 text-h4" />

                    @if ($user->avatar_url)
                        <span class="absolute inset-0 grid place-items-center rounded-full bg-ink/45 opacity-0
                                     transition group-hover:opacity-100">
                            <x-icon name="eye" class="size-5 text-on-dark" />
                        </span>
                    @endif
                </button>

                <div class="min-w-0 flex-1">
                    {{-- 🔴 نموذج الرفع نفسه يحمل حقل الملف المخفي الذي يملؤه
                         المحرّر. حقل الاختيار المرئي منفصل عنه ولا يُرسَل —
                         المرسَل دائماً هو الصورة **المقصوصة**. --}}
                    <form method="POST" action="{{ route('admin.profile.avatar') }}"
                          enctype="multipart/form-data" x-ref="form" class="contents">
                        @csrf
                        <input type="file" name="avatar" x-ref="upload" class="hidden">
                    </form>

                    <input type="file" x-ref="picker" class="hidden"
                           accept="image/jpeg,image/png,image/webp"
                           @change="pick($event)">

                    <div class="flex flex-wrap gap-2">
                        <x-admin.btn type="button" variant="primary" @click="$refs.picker.click()">
                            {{ $user->hasUploadedAvatar() ? 'تغيير الصورة' : 'رفع صورة' }}
                        </x-admin.btn>

                        @if ($user->avatar_url)
                            <x-admin.btn type="button" variant="secondary" @click="view()">معاينة الصورة</x-admin.btn>
                        @endif

                        {{-- ⚠️ يظهر فقط لصورة مرفوعة: صورة جوجل ليست ملك
                             الحساب ليحذفها، وحذفها من هنا لا يفعل شيئاً. --}}
                        @if ($user->hasUploadedAvatar())
                            <form method="POST" action="{{ route('admin.profile.avatar.destroy') }}"
                                  data-confirm-title="حذف صورة الحساب؟"
                                  data-confirm="سيعود الحساب إلى الصورة الافتراضية."
                                  data-confirm-action="نعم، احذفها">
                                @csrf
                                @method('DELETE')
                                <x-admin.btn variant="danger">حذف الصورة</x-admin.btn>
                            </form>
                        @endif
                    </div>

                    <p class="mt-2.5 text-micro text-stone">
                        JPG · PNG · WebP · 4 ميغابايت كحدّ أقصى. تُقصّ الصورة مربّعة قبل الرفع.
                    </p>
                </div>
            </div>

            {{-- ═══ محرّر القصّ ═══ --}}
            <div x-show="cropping" x-cloak
                 @keydown.escape.window="close()"
                 class="fixed inset-0 z-50 grid place-items-center p-4"
                 role="dialog" aria-modal="true" aria-labelledby="cropper-title">

                <div @click="close()" class="absolute inset-0 bg-ink/60"></div>

                <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden
                            rounded-2xl border border-hairline bg-canvas shadow-xl">

                    <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                        <h2 id="cropper-title" class="text-ui font-bold text-ink">قصّ الصورة</h2>
                        <button type="button" @click="close()"
                                class="cursor-pointer rounded-lg p-1.5 text-stone transition hover:bg-surface"
                                aria-label="إغلاق">
                            <x-icon name="close" class="size-5" />
                        </button>
                    </div>

                    {{-- 🔴 dir="ltr" على مسرح القصّ: أدوات cropper تحسب
                         الإحداثيات من اليسار، وقلبها مع الصفحة يجعل السحب
                         يتحرّك عكس اتجاه اليد. نفس استثناء مشغّل الفيديو. --}}
                    <div dir="ltr" class="max-h-[55vh] overflow-hidden bg-canvas-dark">
                        <img x-ref="source" alt="" class="block max-w-full">
                    </div>

                    {{-- شريط الأدوات --}}
                    <div class="flex flex-wrap items-center justify-center gap-2 border-t border-hairline px-5 py-3">
                        {{-- أيقونات دوران اتجاهية، لكنها لا تُقلب مع الاتجاه:
                             «يسار» هنا تعني جهة الدوران لا جهة القراءة. --}}
                        <x-admin.btn type="button" variant="secondary" @click="rotate(-90)" title="تدوير لليسار">
                            <x-icon name="undo" class="size-4" />
                        </x-admin.btn>

                        <x-admin.btn type="button" variant="secondary" @click="rotate(90)" title="تدوير لليمين">
                            <x-icon name="undo" class="size-4 -scale-x-100" />
                        </x-admin.btn>

                        <x-admin.btn type="button" variant="secondary" @click="zoom(0.1)" title="تكبير">
                            <x-icon name="plus" class="size-4" />
                        </x-admin.btn>

                        <x-admin.btn type="button" variant="secondary" @click="zoom(-0.1)" title="تصغير">
                            <span class="grid size-4 place-items-center text-ui leading-none">−</span>
                        </x-admin.btn>

                        <x-admin.btn type="button" variant="ghost" @click="reset()">إعادة ضبط</x-admin.btn>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline px-5 py-4">
                        <x-admin.btn type="button" variant="ghost" @click="close()">إلغاء</x-admin.btn>
                        <x-admin.btn type="button" variant="primary" x-ref="confirm"
                                     @click="confirm()" ::disabled="busy">
                            <span x-show="! busy">تأكيد وقصّ الصورة</span>
                            <span x-show="busy" x-cloak>جارٍ الحفظ…</span>
                        </x-admin.btn>
                    </div>
                </div>
            </div>

            {{-- ═══ عارض الصورة بالحجم الكامل ═══ --}}
            @if ($user->avatar_url)
                <div x-show="viewing" x-cloak
                     @keydown.escape.window="closeViewer()"
                     @click="closeViewer()"
                     class="fixed inset-0 z-50 grid place-items-center p-4"
                     role="dialog" aria-modal="true" aria-label="صورة الحساب">

                    <div class="absolute inset-0 bg-ink/80"></div>

                    {{-- الصورة بدقّتها الكاملة، محدودة بالنافذة لا بعرض ثابت. --}}
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->displayName() }}"
                         @click.stop
                         class="relative max-h-[85vh] max-w-full rounded-xl object-contain shadow-xl">

                    <button type="button" @click="closeViewer()"
                            class="absolute end-4 top-4 cursor-pointer rounded-lg bg-ink/50 p-2 text-on-dark
                                   transition hover:bg-ink/70"
                            aria-label="إغلاق">
                        <x-icon name="close" class="size-5" />
                    </button>
                </div>
            @endif
        </div>

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
