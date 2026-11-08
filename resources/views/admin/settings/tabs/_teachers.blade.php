@php
    /*
     * Teachers panel — section HEADER only.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 Nothing about an individual teacher is editable here, and that is
     * the point: the portrait comes from the polymorphic media relation, the
     * branch through the teacher's subjects, and the lecture count from the
     * lectures table. All three are derived, so a text field for any of them
     * would be a second source that drifts from the first.
     */
    $defaults = \App\Models\Setting::TEACHERS_DEFAULTS;
    $value = fn (string $f) => old('teachers.'.$f, $settings['teachers.'.$f] ?? $defaults['teachers.'.$f]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">ترويسة قسم المعلّمين</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان والنصّ التمهيدي، وزرّ عرض جميع المعلّمين.
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        {{-- Says plainly where the card data comes from, so nobody looks for
             a field here to change a lecture count. --}}
        <div class="flex items-start gap-2.5 rounded-xl border border-hairline bg-surface px-4 py-3">
            <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-stone" />
            <p class="text-caption text-stone">
                بطاقات المعلّمين تُبنى من القاعدة: الصورة من الوسائط، والفرع من مواد المعلّم،
                وعدد المحاضرات محسوب من جدول المحاضرات. لا تُحرَّر من هنا.
            </p>
        </div>

        <div>
            <label for="teachers_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="teachers_badge" name="teachers[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('teachers.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="teachers_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="teachers_title" name="teachers[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('teachers.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="teachers_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="teachers_sub" name="teachers[sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            @error('teachers.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="teachers_btn_text" class="{{ $labelClass }}">نصّ الزرّ</label>
                <input type="text" id="teachers_btn_text" name="teachers[btn_text]" maxlength="80"
                       value="{{ $value('btn_text') }}" class="{{ $inputClass }}">
                @error('teachers.btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="teachers_btn_link" class="{{ $labelClass }}">وجهة الزرّ</label>
                {{-- type=text not url: /teachers and #roster are valid targets
                     the browser validator rejects. --}}
                <input type="text" id="teachers_btn_link" name="teachers[btn_link]" dir="ltr" maxlength="500"
                       placeholder="/teachers" value="{{ $value('btn_link') }}" class="{{ $inputClass }}">
                @error('teachers.btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <p class="text-micro text-stone">النصّ والوجهة يُملآن معاً؛ تركهما فارغين يُخفي الزرّ من الصفحة.</p>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ الترويسة</x-admin.btn>
    </div>
</form>
