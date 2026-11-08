@php
    /*
     * Branch form — one full-width card, same shape as the slider form.
     *
     * Expected variables: $branch · $action · $method
     *
     * 🔴 old() first, then the model value: after a failed validation the
     * fields must come back with what the admin typed, not with what the
     * database still holds.
     *
     * No enctype here — a branch carries no upload. Its card is drawn from
     * an icon NAME and a colour token, both resolved at render time.
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
    $legendClass = 'text-ui font-bold text-ink';

    $isNew = ! $branch->exists;

    $icons = \App\Http\Controllers\Admin\BranchController::ICONS;
    $tones = \App\Http\Controllers\Admin\BranchController::TONES;

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

        {{-- ═══ Identity ═══ --}}
        <div class="space-y-5">
            <h3 class="{{ $legendClass }}">تعريف الفرع</h3>

            <div>
                <label for="name" class="{{ $labelClass }}">اسم الفرع <span class="text-error-deep">*</span></label>
                <input type="text" id="name" name="name" maxlength="100"
                       value="{{ old('name', $branch->name) }}" class="{{ $inputClass }}">
                <p class="{{ $hintClass }}">كما يظهر على البطاقة وفي شاشة اختيار الفرع.</p>
                @error('name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="{{ $labelClass }}">المعرّف في الرابط</label>
                {{-- dir=ltr on the field only: the value is latin, the label
                     is Arabic. --}}
                <input type="text" id="slug" name="slug" dir="ltr" maxlength="100"
                       placeholder="scientific"
                       value="{{ old('slug', $branch->slug) }}" class="{{ $inputClass }}">
                <p class="{{ $hintClass }}">
                    حروف لاتينية وأرقام وشرطات. يُشتقّ من الاسم إن تُرك فارغاً.
                    @unless ($isNew)
                        <strong class="text-warn-deep">تغييره يكسر أي رابط منشور لهذا الفرع.</strong>
                    @endunless
                </p>
                @error('slug')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="{{ $labelClass }}">الوصف</label>
                <textarea id="description" name="description" rows="3" maxlength="500"
                          class="{{ $inputClass }} leading-relaxed">{{ old('description', $branch->description) }}</textarea>
                <p class="{{ $hintClass }}">السطر الذي يظهر داخل البطاقة أسفل الاسم.</p>
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
                         card silently. --}}
                    <select id="icon" name="icon" class="{{ $inputClass }}">
                        @foreach ($icons as $icon)
                            <option value="{{ $icon }}" @selected(old('icon', $branch->icon) === $icon)>
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
                            <option value="{{ $tone }}" @selected(old('accent_token', $branch->accent_token) === $tone)>
                                {{ $toneLabels[$tone] ?? $tone }}
                            </option>
                        @endforeach
                    </select>
                    @error('accent_token')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="btn_text" class="{{ $labelClass }}">نصّ الزرّ</label>
                    <input type="text" id="btn_text" name="btn_text" maxlength="80"
                           value="{{ old('btn_text', $branch->btn_text) }}" class="{{ $inputClass }}">
                    @error('btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="btn_link" class="{{ $labelClass }}">رابط الزرّ</label>
                    <input type="text" id="btn_link" name="btn_link" dir="ltr" maxlength="500"
                           value="{{ old('btn_link', $branch->btn_link) }}" class="{{ $inputClass }}">
                    @error('btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <p class="{{ $hintClass }}">النصّ والرابط يُملآن معاً أو يُتركان فارغين معاً.</p>
        </div>

        {{-- ═══ Display ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="{{ $legendClass }}">العرض</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="display_order" class="{{ $labelClass }}">ترتيب العرض</label>
                    <input type="number" id="display_order" name="display_order" min="0" max="9999"
                           value="{{ old('display_order', $branch->display_order) }}"
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
                               @checked(old('is_active', $branch->is_active ?? true))
                               class="size-4 rounded border-hairline-strong text-accent focus:ring-accent/25">
                        <span class="text-ui text-ink">ظاهر في الصفحة الرئيسية</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <x-admin.btn :href="route('admin.branches.index')" variant="secondary">إلغاء</x-admin.btn>
        <x-admin.btn variant="primary">{{ $isNew ? 'إضافة الفرع' : 'حفظ التغييرات' }}</x-admin.btn>
    </div>
</form>
