@php
    /*
     * نافذة واحدة للرفع والتعديل.
     *
     * 🔴 نموذجان منفصلان بنفس الحقول ينحرفان: يُضاف حقل لأحدهما ويُنسى في
     * الآخر. الوجهة والطريقة تتبدّلان، والحقول واحدة.
     */
    $inputClass = 'h-11 w-full rounded-xl border border-hairline bg-canvas px-3 text-ui text-ink
                   transition placeholder:text-muted focus-visible:border-accent focus-visible:outline-none';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
@endphp

<div x-show="formModal" x-cloak
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 grid place-items-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="library-form-title">

    <div @click="close()" class="absolute inset-0 bg-ink/60"></div>

    <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden
                rounded-2xl border border-hairline bg-canvas shadow-xl">

        <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
            <h2 id="library-form-title" class="text-ui font-bold text-ink"
                x-text="editing ? 'تعديل الملف' : 'رفع ملف جديد'"></h2>

            <button type="button" @click="close()"
                    class="cursor-pointer rounded-lg p-1.5 text-stone transition hover:bg-surface"
                    aria-label="إغلاق">
                <x-icon name="close" class="size-5" />
            </button>
        </div>

        <form method="POST" :action="action" enctype="multipart/form-data"
              class="flex min-h-0 flex-1 flex-col">
            @csrf

            <template x-if="editing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            {{-- ⚠️ الوجهة تُرسَل مع النموذج: عند فشل التحقّق تعود الصفحة كاملة،
                 وبدون هذا الحقل لا تعرف الواجهة أكان الفاشل تعديلاً أم رفعاً
                 فتفتح النموذج الخطأ على مسار الإنشاء. --}}
            <input type="hidden" name="_form_action" :value="action">

            @foreach ($filterState as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5">

                {{-- ⚠️ المكوّن القائم يُعاد استعماله كما هو — منطقة السحب
                     والإفلات <label> فالنقر في أي مكان يفتح المنتقي،
                     والتركيز بلوحة المفاتيح يصل للحقل نفسه. --}}
                <div>
                    <span class="{{ $labelClass }}">
                        الملف
                        <template x-if="! editing"><span class="text-error-deep">*</span></template>
                    </span>

                    <x-ui.file-dropzone
                        name="file"
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.zip"
                        icon="folder"
                        title="اسحب الملف هنا أو اضغط للاختيار"
                        hint="PDF · Word · PowerPoint · ZIP"
                        max-label="20 ميغابايت كحدّ أقصى" />

                    {{-- عند التعديل الملف اختياري: النموذج يُرسَل غالباً
                         لتصحيح عنوان أو تبديل فئة. --}}
                    <p class="mt-1.5 text-micro text-stone" x-show="editing" x-cloak>
                        اتركه فارغاً للإبقاء على الملف الحالي.
                    </p>

                    @error('file')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lib_title" class="{{ $labelClass }}">
                        العنوان <span class="text-error-deep">*</span>
                    </label>
                    <input type="text" id="lib_title" name="title" maxlength="190" required
                           x-model="form.title" value="{{ old('title') }}"
                           placeholder="مثال: امتحان الفصل الأول 2025"
                           class="{{ $inputClass }}">
                    @error('title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lib_subject" class="{{ $labelClass }}">
                        المادة <span class="text-error-deep">*</span>
                    </label>
                    <select id="lib_subject" name="subject_id" required x-model="form.subject_id"
                            class="{{ $inputClass }}">
                        <option value="">— اختر المادة —</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->name }}@if ($subject->branch) — {{ $subject->branch->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <fieldset>
                    <legend class="{{ $labelClass }}">الفئة <span class="text-error-deep">*</span></legend>

                    {{-- 🔴 أزرار لا قائمة: أربع فئات فقط، وإخفاؤها خلف نقرة
                         بلا داعٍ. والفئة نيّة تعليمية لا صيغة ملف. --}}
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($categories as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-hairline
                                          px-3 py-2.5 transition hover:bg-surface
                                          has-checked:border-accent/40 has-checked:bg-accent/8">
                                <input type="radio" name="category" value="{{ $value }}"
                                       x-model="form.category"
                                       class="size-4 shrink-0 border-hairline-strong text-accent
                                              focus:ring-2 focus:ring-accent/25">
                                <span class="text-caption text-ink">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('category')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </fieldset>

                <div>
                    <label for="lib_unit" class="{{ $labelClass }}">الوحدة</label>
                    {{-- نصّ حرّ باقتراحات — نفس اصطلاح lectures.unit_name.
                         المطابقة بالنصّ، فالاقتراح يقلّل اختلاف الكتابة الذي
                         يشقّ الوحدة الواحدة إلى اثنتين. --}}
                    <input type="text" id="lib_unit" name="unit_name" maxlength="160"
                           x-model="form.unit_name" value="{{ old('unit_name') }}"
                           list="library-unit-suggestions"
                           placeholder="مثال: الوحدة الأولى: أساسيات التفاضل"
                           class="{{ $inputClass }}">

                    <datalist id="library-unit-suggestions">
                        @foreach ($knownUnits as $unit)
                            <option value="{{ $unit }}"></option>
                        @endforeach
                    </datalist>

                    <p class="mt-1.5 text-micro text-stone">
                        اتركها فارغة، أو اربط الملف بمحاضرة ليرث وحدتها.
                    </p>
                    @error('unit_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lib_lecture" class="{{ $labelClass }}">ربط بمحاضرة</label>
                    {{-- ⚠️ اختياري، ويجب أن يكون من **نفس المادة** — الخادم
                         يُسقط الربط المخالف بدل أن يقبله. --}}
                    <select id="lib_lecture" name="lecture_id" x-model="form.lecture_id"
                            class="{{ $inputClass }}">
                        <option value="">— بلا ربط —</option>
                        @foreach ($lectures as $lectureOption)
                            <option value="{{ $lectureOption->id }}"
                                    x-show="! form.subject_id || form.subject_id == '{{ $lectureOption->subject_id }}'">
                                {{ $lectureOption->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('lecture_id')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="lib_pages" class="{{ $labelClass }}">عدد الصفحات</label>
                    <input type="number" id="lib_pages" name="page_count" min="1" max="5000" dir="ltr"
                           x-model="form.page_count" value="{{ old('page_count') }}"
                           class="{{ $inputClass }} font-latin">
                    {{-- ⚠️ اختياري ولا يُخترع: «12 صفحة» يقرّر للطالب أكثر من
                         الحجم، لكن رقماً مخترعاً أسوأ من غيابه. --}}
                    <p class="mt-1.5 text-micro text-stone">اختياري — يظهر للطالب على البطاقة.</p>
                    @error('page_count')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <label class="flex cursor-pointer items-start gap-2.5">
                    {{-- 🔴 قيمة 0 مخفية قبل الخانة: الخانة غير المؤشَّرة لا
                         تُرسل شيئاً، وبدونها يصير النشر باتجاه واحد. --}}
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" x-model="form.is_published"
                           class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-hairline-strong
                                  text-accent focus:ring-2 focus:ring-accent/25">
                    <span class="min-w-0">
                        <span class="block text-caption font-semibold text-ink">منشور</span>
                        <span class="mt-0.5 block text-micro text-stone">يظهر لطلبة الفرع في مكتبتهم.</span>
                    </span>
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-hairline px-5 py-4">
                <x-ui.button type="button" variant="ghost" size="sm" @click="close()">إلغاء</x-ui.button>
                <x-ui.button type="submit" size="sm">
                    <span x-text="editing ? 'حفظ التغييرات' : 'رفع الملف'"></span>
                </x-ui.button>
            </div>
        </form>
    </div>
</div>
