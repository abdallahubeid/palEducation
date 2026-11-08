@php
    /*
     * Feature form — one full-width card, same shape as the branch form.
     *
     * Expected variables: $feature · $action · $method
     *
     * 🔴 old() first, then the model value: after a failed validation the
     * fields must come back with what the admin typed, not with what the
     * database still holds.
     *
     * No enctype — a feature carries no upload. Its card is drawn from an
     * icon NAME and a colour token, both resolved at render time.
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
    $legendClass = 'text-ui font-bold text-ink';

    $isNew = ! $feature->exists;

    $icons = \App\Http\Controllers\Admin\FeatureController::ICONS;
    $tones = \App\Http\Controllers\Admin\FeatureController::TONES;

    $toneLabels = [
        'accent' => 'بنفسجي',
        'tag' => 'أزرق',
        'amber' => 'كهرماني',
        'warn' => 'برتقالي',
    ];
@endphp

<form method="POST" action="{{ $action }}" class="mt-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        {{-- ═══ Content ═══ --}}
        <div class="space-y-5">
            <h3 class="{{ $legendClass }}">المحتوى</h3>

            <div>
                <label for="title" class="{{ $labelClass }}">العنوان <span class="text-error-deep">*</span></label>
                <input type="text" id="title" name="title" maxlength="200"
                       value="{{ old('title', $feature->title) }}" class="{{ $inputClass }}">
                @error('title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="{{ $labelClass }}">الشرح</label>
                {{-- Three rows: Arabic body text sits at line-height ≥1.75,
                     so a single-line field crops the sentence and forces
                     horizontal scrolling while typing. --}}
                <textarea id="description" name="description" rows="3" maxlength="500"
                          class="{{ $inputClass }} leading-relaxed">{{ old('description', $feature->description) }}</textarea>
                <p class="{{ $hintClass }}">يظهر داخل البطاقة أسفل العنوان.</p>
                @error('description')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ═══ Card appearance ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="{{ $legendClass }}">مظهر البطاقة</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="icon" class="{{ $labelClass }}">الأيقونة</label>
                    {{-- A closed list, not free text: the column stores a
                         COMPONENT NAME, and an unknown one renders an empty
                         chip silently. --}}
                    <select id="icon" name="icon" class="{{ $inputClass }}">
                        @foreach ($icons as $icon)
                            <option value="{{ $icon }}" @selected(old('icon', $feature->icon) === $icon)>
                                {{ $icon }}
                            </option>
                        @endforeach
                    </select>
                    @error('icon')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="accent_token" class="{{ $labelClass }}">لون البطاقة</label>
                    <select id="accent_token" name="accent_token" class="{{ $inputClass }}">
                        @foreach ($tones as $tone)
                            <option value="{{ $tone }}" @selected(old('accent_token', $feature->accent_token) === $tone)>
                                {{ $toneLabels[$tone] ?? $tone }}
                            </option>
                        @endforeach
                    </select>
                    @error('accent_token')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="btn_text" class="{{ $labelClass }}">نصّ الرابط</label>
                    <input type="text" id="btn_text" name="btn_text" maxlength="80"
                           value="{{ old('btn_text', $feature->btn_text) }}" class="{{ $inputClass }}">
                    @error('btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="btn_link" class="{{ $labelClass }}">وجهة الرابط</label>
                    {{-- type=text not url: /register and #pricing are valid
                         targets the browser validator rejects. --}}
                    <input type="text" id="btn_link" name="btn_link" dir="ltr" maxlength="500"
                           placeholder="/register"
                           value="{{ old('btn_link', $feature->btn_link) }}" class="{{ $inputClass }}">
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
                           value="{{ old('display_order', $feature->display_order) }}"
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
                               @checked(old('is_active', $feature->is_active ?? true))
                               class="size-4 rounded border-hairline-strong text-accent focus:ring-accent/25">
                        <span class="text-ui text-ink">ظاهرة في الصفحة الرئيسية</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <x-admin.btn :href="route('admin.features.index')" variant="secondary">إلغاء</x-admin.btn>
        <x-admin.btn variant="primary">{{ $isNew ? 'إضافة الميزة' : 'حفظ التغييرات' }}</x-admin.btn>
    </div>
</form>
