@php
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    $student = $user->userable;
@endphp

<x-layouts.student :title="__('student.profile_title')"
                   :student-name="$user->displayName()"
                   :student-avatar="$user->avatar_url">

    <div class="mx-auto max-w-3xl space-y-6">

        <div>
            <h1 class="text-h4 font-bold text-ink">{{ __('student.profile_title') }}</h1>
            <p class="mt-1.5 text-caption text-stone">بياناتك وصورتك وكلمة المرور.</p>
        </div>

        {{-- ═══ صورة الحساب ═══
             🔴 نفس مكوّن avatarEditor المستعمل في ملفَّي الأدمن والمعلّم، لا
             نسخة منه: المسار واحد (syncAvatar على User)، فالسلوكات لا تنحرف. --}}
        <div x-data="avatarEditor" class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">

            <h2 class="text-ui font-bold text-ink">صورة الحساب</h2>
            <p class="mt-1.5 text-micro text-stone">تظهر في الشريط العلوي وعلى صفحاتك.</p>

            @error('avatar')
                <p class="mt-3 rounded-lg bg-error/8 px-3 py-2 text-micro text-error-deep">{{ $message }}</p>
            @enderror

            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">

                <button type="button" @click="view()"
                        class="group relative shrink-0 cursor-pointer rounded-full transition
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/40"
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
                    {{-- 🔴 نموذج الرفع يحمل حقل الملف المخفي الذي يملؤه المحرّر.
                         المرسَل دائماً هو الصورة **المقصوصة**. --}}
                    <form method="POST" action="{{ route('student.profile.avatar') }}"
                          enctype="multipart/form-data" x-ref="form" class="contents">
                        @csrf
                        <input type="file" name="avatar" x-ref="upload" class="hidden">
                    </form>

                    <input type="file" x-ref="picker" class="hidden"
                           accept="image/jpeg,image/png,image/webp"
                           @change="pick($event)">

                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="button" size="sm" @click="$refs.picker.click()">
                            {{ $user->hasUploadedAvatar() ? 'تغيير الصورة' : 'رفع صورة' }}
                        </x-ui.button>

                        @if ($user->avatar_url)
                            <x-ui.button type="button" variant="secondary" size="sm" @click="view()">
                                معاينة الصورة
                            </x-ui.button>
                        @endif

                        @if ($user->hasUploadedAvatar())
                            <form method="POST" action="{{ route('student.profile.avatar.destroy') }}"
                                  data-confirm-title="حذف صورة الحساب؟"
                                  data-confirm="سيعود الحساب إلى الصورة الافتراضية."
                                  data-confirm-action="نعم، احذفها">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger" size="sm">حذف الصورة</x-ui.button>
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
                 role="dialog" aria-modal="true" aria-labelledby="student-cropper-title">

                <div @click="close()" class="absolute inset-0 bg-ink/60"></div>

                <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden
                            rounded-2xl border border-hairline bg-canvas shadow-xl">

                    <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                        <h2 id="student-cropper-title" class="text-ui font-bold text-ink">قصّ الصورة</h2>
                        <button type="button" @click="close()"
                                class="cursor-pointer rounded-lg p-1.5 text-stone transition hover:bg-surface"
                                aria-label="إغلاق">
                            <x-icon name="close" class="size-5" />
                        </button>
                    </div>

                    {{-- 🔴 dir="ltr" على مسرح القصّ: أدوات cropper تحسب الإحداثيات
                         من اليسار. نفس استثناء مشغّل الفيديو. --}}
                    <div dir="ltr" class="max-h-[55vh] overflow-hidden bg-canvas-dark">
                        <img x-ref="source" alt="" class="block max-w-full">
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2 border-t border-hairline px-5 py-3">
                        <x-ui.button type="button" variant="secondary" size="sm"
                                     @click="rotate(-90)" title="تدوير لليسار">
                            <x-icon name="undo" class="size-4" />
                        </x-ui.button>

                        <x-ui.button type="button" variant="secondary" size="sm"
                                     @click="rotate(90)" title="تدوير لليمين">
                            <x-icon name="undo" class="size-4 -scale-x-100" />
                        </x-ui.button>

                        <x-ui.button type="button" variant="secondary" size="sm"
                                     @click="zoom(0.1)" title="تكبير">
                            <x-icon name="plus" class="size-4" />
                        </x-ui.button>

                        <x-ui.button type="button" variant="secondary" size="sm"
                                     @click="zoom(-0.1)" title="تصغير">
                            <span class="grid size-4 place-items-center leading-none">−</span>
                        </x-ui.button>

                        <x-ui.button type="button" variant="ghost" size="sm" @click="reset()">
                            إعادة ضبط
                        </x-ui.button>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline px-5 py-4">
                        <x-ui.button type="button" variant="ghost" size="sm" @click="close()">إلغاء</x-ui.button>
                        <x-ui.button type="button" size="sm" x-ref="confirm"
                                     @click="confirm()" ::disabled="busy">
                            <span x-show="! busy">تأكيد وقصّ الصورة</span>
                            <span x-show="busy" x-cloak>جارٍ الحفظ…</span>
                        </x-ui.button>
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

        {{-- ═══ البيانات الشخصية ═══ --}}
        <form method="POST" action="{{ route('student.profile.update') }}"
              class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
            @csrf
            @method('PUT')

            <h2 class="text-ui font-bold text-ink">البيانات الشخصية</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="full_name" class="{{ $labelClass }}">
                        الاسم الكامل <span class="text-error-deep">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name" maxlength="160" required
                           value="{{ old('full_name', $student?->full_name) }}" class="{{ $inputClass }}">
                    @error('full_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="{{ $labelClass }}">
                        البريد الإلكتروني <span class="text-error-deep">*</span>
                    </label>
                    <input type="email" id="email" name="email" maxlength="190" required dir="ltr"
                           value="{{ old('email', $user->email) }}" class="{{ $inputClass }} font-latin">
                    @error('email')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                {{-- 🔴 الفرع للقراءة فقط: هو الفلتر الجذري لكل المحتوى. --}}
                <div>
                    <label class="{{ $labelClass }}">الفرع الدراسي</label>
                    <div class="flex items-center gap-2 rounded-xl border border-hairline bg-surface-soft px-3 py-2.5">
                        <x-icon name="book" class="size-4 shrink-0 text-stone" />
                        <span class="text-ui text-ink">{{ $student?->branch?->name ?? '—' }}</span>
                    </div>
                    <p class="mt-1.5 text-micro text-stone">لتغيير الفرع تواصل مع إدارة المنصّة.</p>
                </div>

                <div>
                    <label for="school_name" class="{{ $labelClass }}">المدرسة</label>
                    <input type="text" id="school_name" name="school_name" maxlength="160"
                           value="{{ old('school_name', $student?->school_name) }}" class="{{ $inputClass }}">
                    @error('school_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="governorate" class="{{ $labelClass }}">المحافظة</label>
                    <input type="text" id="governorate" name="governorate" maxlength="80"
                           value="{{ old('governorate', $student?->governorate) }}" class="{{ $inputClass }}">
                    @error('governorate')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="locale" class="{{ $labelClass }}">لغة الواجهة</label>
                    <select id="locale" name="locale" class="{{ $inputClass }}">
                        <option value="ar" @selected(old('locale', $user->locale) === 'ar')>العربية</option>
                        <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                    </select>
                    @error('locale')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="{{ $labelClass }}">رقم هاتفك</label>
                    <input type="tel" id="phone" name="phone" maxlength="32" dir="ltr"
                           placeholder="+970 599 000 000"
                           value="{{ old('phone', $student?->phone) }}" class="{{ $inputClass }} font-latin">
                    @error('phone')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="guardian_phone" class="{{ $labelClass }}">هاتف وليّ الأمر</label>
                    <input type="tel" id="guardian_phone" name="guardian_phone" maxlength="32" dir="ltr"
                           placeholder="+970 599 000 000"
                           value="{{ old('guardian_phone', $student?->guardian_phone) }}" class="{{ $inputClass }} font-latin">
                    {{-- 🔴 بيانات تواصل لا هوية: الدخول بالبريد وجوجل. --}}
                    <p class="mt-1.5 text-micro text-stone">للتواصل عند الحاجة — لا يُستخدم للدخول.</p>
                    @error('guardian_phone')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-ui.button type="submit">حفظ البيانات</x-ui.button>
            </div>
        </form>

        {{-- ═══ كلمة المرور ═══ --}}
        <form method="POST" action="{{ route('student.profile.password') }}"
              class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
            @csrf
            @method('PUT')

            <h2 class="text-ui font-bold text-ink">كلمة المرور</h2>
            <p class="mt-1.5 text-micro text-stone">تحتاج كلمة المرور الحالية لتغييرها.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="current_password" class="{{ $labelClass }}">
                        كلمة المرور الحالية <span class="text-error-deep">*</span>
                    </label>
                    {{-- 🔴 لا old() على حقول كلمات المرور إطلاقاً. --}}
                    <input type="password" id="current_password" name="current_password" required
                           autocomplete="current-password" class="{{ $inputClass }}">
                    @error('current_password')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="{{ $labelClass }}">
                        كلمة المرور الجديدة <span class="text-error-deep">*</span>
                    </label>
                    <input type="password" id="password" name="password" required
                           autocomplete="new-password" class="{{ $inputClass }}">
                    @error('password')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="{{ $labelClass }}">
                        تأكيد كلمة المرور <span class="text-error-deep">*</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           autocomplete="new-password" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-ui.button type="submit">تحديث كلمة المرور</x-ui.button>
            </div>
        </form>
    </div>

</x-layouts.student>
