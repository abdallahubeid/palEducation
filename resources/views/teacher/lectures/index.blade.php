@php
    /*
     * 🔴 لا عمود «مشاهدات» هنا. لا يوجد في المشروع جدول تتبّع مشاهدة
     * (lecture_progress لم يُبنَ بعد)، وطباعة «0» لكل محاضرة تُقرأ «لم
     * يشاهدها أحد» لا «لا نقيس بعد» — وهما شيئان مختلفان تماماً.
     *
     * أما عمود الكويز فيبقى: لا جدول quizzes بعد، فـ«ناقص» عبارة **صحيحة**
     * عن كل محاضرة، وهي تُظهر الفجوة الحقيقية بين وعد المنصّة وما بُني منه.
     */
    $statusTabs = [
        'all' => 'الكل',
        'published' => 'منشورة',
        'draft' => 'مسودّة',
    ];

    $statusBadge = [
        'published' => ['tone' => 'active', 'label' => 'منشورة'],
        'draft' => ['tone' => 'draft', 'label' => 'مسودّة'],
        'hidden' => ['tone' => 'hidden', 'label' => 'مخفيّة'],
    ];

    // الفلاتر الحالية تُحمَل مع كل كتابة كي تعود بالمعلّم إلى نفس القائمة.
    $filterState = [
        'filter_subject_id' => $filters['subject_id'],
        'filter_status' => $filters['status'],
        'filter_search' => $filters['search'],
    ];
@endphp

