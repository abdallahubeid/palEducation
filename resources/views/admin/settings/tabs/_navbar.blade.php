@php
    /*
     * لوح «الشريط العلوي» — روابط التواصل الثلاثة.
     *
     * المتغيّرات المتوقّعة: $settings (خريطة مفتاح⇐قيمة) · $action
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';

    $fields = [
        ['key' => 'social_link1', 'label' => 'الرابط الأول', 'placeholder' => 'https://facebook.com/…'],
        ['key' => 'social_link2', 'label' => 'الرابط الثاني', 'placeholder' => 'https://instagram.com/…'],
        ['key' => 'social_link3', 'label' => 'الرابط الثالث', 'placeholder' => 'https://youtube.com/…'],
    ];
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">روابط التواصل</h3>
            <p class="mt-1.5 text-micro text-stone">
                تظهر في أعلى كل صفحة عامة. اتركه فارغاً لإخفاء الأيقونة.
            </p>
        </div>

        {{-- 🔴 تذكير دائم لا زخرفة: هذه القيم تُقرأ علناً، وجدول settings لا
             يملك عمود is_public بعد يميّز العام من الخاص. --}}
        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذه الروابط تظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        @foreach ($fields as $field)
            <div>
                <label for="{{ $field['key'] }}" class="{{ $labelClass }}">{{ $field['label'] }}</label>

                {{-- dir=ltr على الحقل وحده: الرابط لاتيني، والتسمية عربية --}}
                <input type="url"
                       id="{{ $field['key'] }}"
                       name="navbar[{{ $field['key'] }}]"
                       dir="ltr"
                       placeholder="{{ $field['placeholder'] }}"
                       value="{{ old('navbar.'.$field['key'], $settings['navbar.'.$field['key']] ?? null) }}"
                       class="{{ $inputClass }}">
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
