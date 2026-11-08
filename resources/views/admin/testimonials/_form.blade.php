@php
    /*
     * Testimonial form.
     *
     * Expected variables: $testimonial · $students · $action · $method
     *
     * 🔴 Linking a student is OPTIONAL, and the form says why in the hint
     * rather than hiding it: a linked review reads the name, branch and city
     * live from the account, and an unlinked one uses the typed snapshot.
     * Both are valid, and the snapshot is what a linked review falls back to
     * once the account is gone.
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    $isNew = ! $testimonial->exists;
@endphp

{{-- 🔴 enctype is REQUIRED for the file input. Without it the browser
     posts field names with no file body — Laravel sees no upload, the
     portrait silently never saves, and nothing errors. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6"
      x-data="{ source: '{{ old('avatar_source', $testimonial->avatar_source ?? \App\Models\Testimonial::AVATAR_STUDENT) }}' }">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        {{-- ═══ The reviewer ═══ --}}
        <div class="space-y-5">
            <h3 class="text-ui font-bold text-ink">صاحب الرأي</h3>

            <div>
                <label for="student_id" class="{{ $labelClass }}">حساب الطالب</label>
                <select id="student_id" name="student_id" class="{{ $inputClass }}">
                    <option value="">— بلا حساب (رأي مسجَّل يدوياً) —</option>
                    @foreach ($students as $id => $label)
                        <option value="{{ $id }}"
                            @selected(old('student_id', $testimonial->student_id) == $id)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="{{ $hintClass }}">
                    عند الربط يُقرأ الاسم والفرع والمدينة من حساب الطالب مباشرة.
                    حذف الحساب لاحقاً يفكّ الارتباط ولا يمحو الرأي.
                </p>
                @error('student_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="{{ $labelClass }}">الاسم المعروض</label>
                    <input type="text" id="name" name="name" maxlength="120"
                           value="{{ old('name', $testimonial->name) }}" class="{{ $inputClass }}">
                    {{-- Required only when nothing else can supply a name. --}}
                    <p class="{{ $hintClass }}">مطلوب إن لم يُربط الرأي بحساب. يُستخدم أيضاً كبديل لو حُذف الحساب.</p>
                    @error('name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="title" class="{{ $labelClass }}">السطر التعريفي</label>
                    <input type="text" id="title" name="title" maxlength="160"
                           placeholder="الفرع العلمي · رام الله"
                           value="{{ old('title', $testimonial->title) }}" class="{{ $inputClass }}">
                    <p class="{{ $hintClass }}">بديل يُعرض حين لا يوجد حساب مرتبط.</p>
                    @error('title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ═══ The review ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="text-ui font-bold text-ink">الرأي</h3>

            <div>
                <label for="rate" class="{{ $labelClass }}">
                    التقييم <span class="text-error-deep">*</span>
                </label>
                <select id="rate" name="rate" class="{{ $inputClass }}">
                    @for ($star = \App\Models\Testimonial::MAX_RATE; $star >= \App\Models\Testimonial::MIN_RATE; $star--)
                        <option value="{{ $star }}" @selected(old('rate', $testimonial->rate ?? 5) == $star)>
                            {{ str_repeat('★', $star) }} — {{ $star }}
                        </option>
                    @endfor
                </select>
                {{-- A select, not a number field: the column is an unsigned
                     tiny int that happily stores 255, and the card would then
                     try to draw 255 stars. --}}
                <p class="{{ $hintClass }}">من {{ \App\Models\Testimonial::MIN_RATE }} إلى {{ \App\Models\Testimonial::MAX_RATE }} نجوم.</p>
                @error('rate')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="{{ $labelClass }}">
                    نصّ الرأي <span class="text-error-deep">*</span>
                </label>
                <textarea id="description" name="description" rows="5" maxlength="1000"
                          class="{{ $inputClass }} leading-relaxed">{{ old('description', $testimonial->description) }}</textarea>
                @error('description')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ═══ Portrait ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <div>
                <h3 class="text-ui font-bold text-ink">الصورة</h3>
                <p class="{{ $hintClass }}">الصورة المعروضة بجانب الاقتباس في الصفحة الرئيسية.</p>
            </div>

            {{-- 🔴 An explicit choice, not precedence. Before this column the
                 attached file always won, so an admin who uploaded once could
                 not go back to the account photo without deleting it. --}}
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    \App\Models\Testimonial::AVATAR_STUDENT => ['label' => 'صورة حساب الطالب', 'hint' => 'تتبع صورة الحساب وتتحدّث معها.'],
                    \App\Models\Testimonial::AVATAR_CUSTOM => ['label' => 'صورة مخصّصة', 'hint' => 'صورة تُرفَع لهذا الرأي وحده.'],
                ] as $key => $option)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition"
                           :class="source === '{{ $key }}'
                               ? 'border-accent bg-accent/8'
                               : 'border-hairline hover:border-hairline-strong'">
                        <input type="radio" name="avatar_source" value="{{ $key }}" x-model="source"
                               class="mt-1 size-4 shrink-0 border-hairline-strong text-accent focus:ring-accent/40">
                        <span class="min-w-0">
                            <span class="block text-ui text-ink">{{ $option['label'] }}</span>
                            <span class="mt-0.5 block text-micro text-stone">{{ $option['hint'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('avatar_source')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

            {{-- Only relevant to the custom branch, so it is hidden otherwise
                 rather than sitting there inviting an upload that would not
                 be shown. x-cloak stops it flashing before Alpine boots. --}}
            <div x-show="source === '{{ \App\Models\Testimonial::AVATAR_CUSTOM }}'" x-cloak class="space-y-3">
                @if ($testimonial->exists && $testimonial->photo)
                    {{-- Bounded preview card, both axes pinned: a bare
                         w-full image stretches the whole form panel. --}}
                    <div class="flex items-start gap-4">
                        <div class="size-32 shrink-0 overflow-hidden rounded-lg border border-hairline bg-surface">
                            <img src="{{ $testimonial->photo->url() }}" alt="الصورة الحالية"
                                 loading="lazy" class="size-full object-cover">
                        </div>
                        <p class="pt-1 text-micro text-stone">الصورة الحالية. رفع صورة جديدة يستبدلها.</p>
                    </div>
                @endif

                <div>
                    <label for="photo" class="{{ $labelClass }}">رفع صورة</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"
                           class="{{ $inputClass }} file:me-3 file:rounded-md file:border-0 file:bg-surface
                                  file:px-3 file:py-1.5 file:text-caption file:text-steel">
                    {{-- ⚠️ Leaving this empty keeps the existing picture. An
                         edit to the quote must not wipe the portrait. --}}
                    <p class="{{ $hintClass }}">JPG أو PNG أو WebP · 4 ميغابايت كحدّ أقصى. اتركه فارغاً للإبقاء على الحالية.</p>
                    @error('photo')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ═══ Display ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <h3 class="text-ui font-bold text-ink">العرض</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="display_order" class="{{ $labelClass }}">ترتيب العرض</label>
                    <input type="number" id="display_order" name="display_order" min="0" max="9999"
                           value="{{ old('display_order', $testimonial->display_order) }}"
                           class="{{ $inputClass }} tabular-nums">
                    <p class="{{ $hintClass }}">الأصغر يظهر أولاً.</p>
                    @error('display_order')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-end">
                    <label class="flex cursor-pointer items-center gap-3 pb-2.5">
                        {{-- Hidden 0 first: an unchecked box submits nothing
                             at all, so without it a review could never be
                             taken off the page. --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $testimonial->is_active ?? true))
                               class="size-4 rounded border-hairline-strong text-accent focus:ring-accent/25">
                        <span class="text-ui text-ink">ظاهر في الصفحة الرئيسية</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <x-admin.btn :href="route('admin.testimonials.index')" variant="secondary">إلغاء</x-admin.btn>
        <x-admin.btn variant="primary">{{ $isNew ? 'إضافة الرأي' : 'حفظ التغييرات' }}</x-admin.btn>
    </div>
</form>
