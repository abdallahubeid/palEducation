@php
    /*
     * Pricing plan form — one full-width card, same shape as the branch and
     * feature forms.
     *
     * Expected variables: $plan · $action · $method
     *
     * 🔴 old() first, then the model value: after a failed validation the
     * fields must come back with what the admin typed, not with what the
     * database still holds.
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
    $legendClass = 'text-ui font-bold text-ink';

    $isNew = ! $plan->exists;

    // Stored as a JSON array, edited as one bullet per line.
    $featuresText = old(
        'features',
        \App\Http\Controllers\Admin\PricingPlanController::featuresToText($plan->features),
    );
@endphp

<form method="POST" action="{{ $action }}" class="mt-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        {{-- ═══ Identity ═══ --}}
        <div class="space-y-5">
            <h3 class="{{ $legendClass }}">تعريف الخطة</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="{{ $labelClass }}">اسم الخطة <span class="text-error-deep">*</span></label>
                    <input type="text" id="name" name="name" maxlength="120"
                           value="{{ old('name', $plan->name) }}" class="{{ $inputClass }}">
                    @error('name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="badge" class="{{ $labelClass }}">السطر التعريفي</label>
                    <input type="text" id="badge" name="badge" maxlength="200"
                           value="{{ old('badge', $plan->badge) }}" class="{{ $inputClass }}">
                    <p class="{{ $hintClass }}">يظهر أسفل اسم الخطة مباشرة.</p>
                    @error('badge')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="highlight_badge" class="{{ $labelClass }}">شارة التمييز</label>
                <input type="text" id="highlight_badge" name="highlight_badge" maxlength="80"
                       placeholder="الأكثر اختياراً"
                       value="{{ old('highlight_badge', $plan->highlight_badge) }}" class="{{ $inputClass }}">
                {{-- Nothing enforces uniqueness: two ribboned plans render two
                     ribbons and the emphasis cancels out. --}}
                <p class="{{ $hintClass }}">اتركها فارغة لخطة عادية. الشريط المميّز يُفترض أن يحمله خطّة واحدة فقط.</p>
                @error('highlight_badge')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ═══ Price ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="{{ $legendClass }}">السعر</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="price" class="{{ $labelClass }}">السعر بالشيكل <span class="text-error-deep">*</span></label>
                    {{-- Whole shekels only — the column is an unsigned int, so
                         a fraction would be truncated on write with no error. --}}
                    <input type="number" id="price" name="price" min="0" max="999999" step="1"
                           value="{{ old('price', $plan->price ?? 0) }}"
                           class="{{ $inputClass }} tabular-nums" dir="ltr">
                    <p class="{{ $hintClass }}">عدد صحيح بلا كسور. اكتب 0 للخطة المجانية.</p>
                    @error('price')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="period" class="{{ $labelClass }}">وحدة المدّة</label>
                    <input type="text" id="period" name="period" maxlength="60"
                           placeholder="/ للفصل"
                           value="{{ old('period', $plan->period) }}" class="{{ $inputClass }}">
                    <p class="{{ $hintClass }}">تظهر بجانب الرقم: «مجاناً» · «/ للفصل» · «/ للسنة».</p>
                    @error('period')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ═══ Feature bullets ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="{{ $legendClass }}">مزايا الخطة</h3>

            <div>
                <label for="features" class="{{ $labelClass }}">الميزات — ميزة في كل سطر</label>
                {{-- 🔴 A textarea rather than repeating inputs: the list has no
                     identity or behaviour of its own, repeating rows would need
                     add/remove JavaScript to edit three sentences, and pasting
                     a list from a document just works.

                     Blank lines are stripped on save — a trailing newline is
                     the normal way to leave a textarea and would otherwise
                     store an empty bullet that renders as a lone tick. --}}
                <textarea id="features" name="features" rows="6" maxlength="2000"
                          class="{{ $inputClass }} leading-relaxed"
                          placeholder="مواد فرعك كاملة دون استثناء&#10;جميع المحاضرات والاختبارات">{{ $featuresText }}</textarea>
                <p class="{{ $hintClass }}">تُعرَض كقائمة بعلامات صحّ. الأسطر الفارغة تُهمَل.</p>
                @error('features')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ═══ Call to action ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="{{ $legendClass }}">زرّ الخطة</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="btn_text" class="{{ $labelClass }}">نصّ الزرّ</label>
                    <input type="text" id="btn_text" name="btn_text" maxlength="80"
                           value="{{ old('btn_text', $plan->btn_text) }}" class="{{ $inputClass }}">
                    @error('btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="btn_link" class="{{ $labelClass }}">وجهة الزرّ</label>
                    {{-- type=text not url: /register and #compare are valid
                         targets the browser validator rejects. --}}
                    <input type="text" id="btn_link" name="btn_link" dir="ltr" maxlength="500"
                           placeholder="/register"
                           value="{{ old('btn_link', $plan->btn_link) }}" class="{{ $inputClass }}">
                    @error('btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <p class="{{ $hintClass }}">النصّ والوجهة يُملآن معاً أو يُتركان فارغين معاً.</p>
        </div>

        {{-- ═══ Display ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="{{ $legendClass }}">العرض</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="display_order" class="{{ $labelClass }}">ترتيب العرض</label>
                    <input type="number" id="display_order" name="display_order" min="0" max="9999"
                           value="{{ old('display_order', $plan->display_order) }}"
                           class="{{ $inputClass }} tabular-nums">
                    <p class="{{ $hintClass }}">الأصغر يظهر أولاً.</p>
                    @error('display_order')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-end">
                    <label class="flex cursor-pointer items-center gap-3 pb-2.5">
                        {{-- The hidden 0 makes an unchecked box submit a value:
                             an unchecked checkbox sends nothing at all. --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $plan->is_active ?? true))
                               class="size-4 rounded border-hairline-strong text-accent focus:ring-accent/25">
                        <span class="text-ui text-ink">ظاهرة في قسم الأسعار</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <x-admin.btn :href="route('admin.pricing.index')" variant="secondary">إلغاء</x-admin.btn>
        <x-admin.btn variant="primary">{{ $isNew ? 'إضافة الخطة' : 'حفظ التغييرات' }}</x-admin.btn>
    </div>
</form>
