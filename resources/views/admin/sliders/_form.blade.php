@php
    /*
     * نموذج الشريحة — عمودي بعرض كامل، حاوية واحدة (نمط نماذج veyra).
     *
     * 🔴 لا شبكة 3/1 ولا `max-w-*`: التقسيم السابق كان يترك عمود الشرح
     * شبه فارغ، والعمود الضيّق قبله كان يترك نصف الشاشة بيضاء. الحقول
     * هنا تتكدّس رأسياً وتملأ عرض الحاوية طبيعياً.
     *
     * المتغيّرات المتوقّعة: $slider · $action · $method
     *
     * 🔴 old() أولاً ثم قيمة النموذج: بعد فشل تحقّق تعود الحقول بما كتبه
     * المستخدم لا بما في القاعدة — وإلّا ضاع إدخاله وأعاد كتابته كاملاً.
     *
     * 🔴 enctype إجباري: بدونه يصل $request->file('image') فارغاً بلا خطأ
     * ولا تحذير — الحقل يُرسَل كنصّ باسم الملف فقط.
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $legendClass = 'text-ui font-bold text-ink';
    $isNew = ! $slider->exists;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        {{-- ═══ المحتوى ═══ --}}
        <div class="space-y-5">
            <h3 class="{{ $legendClass }}">المحتوى</h3>

            <div>
                <label for="badge_text" class="{{ $labelClass }}">الشارة</label>
                <input type="text" id="badge_text" name="badge_text"
                       value="{{ old('badge_text', $slider->badge_text) }}" class="{{ $inputClass }}">
                <p class="{{ $hintClass }}">السطر الصغير فوق العنوان.</p>
            </div>

            <div>
                <label for="title" class="{{ $labelClass }}">العنوان <span class="text-error-deep">*</span></label>
                <input type="text" id="title" name="title"
                       value="{{ old('title', $slider->title) }}" class="{{ $inputClass }}" required>
            </div>

            <div>
                <label for="sub_title" class="{{ $labelClass }}">النصّ التمهيدي</label>
                <textarea id="sub_title" name="sub_title" rows="3" class="{{ $inputClass }}">{{ old('sub_title', $slider->sub_title) }}</textarea>
            </div>
        </div>

        <hr class="border-hairline">

        {{-- ═══ الأزرار ═══ --}}
        <div class="space-y-5">
            <div>
                <h3 class="{{ $legendClass }}">الأزرار</h3>
                <p class="{{ $hintClass }}">
                    زرّ أساسي واحد لكل شريحة، والثاني يُعرض ثانوياً.
                    النصّ والرابط يُملآن معاً — نصّ بلا رابط يُنتج زرّاً لا يعمل.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="btn1_text" class="{{ $labelClass }}">نصّ الزرّ الأساسي</label>
                    <input type="text" id="btn1_text" name="btn1_text"
                           value="{{ old('btn1_text', $slider->btn1_text) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    {{-- dir=ltr على الحقل وحده: الروابط لاتينية دائماً --}}
                    <label for="btn1_link" class="{{ $labelClass }}">رابط الزرّ الأساسي</label>
                    <input type="text" id="btn1_link" name="btn1_link" dir="ltr" placeholder="/register"
                           value="{{ old('btn1_link', $slider->btn1_link) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="btn2_text" class="{{ $labelClass }}">نصّ الزرّ الثانوي</label>
                    <input type="text" id="btn2_text" name="btn2_text"
                           value="{{ old('btn2_text', $slider->btn2_text) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="btn2_link" class="{{ $labelClass }}">رابط الزرّ الثانوي</label>
                    <input type="text" id="btn2_link" name="btn2_link" dir="ltr" placeholder="#branches"
                           value="{{ old('btn2_link', $slider->btn2_link) }}" class="{{ $inputClass }}">
                </div>
            </div>
        </div>

        <hr class="border-hairline">

        {{-- ═══ الصورة ═══ --}}
        <div class="space-y-5">
            <div>
                <h3 class="{{ $legendClass }}">الصورة</h3>
                <p class="{{ $hintClass }}">
                    JPG أو PNG أو WebP · 4 ميغابايت كحدّ أقصى. رفع صورة جديدة يستبدل الحالية.
                </p>
            </div>

            @if ($slider->image)
                <div class="flex items-center gap-4">
                    <img src="{{ $slider->image->url() }}" alt="{{ $slider->image->alt_text }}"
                         class="h-20 w-32 rounded-lg object-cover ring-1 ring-hairline">
                    <p class="text-micro text-stone">الصورة الحالية</p>
                </div>
            @endif

            <div>
                <label for="image" class="{{ $labelClass }}">
                    ملف الصورة @if ($isNew)<span class="text-error-deep">*</span>@endif
                </label>
                <input type="file" id="image" name="image" accept="image/*"
                       class="{{ $inputClass }} file:me-3 file:rounded-lg file:border-0 file:bg-surface
                              file:px-3 file:py-1 file:text-caption file:font-semibold file:text-ink"
                       @if ($isNew) required @endif>
            </div>

            <div>
                <label for="alt_text" class="{{ $labelClass }}">النصّ البديل</label>
                <input type="text" id="alt_text" name="alt_text"
                       value="{{ old('alt_text', $slider->image?->alt_text) }}" class="{{ $inputClass }}">
                <p class="{{ $hintClass }}">وصف الصورة لقارئ الشاشة ولمن لا تظهر عنده الصورة.</p>
            </div>
        </div>

        <hr class="border-hairline">

        {{-- ═══ العرض ═══ --}}
        <div class="space-y-5">
            <h3 class="{{ $legendClass }}">العرض</h3>

            {{-- الترتيب ومفتاح الظهور في صفّ واحد.
                 items-start + pt على المفتاح يضعانه على خطّ أساس الحقل نفسه:
                 الحقل يحمل تسمية فوقه، فالمحاذاة المركزية كانت ترفع المفتاح. --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:items-start">
                <div>
                    <label for="display_order" class="{{ $labelClass }}">ترتيب العرض</label>
                    <input type="number" id="display_order" name="display_order" min="0" dir="ltr"
                           value="{{ old('display_order', $slider->display_order ?? 0) }}"
                           class="{{ $inputClass }}">
                    <p class="{{ $hintClass }}">الأصغر يظهر أولاً.</p>
                </div>

                {{-- sm:mt-7 يعادل ارتفاع التسمية فوق الحقل المجاور، فيستقرّ
                     المفتاح على نفس خطّ أساس صندوق الإدخال لا فوقه. --}}
                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-hairline
                              bg-surface/40 px-3 py-2.5 text-ui text-ink transition hover:bg-surface
                              sm:mt-7">
                    {{-- 🔴 خانة مخفية بقيمة 0 قبل المفتاح: المتصفّح لا يرسل
                         خانة اختيار غير مؤشّرة إطلاقاً، فبدونها يستحيل إطفاء
                         المفتاح — يبقى الحقل غائباً فتُقرأ القيمة القديمة. --}}
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $slider->is_active ?? true))
                           class="size-4 shrink-0 rounded border-hairline-strong text-accent focus:ring-accent/40">
                    ظاهرة في الصفحة الرئيسية
                </label>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-admin.btn :href="route('admin.sliders.index')" variant="ghost">إلغاء</x-admin.btn>
        <x-admin.btn variant="primary">حفظ</x-admin.btn>
    </div>
</form>
