@php
    $teacherName = 'أ. سامر خليل';
    $assignedSubjects = ['الرياضيات', 'الفيزياء', 'رياضيات تجارية'];
@endphp

<x-layouts.teacher :title="__('teacher.profile_title')" :teacher-name="$teacherName">

    <div class="mx-auto max-w-3xl" data-profile-form>

        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('teacher.profile_title') }}</h1>
            <p class="measure mt-1 text-ui text-steel">{{ __('teacher.profile_subtitle') }}</p>
        </div>

        {{-- تنبيه العلانية — المعلم يجب أن يعرف أن نبذته ليست داخلية --}}
        <x-ui.alert variant="accent" icon="users" class="mt-6">
            {{ __('teacher.profile_public_note') }}
        </x-ui.alert>

        <form data-demo-submit="{{ route('teacher.profile') }}" class="mt-6 flex flex-col gap-6">

            {{-- الصورة --}}
            <section class="tile flex flex-col gap-4 p-5 sm:p-6">
                <h2 class="text-h5 font-semibold text-ink">{{ __('teacher.profile_photo_section') }}</h2>

                <div class="flex flex-wrap items-center gap-4">
                    <x-ui.avatar :name="$teacherName" size="lg" class="size-20 text-h4" />

                    <div class="min-w-0">
                        <label class="inline-flex h-11 cursor-pointer items-center rounded-full border border-hairline-strong
                                      bg-canvas px-5 text-ui font-semibold text-ink transition hover:bg-surface lg:h-10">
                            <input type="file" name="avatar" accept="image/*" class="sr-only">
                            {{ __('teacher.profile_photo_change') }}
                        </label>
                        <p class="mt-1.5 text-caption text-stone">{{ __('teacher.profile_photo_hint') }}</p>
                    </div>
                </div>
            </section>

            {{-- بيانات الحساب --}}
            <section class="tile flex flex-col gap-4 p-5 sm:p-6">
                <h2 class="text-h5 font-semibold text-ink">{{ __('teacher.profile_account_section') }}</h2>

                <x-ui.input name="name" :label="__('teacher.profile_name_label')" :value="$teacherName" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="email" type="email" :label="__('teacher.profile_email_label')"
                                value="samer@paledu.ps" required />

                    {{-- 🔴 الجوال LTR إلزاماً داخل نموذج RTL --}}
                    <x-ui.input name="phone" type="tel" :label="__('teacher.profile_phone_label')"
                                value="0599123456" dir="ltr" inputmode="tel" />
                </div>

                <x-ui.input name="specialty" :label="__('teacher.profile_specialty_label')"
                            :placeholder="__('teacher.profile_specialty_placeholder')"
                            value="رياضيات — تفاضل وتكامل" />

                <x-ui.textarea
                    name="bio"
                    :label="__('teacher.profile_bio_label')"
                    :placeholder="__('teacher.profile_bio_placeholder')"
                    :hint="__('teacher.profile_bio_hint')"
                    :rows="4"
                    value="معلّم رياضيات منذ 12 عاماً، أركّز على بناء الحدس قبل القاعدة، وأشرح كل فكرة بمثال من ورقة امتحان سابقة." />
            </section>

            {{-- المواد المسندة — للقراءة فقط --}}
            <section class="tile flex flex-col gap-3 p-5 sm:p-6">
                <h2 class="text-h5 font-semibold text-ink">{{ __('teacher.profile_subjects_section') }}</h2>

                <div class="flex flex-wrap gap-2">
                    @foreach ($assignedSubjects as $subject)
                        <x-ui.badge variant="branch">{{ $subject }}</x-ui.badge>
                    @endforeach
                </div>

                <p class="text-caption text-stone">{{ __('teacher.profile_subjects_note') }}</p>
            </section>

            <div class="flex justify-end">
                <x-ui.button type="submit" size="md" class="w-full sm:w-auto sm:min-w-44">
                    {{ __('teacher.profile_save') }}
                </x-ui.button>
            </div>
        </form>

        {{-- كلمة المرور — نموذج منفصل عمداً، لا يُحفظ مع بقية الحقول --}}
        <form data-demo-submit="{{ route('teacher.profile') }}" class="mt-6">
            <section class="tile flex flex-col gap-4 p-5 sm:p-6">
                <h2 class="text-h5 font-semibold text-ink">{{ __('teacher.profile_password_section') }}</h2>

                <x-ui.password-input name="current_password" :label="__('teacher.profile_current_password')" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.password-input name="password" :label="__('teacher.profile_new_password')" required />
                    <x-ui.password-input name="password_confirmation" :label="__('teacher.profile_confirm_password')" required />
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="secondary" size="md" class="w-full sm:w-auto">
                        {{ __('teacher.profile_update_password') }}
                    </x-ui.button>
                </div>
            </section>
        </form>
    </div>

</x-layouts.teacher>
