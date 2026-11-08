@php
    /*
     * لوح «آلية العمل» — ترويسة القسم + إدارة خطواته.
     *
     * المتغيّرات المتوقّعة: $settings (خريطة مفتاح⇐قيمة) · $action · $steps
     *
     * 🔴 نموذجان منفصلان لا نموذج واحد: الترويسة ثلاثة حقول ثابتة،
     * والخطوات قائمة متغيّرة الطول. دمجهما كان يعني أن تعديل كلمة في
     * خطوة يُرسل القسم كلّه ويكتب فوق كل صفّ فيه.
     */
    $defaults = \App\Models\Setting::HOW_DEFAULTS;

    $value = fn (string $field) => old('how.'.$field, $settings['how.'.$field] ?? $defaults['how.'.$field]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
@endphp

{{-- ═══ ترويسة القسم ═══════════════════════════════════════════ --}}
<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">ترويسة القسم</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان والنصّ التمهيدي فوق الخطوات.
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="how_badge_text" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="how_badge_text" name="how[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('how.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="how_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="how_title" name="how[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('how.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="how_sub_title" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="how_sub_title" name="how[sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            <p class="mt-1.5 text-micro text-stone">اتركه فارغاً لإخفاء السطر من القسم.</p>
            @error('how.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ الترويسة</x-admin.btn>
    </div>
</form>

{{-- ═══ الخطوات ════════════════════════════════════════════════ --}}
@php
    /*
     * 🔴 نموذج مستقلّ لكل خطوة: الحفظ جزئي، فتعديل كلمة في الثانية لا
     * يكتب فوق الأولى والثالثة. ولهذا لا يمكن أن يكون كلّ هذا نموذجاً واحداً.
     *
     * 🔴 الحقول باسم steps[<id>][field] — `old()` نطاقها الطلب لا النموذج،
     * فاسم مسطّح كان يملأ الخطوات الثلاث بمُدخَل الخطوة التي فشل تحقّقها.
     *
     * ⚠️ الترقيم المعروض للزائر (01 · 02 · 03) مشتقّ من موضع الصفّ في
     * القائمة لا من display_order — هذا يرتّب، وذاك يَعُدّ.
     */
    $stepValue = fn (string $key, string $field, $fallback) => old("steps.$key.$field", $fallback);
@endphp

<div class="mt-10 space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

    <div>
        <h3 class="text-ui font-bold text-ink">الخطوات</h3>
        <p class="mt-1.5 text-micro text-stone">
            تظهر بالترتيب أدناه، ويُرقّمها العرض تلقائياً حسب موضعها.
        </p>
    </div>

    @if ($steps->isEmpty())
        <div class="rounded-xl border border-dashed border-hairline-strong px-4 py-8 text-center">
            <p class="text-ui font-semibold text-ink">لا توجد خطوات بعد</p>
            <p class="mt-1.5 text-micro text-stone">أضف أول خطوة من النموذج أدناه لتظهر في الصفحة الرئيسية.</p>
        </div>
    @endif

    @foreach ($steps as $step)
        <div class="rounded-xl border border-hairline bg-ground p-4 sm:p-5">

            {{-- 🔴 نموذج الحذف **شقيق** لا ابن: نموذج داخل نموذج غير صالح
                 في HTML، والمتصفّح يفكّ التداخل بصمت فيضيع أحدهما. --}}
            <div class="mb-4 flex items-center justify-between gap-3">
                <span class="text-caption font-semibold text-steel">
                    الخطوة {{ sprintf('%02d', $loop->iteration) }}
                </span>

                <form method="POST" action="{{ route('admin.settings.steps.destroy', $step) }}"
                      data-confirm-title="حذف «{{ $step->title }}»؟"
                      data-confirm="تنتقل الخطوة إلى سلّة المحذوفات ويمكن استرجاعها."
                      data-confirm-action="نعم، احذفها">
                    @csrf
                    @method('DELETE')
                    <x-admin.btn variant="danger">حذف</x-admin.btn>
                </form>
            </div>

            <form method="POST" action="{{ route('admin.settings.steps.update', $step) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-[1fr_7rem]">
                    <div>
                        <label for="step_{{ $step->id }}_title" class="{{ $labelClass }}">العنوان</label>
                        <input type="text"
                               id="step_{{ $step->id }}_title"
                               name="steps[{{ $step->id }}][title]"
                               maxlength="200"
                               value="{{ $stepValue((string) $step->id, 'title', $step->title) }}"
                               class="{{ $inputClass }}">
                        @error('steps.'.$step->id.'.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="step_{{ $step->id }}_order" class="{{ $labelClass }}">الترتيب</label>
                        <input type="number"
                               id="step_{{ $step->id }}_order"
                               name="steps[{{ $step->id }}][display_order]"
                               min="0" max="65535"
                               value="{{ $stepValue((string) $step->id, 'display_order', $step->display_order) }}"
                               class="{{ $inputClass }} tabular-nums">
                        @error('steps.'.$step->id.'.display_order')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="step_{{ $step->id }}_sub" class="{{ $labelClass }}">الشرح</label>
                    <textarea id="step_{{ $step->id }}_sub"
                              name="steps[{{ $step->id }}][sub_title]"
                              rows="2" maxlength="300"
                              class="{{ $inputClass }} leading-relaxed">{{ $stepValue((string) $step->id, 'sub_title', $step->sub_title) }}</textarea>
                    @error('steps.'.$step->id.'.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end">
                    <x-admin.btn variant="secondary">حفظ الخطوة</x-admin.btn>
                </div>
            </form>
        </div>
    @endforeach

    {{-- ── إضافة خطوة ──────────────────────────────────────── --}}
    <form method="POST" action="{{ route('admin.settings.steps.store') }}"
          class="space-y-4 rounded-xl border border-dashed border-hairline-strong p-4 sm:p-5">
        @csrf

        <h4 class="text-caption font-semibold text-steel">إضافة خطوة</h4>

        <div class="grid gap-4 sm:grid-cols-[1fr_7rem]">
            <div>
                <label for="step_new_title" class="{{ $labelClass }}">العنوان</label>
                <input type="text" id="step_new_title" name="steps[new][title]" maxlength="200"
                       value="{{ $stepValue('new', 'title', null) }}" class="{{ $inputClass }}">
                @error('steps.new.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="step_new_order" class="{{ $labelClass }}">الترتيب</label>
                {{-- الافتراضي بعد آخر خطوة: الإضافة في نهاية التسلسل هي
                     النيّة الغالبة، وصفر يضعها في رأسه بلا قصد. --}}
                <input type="number" id="step_new_order" name="steps[new][display_order]" min="0" max="65535"
                       value="{{ $stepValue('new', 'display_order', $steps->count() + 1) }}"
                       class="{{ $inputClass }} tabular-nums">
                @error('steps.new.display_order')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="step_new_sub" class="{{ $labelClass }}">الشرح</label>
            <textarea id="step_new_sub" name="steps[new][sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $stepValue('new', 'sub_title', null) }}</textarea>
            @error('steps.new.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end">
            <x-admin.btn variant="primary">إضافة</x-admin.btn>
        </div>
    </form>
</div>
