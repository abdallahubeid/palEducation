@php
    /*
     * Shared create/edit form.
     * Expected: $action · $method ('POST'|'PUT') · $subject (Subject|null)
     *           $branches · $teacherOptions · $tones
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    // ⚠️ old() first everywhere: a validation failure on one field must not
    // discard what was typed into the others.
    $assigned = old('teacher_ids', $subject?->teachers->pluck('id')->all() ?? []);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      class="grid gap-6 lg:grid-cols-3">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    {{-- ═══ البيانات ═══ --}}
    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:col-span-2 lg:p-8">

        <div>
            <label for="name" class="{{ $labelClass }}">اسم المادة <span class="text-error-deep">*</span></label>
            <input type="text" id="name" name="name" maxlength="160" required
                   value="{{ old('name', $subject?->name) }}"
                   placeholder="مثال: الرياضيات" class="{{ $inputClass }}">
            @error('name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

            @if ($subject)
                {{-- 🔴 معروض لأنه رابط المادة العام. يتبع الاسم، لكن عند
                     تغيّره فقط — تعديل يصحّح خطأ مطبعياً يجب ألّا يكسر
                     روابط شُورِكت. --}}
                <p class="mt-1.5 text-micro text-stone">
                    الرابط الحالي: <bdi dir="ltr" class="font-latin">{{ $subject->slug }}</bdi>
                </p>
            @endif
        </div>

        <div>
            <label for="branch_id" class="{{ $labelClass }}">الفرع <span class="text-error-deep">*</span></label>
            <select id="branch_id" name="branch_id" required class="{{ $inputClass }}">
                <option value="">— اختر الفرع —</option>
                @foreach ($branches as $id => $name)
                    <option value="{{ $id }}" @selected((int) old('branch_id', $subject?->branch_id) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            {{-- 🔴 subjects.branch_id هو **المسار الوحيد** الذي يصل به الفرع
                 إلى محتواه. نقل مادة بين فرعين ينقل معها كل محاضراتها
                 وملفاتها من طلبة فرع إلى طلبة آخر. --}}
            <p class="mt-1.5 text-micro text-warn-deep">
                تغيير الفرع ينقل كل محاضرات المادة وملفاتها إلى طلبة الفرع الجديد.
            </p>
            @error('branch_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="{{ $labelClass }}">الوصف</label>
            <textarea id="description" name="description" rows="4" maxlength="1000"
                      placeholder="سطران يظهران على بطاقة المادة."
                      class="{{ $inputClass }} leading-relaxed">{{ old('description', $subject?->description) }}</textarea>
            @error('description')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        {{-- ═══ المعلّمون ═══ --}}
        <div>
            <span class="{{ $labelClass }}">المعلّمون المسندون</span>

            @if ($teacherOptions->isEmpty())
                <p class="rounded-xl border border-dashed border-hairline-strong px-4 py-6 text-center text-micro text-stone">
                    لا معلّمون بعد — أنشئ حساب معلّم أولاً من شاشة المستخدمين.
                </p>
            @else
                <div class="max-h-56 space-y-1.5 overflow-y-auto rounded-xl border border-hairline p-3">
                    @foreach ($teacherOptions as $teacher)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5
                                      transition hover:bg-surface has-checked:bg-accent/8">
                            <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}"
                                   @checked(in_array($teacher->id, array_map('intval', (array) $assigned), true))
                                   class="size-4 shrink-0 cursor-pointer rounded border-hairline-strong
                                          text-accent focus:ring-2 focus:ring-accent/25">
                            <span class="text-caption text-ink">{{ $teacher->full_name }}</span>
                        </label>
                    @endforeach
                </div>
                {{-- ⚠️ الأوّل ترتيباً يصير المعلّم الأساسي — هو الاسم الذي
                     تعرضه بطاقة المادة العامة. --}}
                <p class="mt-1.5 text-micro text-stone">
                    الأول في القائمة يصبح المعلّم الأساسي الظاهر على بطاقة المادة.
                </p>
            @endif
            @error('teacher_ids')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            @error('teacher_ids.*')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- ═══ العرض والنشر ═══ --}}
    <aside class="lg:col-span-1">
        <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6">

            <h3 class="text-ui font-bold text-ink">العرض</h3>

            <div>
                <label class="flex cursor-pointer items-start gap-2.5">
                    {{-- 🔴 قيمة 0 مخفية قبل الخانة: الخانة غير المؤشَّرة لا
                         تُرسل شيئاً، وبدونها يصير المفتاح باتجاه واحد. --}}
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" id="is_published" name="is_published" value="1"
                           @checked(old('is_published', $subject?->is_published ?? true))
                           class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-hairline-strong
                                  text-accent focus:ring-2 focus:ring-accent/25">
                    <span class="min-w-0">
                        <span class="block text-caption font-semibold text-ink">منشورة</span>
                        <span class="mt-0.5 block text-micro text-stone">تظهر في صفحة الفرع العامة.</span>
                    </span>
                </label>
            </div>

            <div>
                <label for="accent_token" class="{{ $labelClass }}">النغمة اللونية</label>
                <select id="accent_token" name="accent_token" class="{{ $inputClass }}">
                    @foreach ($tones as $token => $label)
                        <option value="{{ $token }}" @selected(old('accent_token', $subject?->accent_token ?? 'accent') === $token)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('accent_token')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="icon" class="{{ $labelClass }}">الأيقونة</label>
                {{-- ⚠️ قائمة مغلقة لا حقل حرّ: اسم غير موجود في مجموعة x-icon
                     لا يرمي خطأً — يرسم فراغاً. --}}
                <select id="icon" name="icon" class="{{ $inputClass }}">
                    @foreach (['book' => 'كتاب', 'beaker' => 'دورق', 'compass' => 'بوصلة', 'briefcase' => 'حقيبة', 'wrench' => 'مفتاح'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('icon', $subject?->icon ?? 'book') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('icon')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="display_order" class="{{ $labelClass }}">الترتيب</label>
                <input type="number" id="display_order" name="display_order" min="0" max="9999" dir="ltr"
                       value="{{ old('display_order', $subject?->display_order ?? 0) }}"
                       class="{{ $inputClass }} font-latin">
                @error('display_order')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="cover" class="{{ $labelClass }}">صورة الغلاف</label>

                @if ($subject?->cover_url)
                    <div class="mb-3 overflow-hidden rounded-lg border border-hairline">
                        <img src="{{ $subject->cover_url }}" alt="" class="aspect-video w-full object-cover">
                    </div>
                @endif

                <input type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp"
                       class="{{ $inputClass }} file:me-3 file:rounded-lg file:border-0 file:bg-surface
                              file:px-3 file:py-1.5 file:text-caption file:font-semibold file:text-steel">
                <p class="mt-1.5 text-micro text-stone">
                    JPG · PNG · WebP · 4 ميغابايت.
                    @if ($subject?->cover_url) اتركه فارغاً للإبقاء على الصورة الحالية. @endif
                </p>
                @error('cover')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-hairline pt-5">
                <x-admin.btn :href="route('admin.subjects.index')" variant="ghost">إلغاء</x-admin.btn>
                <x-admin.btn variant="primary">{{ $subject ? 'حفظ التغييرات' : 'إضافة المادة' }}</x-admin.btn>
            </div>
        </div>
    </aside>
</form>