<x-layouts.teacher :title="__('teacher.lectures_title')" :teacher-name="auth()->user()->displayName()">

    {{-- x-data على الجذر: زرّ الترويسة وزرّ الحالة الفارغة يفتحان النافذة
         نفسها، فلا بدّ أن يشتركا في نفس النطاق. --}}
    <div x-data="teacherLectures()"
         data-store-url="{{ route('teacher.lectures.store') }}"
         data-reopen="{{ $errors->any() ? '1' : '0' }}"
         data-reopen-editing="{{ old('_method') === 'PUT' ? '1' : '0' }}"
         data-reopen-action="{{ old('_form_action', route('teacher.lectures.store')) }}"
         {{-- 🔴 القيم تُعاد من old() في PHP لا من x-model: ربط x-model يصفّر
              الحقول عند تركيب المودال، فتضيع محاولة المعلّم بعد خطأ تحقّق. --}}
         data-reopen-form="{{ json_encode([
             'title' => old('title', ''),
             'subject_id' => (string) old('subject_id', ''),
             'video_url' => old('video_url', ''),
             'description' => old('description', ''),
             'unit_name' => old('unit_name', ''),
             'status' => old('status', 'draft'),
         ], JSON_UNESCAPED_UNICODE) }}">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-ink">{{ __('teacher.lectures_title') }}</h1>
                <p class="mt-1 text-ui text-steel">{{ __('teacher.lectures_subtitle') }}</p>
            </div>

            @if ($subjects->isNotEmpty())
                <x-ui.button type="button" @click="openCreate()">
                    <x-icon name="plus" class="size-4" />
                    رفع محاضرة
                </x-ui.button>
            @endif
        </div>

        {{-- ⚠️ محاضرة بلا فصل دراسي مستحيلة: academic_term_id عمود NOT NULL.
             يُقال هنا بوضوح بدل أن يفشل الإدراج بخطأ قاعدة بيانات. --}}
        @if (! $currentTerm)
            <div class="mt-6 rounded-xl border border-warn/30 bg-warn/8 px-4 py-3">
                <p class="text-caption text-warn-deep">
                    لا يوجد فصل دراسي حالي — لا يمكن رفع محاضرات حتى تحدّده الإدارة.
                </p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-error/25 bg-error/8 px-4 py-3">
                <ul class="space-y-1 text-caption text-error-deep">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($subjects->isEmpty())
            {{-- 🔴 بلا مواد مسندة لا معنى لأي شيء في هذه الشاشة: لا جدول
                 يُملأ ولا نموذج يمكن حفظه. --}}
            <div class="tile mt-6">
                <x-ui.empty-state icon="play"
                                  :title="__('teacher.subjects_empty_title')"
                                  :body="__('teacher.subjects_empty_body')" />
            </div>
        @else
            {{-- ═══ البحث والتصفية ═══

                 نموذج GET واحد يحمل الثلاثة: نماذج منفصلة تُسقط كلمة البحث
                 عند تبديل المادة، وتُسقط المادة عند البحث. --}}
            <form method="GET" action="{{ route('teacher.lectures') }}"
                  class="mt-6 flex flex-wrap items-center gap-3">

                <input type="hidden" name="status" value="{{ $filters['status'] }}">

                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label for="lecture-search" class="sr-only">بحث بعنوان المحاضرة</label>
                    <input id="lecture-search" type="search" name="search" value="{{ $filters['search'] }}"
                           placeholder="ابحث بعنوان المحاضرة…"
                           class="h-10 w-full rounded-full border border-hairline bg-canvas px-4 text-ui text-ink
                                  transition placeholder:text-muted focus-visible:border-accent
                                  focus-visible:outline-none">
                </div>

                <select name="subject_id"
                        class="h-10 rounded-full border border-hairline bg-canvas px-4 text-caption text-ink
                               focus-visible:border-accent focus-visible:outline-none">
                    {{-- 🔴 المواد المسندة لهذا المعلّم وحدها: قائمة تعرض كل
                         مواد المنصّة تكشف بنية المحتوى لمن لا يملكه. --}}
                    <option value="">كل موادي</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($filters['subject_id'] === $subject->id)>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>

                <x-ui.button type="submit" variant="secondary" size="sm">تصفية</x-ui.button>

                @if (array_filter($filters, fn ($v) => filled($v) && $v !== 'all'))
                    <x-ui.button variant="ghost" size="sm" :href="route('teacher.lectures')">مسح</x-ui.button>
                @endif
            </form>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($statusTabs as $key => $label)
                    <a href="{{ route('teacher.lectures', array_filter([
                           'status' => $key === 'all' ? null : $key,
                           'subject_id' => $filters['subject_id'],
                           'search' => $filters['search'],
                       ])) }}"
                       @class([
                           'inline-flex min-h-9 items-center rounded-full px-4 text-caption font-semibold transition',
                           'bg-accent text-on-primary' => $filters['status'] === $key,
                           'bg-surface text-steel hover:text-ink' => $filters['status'] !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </div>

            @if ($lectures->isEmpty())
                <div class="tile mt-6">
                    <x-ui.empty-state icon="play"
                                      :title="array_filter($filters, fn ($v) => filled($v) && $v !== 'all')
                                          ? 'لا نتائج لهذه التصفية'
                                          : 'لا محاضرات بعد'"
                                      :body="array_filter($filters, fn ($v) => filled($v) && $v !== 'all')
                                          ? 'جرّب مادة أخرى أو كلمة بحث مختلفة.'
                                          : 'ارفع أول محاضرة برابط يوتيوب، وستظهر هنا فوراً.'" />
                </div>
            @else
                <div class="tile mt-6 overflow-x-auto p-0">
                    <table class="min-w-full divide-y divide-hairline text-ui">
                        <thead class="bg-surface-soft">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start text-caption font-semibold text-stone">المحاضرة</th>
                                <th scope="col" class="w-40 px-4 py-3 text-start text-caption font-semibold text-stone">المادة</th>
                                <th scope="col" class="w-28 px-4 py-3 text-start text-caption font-semibold text-stone">الحالة</th>
                                <th scope="col" class="w-24 px-4 py-3 text-start text-caption font-semibold text-stone">الكويز</th>
                                <th scope="col" class="w-32 px-4 py-3 text-start text-caption font-semibold text-stone">التاريخ</th>
                                <th scope="col" class="px-4 py-3 text-end text-caption font-semibold text-stone">إجراءات</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-hairline">
                            @foreach ($lectures as $lecture)
                                <tr class="transition hover:bg-surface/60">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            {{-- زرّ المعاينة: يفتح المشغّل في مكانه بدل مغادرة القائمة. --}}
                                            {{-- 🔴 يقود إلى شاشة المشاهدة **داخل المنصّة**، لا إلى
                                                 يوتيوب: الطالب والمعلّم يريان الشاشة نفسها، ورابط
                                                 خارجي ينهي الجلسة عندنا ويضع الدرس في صفحة فيها
                                                 اقتراحات وإعلانات لا نتحكّم بها. --}}
                                            <a href="{{ route('student.lectures.show', $lecture) }}"
                                               class="grid size-9 shrink-0 place-items-center rounded-full
                                                      bg-accent/12 text-accent-deep transition hover:bg-accent/20"
                                               aria-label="معاينة {{ $lecture->title }}">
                                                <x-icon name="play" class="size-4" />
                                            </a>

                                            <div class="min-w-0">
                                                <a href="{{ route('student.lectures.show', $lecture) }}"
                                                   class="block truncate font-semibold text-ink transition hover:text-accent-deep">
                                                    {{ $lecture->title }}
                                                </a>
                                                @if (filled($lecture->description))
                                                    <p class="mt-0.5 line-clamp-1 max-w-sm text-micro text-stone">
                                                        {{ $lecture->description }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-surface px-2 py-1 text-micro text-stone">
                                            {{ $lecture->subject?->name ?? '—' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        @php $badge = $statusBadge[$lecture->status->value] ?? $statusBadge['draft']; @endphp
                                        <x-admin.status-badge :status="$badge['tone']" :label="$badge['label']" />
                                    </td>

                                    <td class="px-4 py-3">
                                        {{-- ⚠️ «ناقص» صحيحة عن كل محاضرة اليوم: لا جدول
                                             quizzes بعد. الوعد الأساسي للمنصّة أن كل
                                             محاضرة يتبعها كويز، وإخفاء العمود يُخفي
                                             الفجوة بدل أن يُظهرها. --}}
                                        <span class="rounded-md bg-warn/12 px-2 py-1 text-micro text-warn-deep">ناقص</span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="num text-caption text-stone" dir="ltr">
                                            {{ ($lecture->published_at ?? $lecture->created_at)->format('Y-m-d') }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- ⚠️ يملأ النموذج من بيانات الصفّ نفسه — لا طلب شبكة
                                                 لجلب ما هو معروض أصلاً. ورابط الفيديو يُعاد بناؤه
                                                 من المعرّف المخزَّن، فما يراه المعلّم هو ما سيُحفَظ. --}}
                                            {{-- ⚠️ {{ Js::from() }} لا @js(): الموجّه @js لا يُصرَّف داخل
                                                 سمة على وسم مكوّن (<x-…>) — يُقتطع كنصّ حرفي قبل مرور مصرّف
                                                 الموجّهات، فيصل Alpine تعبيرَ ‎@click‎ نصّاً فيه ‎@‎ ⇒ SyntaxError
                                                 والزرّ ميت بلا أثر. الصدى {{ }} يُصرَّف في سمات المكوّن. --}}
                                            <x-ui.button type="button" variant="ghost" size="sm"
                                                         @click="openEdit({{ \Illuminate\Support\Js::from([
                                                             'id' => $lecture->id,
                                                             'title' => $lecture->title,
                                                             'subject_id' => $lecture->subject_id,
                                                             'video_url' => $lecture->watch_url,
                                                             'description' => $lecture->description,
                                                             'unit_name' => $lecture->unit_name,
                                                             'status' => $lecture->status->value,
                                                             'action' => route('teacher.lectures.update', $lecture),
                                                         ]) }})">
                                                <x-icon name="edit" class="size-4" />
                                                تعديل
                                            </x-ui.button>

                                            <form method="POST"
                                                  action="{{ route('teacher.lectures.destroy', $lecture) }}"
                                                  data-confirm-title="حذف «{{ $lecture->title }}»؟"
                                                  data-confirm="تنتقل إلى سلّة المحذوفات ويمكن استرجاعها."
                                                  data-confirm-action="نعم، احذفها">
                                                @csrf
                                                @method('DELETE')
                                                @foreach ($filterState as $key => $value)
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endforeach
                                                <x-ui.button type="submit" variant="danger" size="sm">حذف</x-ui.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($lectures->hasPages())
                    <div class="mt-4">{{ $lectures->links() }}</div>
                @endif
            @endif
        @endif

        {{-- ═══ نافذة الرفع ═══ --}}
        <div x-show="formModal" x-cloak
             @keydown.escape.window="close()"
             class="fixed inset-0 z-50 grid place-items-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="upload-title">

            <div @click="close()" class="absolute inset-0 bg-ink/60"></div>

            <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden
                        rounded-2xl border border-hairline bg-canvas shadow-xl">

                <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                    <h2 id="upload-title" class="text-ui font-bold text-ink" x-text="editing ? 'تعديل المحاضرة' : 'رفع محاضرة'"></h2>
                    <button type="button" @click="close()"
                            class="cursor-pointer rounded-lg p-1.5 text-stone transition hover:bg-surface"
                            aria-label="إغلاق">
                        <x-icon name="close" class="size-5" />
                    </button>
                </div>

                                {{-- 🔴 نموذج واحد بوجهتين: الإنشاء POST والتعديل PUT عبر
                     حقل _method. نموذجان منفصلان بنفس الحقول ينحرفان — يُضاف
                     حقل لأحدهما ويُنسى في الآخر. --}}
                <form method="POST" :action="action" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    {{-- ⚠️ الوجهة تُرسَل مع النموذج: عند فشل التحقّق تعود
                         الصفحة كاملة، وبدون هذا الحقل لا تعرف الواجهة إن
                         كان ما فشل إنشاءً أم تعديلاً — فتفتح النموذج الخطأ. --}}
                    <input type="hidden" name="_form_action" :value="action">

                    {{-- الفلاتر الحالية تُحمَل كي يعود المعلّم إلى نفس القائمة. --}}
                    @foreach ($filterState as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5">

                        <div>
                            <label for="title" class="mb-1.5 block text-caption font-medium text-steel">
                                عنوان المحاضرة <span class="text-error-deep">*</span>
                            </label>
                            <input type="text" id="title" name="title" maxlength="190" required
                                   x-model="form.title" value="{{ old('title') }}"
                                   placeholder="مثال: قواعد الاشتقاق"
                                   class="h-11 w-full rounded-xl border border-hairline bg-canvas px-3 text-ui text-ink
                                          transition placeholder:text-muted focus-visible:border-accent
                                          focus-visible:outline-none">
                            @error('title')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="subject_id" class="mb-1.5 block text-caption font-medium text-steel">
                                المادة <span class="text-error-deep">*</span>
                            </label>
                            <select id="subject_id" name="subject_id" required x-model="form.subject_id"
                                    class="h-11 w-full rounded-xl border border-hairline bg-canvas px-3 text-ui text-ink
                                           focus-visible:border-accent focus-visible:outline-none">
                                <option value="">— اختر المادة —</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}"
                                        @selected((int) old('subject_id', $filters['subject_id']) === $subject->id)>
                                        {{ $subject->name }}
                                        @if ($subject->branch) — {{ $subject->branch->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="unit_name" class="mb-1.5 block text-caption font-medium text-steel">
                                الوحدة
                            </label>
                            {{-- ⚠️ نصّ حرّ لا قائمة: الوحدات ليست كياناً في
                                 قاعدة البيانات، والمعلّم يكتب العنوان الذي
                                 يجمع محاضراته. المطابقة بالنصّ، فتوحيد
                                 الكتابة يوحّد المجموعة. --}}
                            <input type="text" id="unit_name" name="unit_name" maxlength="160"
                                   x-model="form.unit_name" value="{{ old('unit_name') }}"
                                   list="unit-suggestions"
                                   placeholder="مثال: الوحدة الأولى: أساسيات التفاضل"
                                   class="h-11 w-full rounded-xl border border-hairline bg-canvas px-3 text-ui text-ink
                                          transition placeholder:text-muted focus-visible:border-accent
                                          focus-visible:outline-none">

                            {{-- اقتراحات من الوحدات المستعملة فعلاً: تقلّل
                                 اختلاف الكتابة الذي يشقّ الوحدة الواحدة. --}}
                            <datalist id="unit-suggestions">
                                @foreach ($knownUnits as $unit)
                                    <option value="{{ $unit }}"></option>
                                @endforeach
                            </datalist>

                            <p class="mt-1.5 text-micro text-stone">
                                اتركها فارغة إن لم تكن مادتك مقسّمة إلى وحدات.
                            </p>
                            @error('unit_name')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="video_url" class="mb-1.5 block text-caption font-medium text-steel">
                                رابط الفيديو على يوتيوب <span class="text-error-deep">*</span>
                            </label>
                            {{-- الرابط لاتيني: يُجبَر LTR داخل صفحة RTL. --}}
                            <input type="text" id="video_url" name="video_url" required dir="ltr" maxlength="500"
                                   x-model="form.video_url" value="{{ old('video_url') }}"
                                   placeholder="https://www.youtube.com/watch?v=..."
                                   class="h-11 w-full rounded-xl border border-hairline bg-canvas px-3 font-latin
                                          text-ui text-ink transition placeholder:text-muted
                                          focus-visible:border-accent focus-visible:outline-none">
                            {{-- 🔴 يُقال صراحةً: يوتيوب استضافة عامة، ومن يملك
                                 الرابط يشاهد حتى لو كان الفيديو «غير مدرَج». --}}
                            <p class="mt-1.5 text-micro text-warn-deep">
                                الفيديو على يوتيوب متاح لمن يملك رابطه، حتى لو كان «غير مدرَج».
                            </p>
                            @error('video_url')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="description" class="mb-1.5 block text-caption font-medium text-steel">
                                وصف أو ملاحظات
                            </label>
                            <textarea id="description" name="description" rows="4" maxlength="2000"
                                      placeholder="ما الذي تغطّيه هذه المحاضرة؟"
                                      x-model="form.description"
                                      class="w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui
                                             leading-relaxed text-ink transition placeholder:text-muted
                                             focus-visible:border-accent focus-visible:outline-none">{{ old('description') }}</textarea>
                            @error('description')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                        </div>

                        <fieldset>
                            <legend class="mb-1.5 block text-caption font-medium text-steel">الحالة</legend>

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach (['draft' => 'حفظ كمسودّة', 'published' => 'نشر فوراً'] as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border
                                                  border-hairline px-3 py-2.5 transition hover:bg-surface
                                                  has-checked:border-accent/40 has-checked:bg-accent/8">
                                        <input type="radio" name="status" value="{{ $value }}"
                                               x-model="form.status"
                                               @checked(old('status', 'draft') === $value)
                                               class="size-4 shrink-0 border-hairline-strong text-accent
                                                      focus:ring-2 focus:ring-accent/25">
                                        <span class="text-caption text-ink">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('status')<p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>@enderror
                        </fieldset>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline px-5 py-4">
                        <x-ui.button type="button" variant="ghost" size="sm"
                                     @click="close()">إلغاء</x-ui.button>
                        <x-ui.button type="submit" size="sm" :disabled="! $currentTerm">
                            <span x-text="editing ? 'حفظ التغييرات' : 'حفظ المحاضرة'"></span>
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</x-layouts.teacher>
