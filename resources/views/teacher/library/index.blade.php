@php
    /*
     * 🔴 لا رابط ملف في هذه الصفحة إطلاقاً. كل فتح يمرّ من
     * teacher.library.download الذي يفحص الملكية أولاً — والملف نفسه على
     * القرص الخاص، فـMedia::url() يُرجع له null عمداً.
     */
    $filterState = [
        'filter_subject_id' => $filters['subject_id'],
        'filter_category' => $filters['category'],
        'filter_search' => $filters['search'],
    ];

    // أيقونة النوع تُشتق من mime — لا عمود ثانٍ يحمل ما هو معروف أصلاً.
    $iconFor = function (?string $mime): string {
        return match (true) {
            $mime === 'application/pdf' => 'clipboard',
            str_contains((string) $mime, 'word') => 'book',
            str_contains((string) $mime, 'presentation') => 'play',
            str_contains((string) $mime, 'zip') => 'folder',
            default => 'folder',
        };
    };
@endphp

<x-layouts.teacher :title="__('teacher.library_title')" :teacher-name="auth()->user()->displayName()">

    {{-- نموذج أُعيد بأخطاء: يُفتح مباشرةً على ما كان يُحرَّر. old('_method')
         يميّز تعديلاً فاشلاً من رفعٍ فاشل، و old('_form_action') يعيد الوجهة
         الصحيحة (PUT على الملف) بدل السقوط إلى مسار الإنشاء — نفس عقد
         teacher/lectures. --}}
    <div x-data="teacherLibrary()"
         data-store-url="{{ route('teacher.library.store') }}"
         data-reopen="{{ $errors->any() ? '1' : '0' }}"
         data-reopen-editing="{{ old('_method') === 'PUT' ? '1' : '0' }}"
         data-reopen-action="{{ old('_form_action', route('teacher.library.store')) }}"
         {{-- 🔴 القيم تُعاد من old() في PHP لا من x-model: ربط x-model يصفّر
              الحقول عند تركيب المودال، فتضيع محاولة المعلّم بعد خطأ تحقّق. --}}
         data-reopen-form="{{ json_encode([
             'title' => old('title', ''),
             'subject_id' => (string) old('subject_id', ''),
             'lecture_id' => old('lecture_id') ? (string) old('lecture_id') : '',
             'category' => old('category', 'summary'),
             'unit_name' => old('unit_name', ''),
             'page_count' => (string) old('page_count', ''),
             'is_published' => (bool) old('is_published'),
         ], JSON_UNESCAPED_UNICODE) }}">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-ink">{{ __('teacher.library_title') }}</h1>
                <p class="mt-1 text-ui text-steel">ملخّصات وامتحانات وأوراق عمل موادك المسندة.</p>
            </div>

            @if ($subjects->isNotEmpty())
                <x-ui.button type="button" @click="openCreate()">
                    <x-icon name="plus" class="size-4" />
                    رفع ملف جديد
                </x-ui.button>
            @endif
        </div>

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
            {{-- بلا مواد مسندة لا يوجد ما يُرفع إليه أصلاً. --}}
            <div class="tile mt-6">
                <x-ui.empty-state icon="folder"
                                  :title="__('teacher.subjects_empty_title')"
                                  :body="__('teacher.subjects_empty_body')" />
            </div>
        @else
            {{-- ═══ البحث والتصفية ═══
                 نموذج GET واحد يحمل الثلاثة: نماذج منفصلة تُسقط كلمة البحث
                 عند تبديل المادة، وتُسقط المادة عند البحث. --}}
            <form method="GET" action="{{ route('teacher.library') }}"
                  class="mt-6 flex flex-wrap items-center gap-3">

                <input type="hidden" name="category" value="{{ $filters['category'] }}">

                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label for="library-search" class="sr-only">بحث بعنوان الملف</label>
                    <input id="library-search" type="search" name="search" value="{{ $filters['search'] }}"
                           placeholder="ابحث بعنوان الملف…"
                           class="h-10 w-full rounded-full border border-hairline bg-canvas px-4 text-ui text-ink
                                  transition placeholder:text-muted focus-visible:border-accent
                                  focus-visible:outline-none">
                </div>

                <select name="subject_id"
                        class="h-10 rounded-full border border-hairline bg-canvas px-4 text-caption text-ink
                               focus-visible:border-accent focus-visible:outline-none">
                    {{-- 🔴 مواد هذا المعلّم وحدها: قائمة بكل مواد المنصّة
                         تكشف بنية المحتوى لمن لا يملكه. --}}
                    <option value="">كل موادي</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($filters['subject_id'] === $subject->id)>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>

                <x-ui.button type="submit" variant="secondary" size="sm">تصفية</x-ui.button>

                @if (array_filter($filters, fn ($v) => filled($v) && $v !== 'all'))
                    <x-ui.button variant="ghost" size="sm" :href="route('teacher.library')">مسح</x-ui.button>
                @endif
            </form>

            {{-- أزرار الفئات: أربع فقط، والقائمة المنسدلة تُخفيها بلا داعٍ. --}}
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (['all' => 'الكل'] + $categories as $key => $label)
                    <a href="{{ route('teacher.library', array_filter([
                           'category' => $key === 'all' ? null : $key,
                           'subject_id' => $filters['subject_id'],
                           'search' => $filters['search'],
                       ])) }}"
                       @class([
                           'inline-flex min-h-9 items-center rounded-full px-4 text-caption font-semibold transition',
                           'bg-accent text-on-primary' => $filters['category'] === $key,
                           'bg-surface text-steel hover:text-ink' => $filters['category'] !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </div>

            @if ($files->isEmpty())
                <div class="tile mt-6">
                    <x-ui.empty-state icon="folder"
                                      :title="array_filter($filters, fn ($v) => filled($v) && $v !== 'all')
                                          ? 'لا نتائج لهذه التصفية'
                                          : 'لا ملفات بعد'"
                                      :body="array_filter($filters, fn ($v) => filled($v) && $v !== 'all')
                                          ? 'جرّب مادة أخرى أو فئة مختلفة.'
                                          : 'ارفع أول ملف لمادتك — ملخّصاً أو امتحاناً سابقاً.'" />
                </div>
            @else
                <div class="tile mt-6 overflow-x-auto p-0">
                    <table class="min-w-full divide-y divide-hairline text-ui">
                        <thead class="bg-surface-soft">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start text-caption font-semibold text-stone">الملف</th>
                                <th scope="col" class="w-44 px-4 py-3 text-start text-caption font-semibold text-stone">المادة / الوحدة</th>
                                <th scope="col" class="w-28 px-4 py-3 text-start text-caption font-semibold text-stone">الفئة</th>
                                <th scope="col" class="w-24 px-4 py-3 text-start text-caption font-semibold text-stone">الحجم</th>
                                <th scope="col" class="w-24 px-4 py-3 text-start text-caption font-semibold text-stone">التنزيلات</th>
                                <th scope="col" class="w-28 px-4 py-3 text-start text-caption font-semibold text-stone">الحالة</th>
                                <th scope="col" class="px-4 py-3 text-end text-caption font-semibold text-stone">إجراءات</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-hairline">
                            @foreach ($files as $file)
                                <tr class="transition hover:bg-surface/60">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                                                <x-icon :name="$iconFor($file->media?->mime_type)" class="size-4" />
                                            </span>

                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-ink">{{ $file->title }}</p>
                                                @if ($file->media?->original_filename)
                                                    {{-- الاسم الأصلي لاتيني غالباً: يُعزَل أو ينقلب ترتيبه. --}}
                                                    <p class="mt-0.5 truncate text-micro text-stone">
                                                        <bdi dir="ltr">{{ $file->media->original_filename }}</bdi>
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-surface px-2 py-1 text-micro text-stone">
                                            {{ $file->subject?->name ?? '—' }}
                                        </span>

                                        @if ($file->unitLabel())
                                            <p class="mt-1 truncate text-micro text-stone">{{ $file->unitLabel() }}</p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="rounded-md px-2 py-1 text-micro font-semibold {{ $file->category->badge() }}">
                                            {{ $file->category->label() }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="num text-caption text-stone">{{ $file->humanSize() }}</span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="num text-caption text-stone">{{ $file->download_count }}</span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <x-admin.status-badge :status="$file->is_published ? 'active' : 'draft'"
                                                              :label="$file->is_published ? 'منشور' : 'مسودّة'" />
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- ⚠️ يمرّ من مسارنا لا من رابط ملف: القرص خاص،
                                                 والفحص في الـcontroller. --}}
                                            <x-ui.button variant="ghost" size="sm"
                                                         :href="route('teacher.library.download', $file)"
                                                         target="_blank" rel="noopener">فتح</x-ui.button>

                                            {{-- ⚠️ {{ Js::from() }} لا @js(): الموجّه @js لا يُصرَّف داخل
                                                 سمة على وسم مكوّن (<x-…>) — يُقتطع كنصّ حرفي قبل مرور مصرّف
                                                 الموجّهات، فيصل Alpine تعبيرَ ‎@click‎ نصّاً فيه ‎@‎ ⇒ SyntaxError
                                                 والزرّ ميت بلا أثر. الصدى {{ }} يُصرَّف في سمات المكوّن. --}}
                                            <x-ui.button type="button" variant="ghost" size="sm"
                                                         @click="openEdit({{ \Illuminate\Support\Js::from([
                                                             'title' => $file->title,
                                                             'subject_id' => $file->subject_id,
                                                             'lecture_id' => $file->lecture_id,
                                                             'category' => $file->category->value,
                                                             'unit_name' => $file->unit_name,
                                                             'page_count' => $file->page_count,
                                                             'is_published' => (bool) $file->is_published,
                                                             'action' => route('teacher.library.update', $file),
                                                         ]) }})">
                                                <x-icon name="edit" class="size-4" />
                                                تعديل
                                            </x-ui.button>

                                            <form method="POST" action="{{ route('teacher.library.destroy', $file) }}"
                                                  data-confirm-title="حذف «{{ $file->title }}»؟"
                                                  data-confirm="ينتقل إلى سلّة المحذوفات ويمكن استرجاعه."
                                                  data-confirm-action="نعم، احذفه">
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

                @if ($files->hasPages())
                    <div class="mt-4">{{ $files->links() }}</div>
                @endif
            @endif
        @endif

        @include('teacher.library._form-modal', [
            'subjects' => $subjects,
            'categories' => $categories,
            'knownUnits' => $knownUnits,
            'lectures' => $lectures,
            'filterState' => $filterState,
        ])
    </div>

</x-layouts.teacher>
