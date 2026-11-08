@php
    /*
     * 🔴 أصناف النغمات مكتوبة حرفياً كاملة — فاحص Tailwind لا ينفّذ PHP،
     * فـ"bg-{$token}/12" لا يولّد أي CSS. فخّ وقع مرّتين في هذا المشروع.
     */
    $tones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag' => 'bg-tag/12 text-tag',
        'amber' => 'bg-amber/12 text-amber-deep',
        'warn' => 'bg-warn/14 text-warn-deep',
    ];
@endphp

<x-layouts.admin title="المواد الدراسية" breadcrumbs="المحتوى الأكاديمي / المواد">

    <x-admin.page-head
        title="المواد الدراسية"
        lede="المادة هي ما يُعلَّق به المعلّم والمحاضرة وملف المكتبة.">
        <x-slot:actions>
            @can('create-subjects')
                <x-admin.btn :href="route('admin.subjects.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    مادة جديدة
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    {{-- ═══ البحث والتصفية ═══

         🔴 نموذج GET واحد يحمل الثلاثة: نماذج منفصلة كانت ستُسقط كلمة البحث
         عند تبديل الفرع، وتُسقط الفرع عند البحث. --}}
    <form method="GET" action="{{ route('admin.subjects.index') }}"
          class="mt-6 flex flex-wrap items-center gap-3">

        <div class="min-w-0 flex-1 sm:max-w-xs">
            <label for="subject-search" class="sr-only">بحث بالاسم</label>
            <input id="subject-search" type="search" name="q" value="{{ $filters['q'] }}"
                   placeholder="ابحث باسم المادة…"
                   class="h-10 w-full rounded-lg border border-hairline bg-canvas px-3 text-ui text-ink
                          transition placeholder:text-muted focus:border-accent focus:outline-none
                          focus:ring-2 focus:ring-accent/25">
        </div>

        <select name="branch_id"
                class="h-10 rounded-lg border border-hairline bg-canvas px-3 text-caption text-ink
                       focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
            <option value="">كل الفروع</option>
            @foreach ($branches as $id => $name)
                <option value="{{ $id }}" @selected($filters['branch_id'] === $id)>{{ $name }}</option>
            @endforeach
        </select>

        <select name="teacher_id"
                class="h-10 rounded-lg border border-hairline bg-canvas px-3 text-caption text-ink
                       focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
            <option value="">كل المعلّمين</option>
            @foreach ($teachers as $id => $name)
                <option value="{{ $id }}" @selected($filters['teacher_id'] === $id)>{{ $name }}</option>
            @endforeach
        </select>

        <x-admin.btn variant="secondary">تصفية</x-admin.btn>

        @if (array_filter($filters))
            <x-admin.btn :href="route('admin.subjects.index')" variant="ghost">مسح</x-admin.btn>
        @endif
    </form>

    @if ($subjects->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="book" class="size-5" />
            </span>
            {{-- الحالة الفارغة تسمّي سببها لا الجدول. --}}
            @if (array_filter($filters))
                <p class="mt-4 text-ui font-semibold text-ink">لا نتائج لهذه التصفية</p>
                <p class="mt-1 text-caption text-stone">جرّب فرعاً أو معلّماً آخر.</p>
            @else
                <p class="mt-4 text-ui font-semibold text-ink">لا مواد بعد</p>
                <p class="mt-1 text-caption text-stone">أضف أول مادة ليبني عليها المعلّمون محاضراتهم.</p>
            @endif
        </div>
    @else
        @php $offset = $subjects->firstItem() - 1; @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'الغلاف', 'width' => 'w-24'],
            ['label' => 'المادة'],
            ['label' => 'الفرع', 'width' => 'w-36'],
            ['label' => 'المعلّمون', 'width' => 'w-40'],
            ['label' => 'المحتوى', 'width' => 'w-28'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($subjects as $subject)
                <tr class="transition hover:bg-surface/60">
                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        @if ($subject->cover_url)
                            <img src="{{ $subject->cover_url }}" alt="" loading="lazy"
                                 class="h-12 w-20 rounded-lg object-cover">
                        @else
                            {{-- بديل الغلاف أيقونة المادة بنغمتها — لا مربّع فارغ. --}}
                            <span class="grid h-12 w-20 place-items-center rounded-lg
                                         {{ $tones[$subject->accent_token] ?? $tones['accent'] }}">
                                <x-icon :name="$subject->icon ?? 'book'" class="size-5" />
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <p class="font-semibold text-ink">{{ $subject->name }}</p>
                        @if (filled($subject->description))
                            <p class="mt-0.5 line-clamp-1 max-w-xs text-micro text-stone">{{ $subject->description }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <span class="rounded-md bg-surface px-2 py-1 text-micro text-stone">
                            {{ $subject->branch?->name ?? '—' }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        @if ($subject->teachers->isEmpty())
                            {{-- 🔴 مادة بلا معلّم حالة تُرى لا تُخفى: بطاقتها
                                 العامة تعرض اسم معلّم فارغاً. --}}
                            <span class="text-micro text-warn-deep">بلا معلّم</span>
                        @else
                            <div class="flex items-center">
                                @foreach ($subject->teachers->take(3) as $teacher)
                                    {{-- كومة صور متداخلة: -ms-2 إزاحة منطقية
                                         تنعكس مع الاتجاه تلقائياً. --}}
                                    <span class="{{ $loop->first ? '' : '-ms-2' }} ring-2 ring-canvas rounded-full">
                                        <x-ui.avatar :src="$teacher->avatarUrl()" :name="$teacher->full_name" size="sm" />
                                    </span>
                                @endforeach

                                @if ($subject->teachers->count() > 3)
                                    <span class="-ms-2 grid size-8 place-items-center rounded-full bg-surface
                                                 text-micro font-semibold text-stone ring-2 ring-canvas">
                                        +{{ $subject->teachers->count() - 3 }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-micro tabular-nums text-stone">
                            {{ $subject->lectures_count }} محاضرة
                        </p>
                        <p class="mt-0.5 text-micro tabular-nums text-stone">
                            {{ $subject->library_files_count }} ملف
                        </p>
                    </td>

                    <td class="px-4 py-3">
                        <x-admin.status-badge :status="$subject->is_published ? 'active' : 'draft'"
                                              :label="$subject->is_published ? 'منشورة' : 'مسودّة'" />
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-subjects')
                                <x-admin.btn variant="secondary" type="submit" form="toggle-{{ $subject->id }}">
                                    {{ $subject->is_published ? 'إخفاء' : 'نشر' }}
                                </x-admin.btn>

                                <x-admin.btn :href="route('admin.subjects.edit', $subject)" variant="secondary">تعديل</x-admin.btn>
                            @endcan

                            @can('view-subjects')
                                <x-admin.btn :href="route('admin.subjects.show', $subject)" variant="ghost">عرض</x-admin.btn>
                            @endcan

                            @can('delete-subjects')
                                <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}"
                                      data-confirm-title="حذف مادة «{{ $subject->name }}»؟"
                                      data-confirm="@if ($subject->lectures_count || $subject->library_files_count)المادة تحتوي محتوى، وسيُرفض الحذف.@elseتنتقل إلى سلّة المحذوفات ويمكن استرجاعها.@endif"
                                      data-confirm-action="نعم، احذفها">
                                    @csrf
                                    @method('DELETE')
                                    <x-admin.btn variant="danger">حذف</x-admin.btn>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        {{-- نماذج التبديل خارج صفوف الجدول: HTML يمنع تداخل النماذج
             والمتصفّح يُسقط الداخلي بصمت. --}}
        @can('edit-subjects')
            @foreach ($subjects as $subject)
                <form id="toggle-{{ $subject->id }}" method="POST" class="hidden"
                      action="{{ route('admin.subjects.toggle', $subject) }}">
                    @csrf
                    @method('PATCH')
                </form>
            @endforeach
        @endcan

        <div class="mt-4">{{ $subjects->links() }}</div>
    @endif

</x-layouts.admin>
