@php
    /*
     * FAQ form.
     *
     * Expected variables: $faq · $action · $method
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    $isNew = ! $faq->exists;
@endphp

<form method="POST" action="{{ $action }}" class="mt-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div class="space-y-5">
            <div>
                <label for="question" class="{{ $labelClass }}">
                    السؤال <span class="text-error-deep">*</span>
                </label>
                <input type="text" id="question" name="question" maxlength="255"
                       value="{{ old('question', $faq->question) }}" class="{{ $inputClass }}">
                <p class="{{ $hintClass }}">اكتبه بصيغة سؤال الطالب لا بصيغة العنوان.</p>
                @error('question')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="answer" class="{{ $labelClass }}">
                    الإجابة <span class="text-error-deep">*</span>
                </label>
                {{-- Arabic body copy needs a generous line box; leading-relaxed
                     keeps the editor readable at the same rhythm as the page. --}}
                <textarea id="answer" name="answer" rows="7" maxlength="2000"
                          class="{{ $inputClass }} leading-relaxed">{{ old('answer', $faq->answer) }}</textarea>
                <p class="{{ $hintClass }}">إجابة مباشرة. تُعرض داخل عنصر قابل للطيّ في الصفحة الرئيسية.</p>
                @error('answer')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-5 border-t border-hairline pt-6 sm:grid-cols-2">
            <div>
                <label for="display_order" class="{{ $labelClass }}">ترتيب العرض</label>
                <input type="number" id="display_order" name="display_order" min="0" max="9999"
                       value="{{ old('display_order', $faq->display_order) }}"
                       class="{{ $inputClass }} tabular-nums">
                <p class="{{ $hintClass }}">الأصغر يظهر أولاً. الأول يُفتح تلقائياً في الصفحة.</p>
                @error('display_order')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-end">
                <label class="flex cursor-pointer items-center gap-3 pb-2.5">
                    {{-- Hidden 0 first: an unchecked box submits nothing at
                         all, so without it a question could never be hidden. --}}
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $faq->is_active ?? true))
                           class="size-4 rounded border-hairline-strong text-accent focus:ring-accent/25">
                    <span class="text-ui text-ink">ظاهر في الصفحة الرئيسية</span>
                </label>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <x-admin.btn :href="route('admin.faqs.index')" variant="secondary">إلغاء</x-admin.btn>
        <x-admin.btn variant="primary">{{ $isNew ? 'إضافة السؤال' : 'حفظ التغييرات' }}</x-admin.btn>
    </div>
</form>
