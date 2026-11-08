@php
    $tones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag' => 'bg-tag/12 text-tag',
        'amber' => 'bg-amber/12 text-amber-deep',
        'warn' => 'bg-warn/14 text-warn-deep',
    ];
@endphp

<x-layouts.admin :title="$subject->name" breadcrumbs="المحتوى الأكاديمي / المواد / عرض">

    <x-admin.page-head :title="$subject->name" :lede="$subject->branch?->name">
        <x-slot:actions>
            @can('edit-subjects')
                <x-admin.btn :href="route('admin.subjects.edit', $subject)" variant="primary">تعديل</x-admin.btn>
            @endcan

            <x-admin.btn :href="route('admin.subjects.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">

        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-xl border border-hairline bg-canvas">
                @if ($subject->cover_url)
                    <img src="{{ $subject->cover_url }}" alt="" class="aspect-video w-full object-cover">
                @else
                    <div class="grid aspect-video w-full place-items-center
                                {{ $tones[$subject->accent_token] ?? $tones['accent'] }}">
                        <x-icon :name="$subject->icon ?? 'book'" class="size-12" />
                    </div>
                @endif

                <div class="p-5 sm:p-6">
                    <h3 class="text-ui font-bold text-ink">الوصف</h3>

                    @if (filled($subject->description))
                        <p class="mt-3 text-body leading-[1.9] text-steel">{{ $subject->description }}</p>
                    @else
                        <p class="mt-3 text-caption text-stone">لا وصف — بطاقة المادة ستعرض اسمها وحده.</p>
                    @endif
                </div>
            </div>

            {{-- ═══ المعلّمون ═══ --}}
            <div class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
                <h3 class="text-ui font-bold text-ink">المعلّمون المسندون</h3>

                @if ($subject->teachers->isEmpty())
                    {{-- 🔴 حالة تُرى لا تُخفى: بطاقة المادة العامة ستعرض
                         خانة معلّم فارغة. --}}
                    <p class="mt-3 rounded-lg bg-warn/10 px-3 py-2 text-caption text-warn-deep">
                        لا معلّم مسند — بطاقة المادة العامة ستظهر بلا اسم معلّم.
                    </p>
                @else
                    <ul class="mt-4 divide-y divide-hairline">
                        @foreach ($subject->teachers as $teacher)
                            <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                <x-ui.avatar :src="$teacher->avatarUrl()" :name="$teacher->full_name" size="sm" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-caption font-semibold text-ink">{{ $teacher->full_name }}</p>
                                    @if (filled($teacher->title))
                                        <p class="mt-0.5 truncate text-micro text-stone">{{ $teacher->title }}</p>
                                    @endif
                                </div>

                                @if ($teacher->pivot->is_primary)
                                    <x-admin.status-badge status="active" label="أساسي" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- ═══ الحالة والمحتوى ═══ --}}
        <aside class="space-y-6">
            <div class="rounded-xl border border-hairline bg-canvas p-5">
                <h3 class="text-ui font-bold text-ink">الحالة</h3>

                <dl class="mt-4 space-y-3 text-ui">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-caption text-stone">النشر</dt>
                        <dd>
                            <x-admin.status-badge :status="$subject->is_published ? 'active' : 'draft'"
                                                  :label="$subject->is_published ? 'منشورة' : 'مسودّة'" />
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-caption text-stone">الفرع</dt>
                        <dd class="text-caption font-semibold text-ink">{{ $subject->branch?->name ?? '—' }}</dd>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-caption text-stone">الترتيب</dt>
                        <dd class="text-caption font-semibold tabular-nums text-ink">{{ $subject->display_order }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-hairline bg-canvas p-5">
                <h3 class="text-ui font-bold text-ink">المحتوى</h3>

                <dl class="mt-4 space-y-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-caption text-stone">المحاضرات</dt>
                        <dd class="text-h5 font-bold tabular-nums text-ink">{{ $subject->lectures_count }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-caption text-stone">ملفات المكتبة</dt>
                        <dd class="text-h5 font-bold tabular-nums text-ink">{{ $subject->library_files_count }}</dd>
                    </div>
                </dl>

                {{-- ⚠️ يُقال صراحةً هنا لا عند فشل الحذف فقط: الأدمن يرى
                     السبب قبل أن يحاول. --}}
                @if ($subject->lectures_count > 0 || $subject->library_files_count > 0)
                    <p class="mt-4 border-t border-hairline pt-3 text-micro text-stone">
                        لا يمكن حذف المادة ما دامت تحمل محتوى — أخفِها بدل حذفها.
                    </p>
                @endif
            </div>
        </aside>
    </div>

</x-layouts.admin>
