<x-layouts.admin title="لوحة التحكّم" breadcrumbs="نظرة عامة">

    <x-admin.page-head
        :title="'مرحباً، ' . auth()->user()->name"
        lede="نظرة سريعة على محتوى المنصّة وما يحتاج إجراءً اليوم.">
        <x-slot:actions>
            @can('create-sliders')
                <x-admin.btn :href="route('admin.sliders.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    شريحة جديدة
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    {{-- ═══ المؤشّرات ═══ --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="الطلبة" :value="$metrics['students']" icon="users" />
        <x-admin.stat-card label="المعلّمون" :value="$metrics['teachers']" icon="user" />
        <x-admin.stat-card label="المواد" :value="$metrics['subjects']" icon="book" />
        <x-admin.stat-card label="المحاضرات" :value="$metrics['lectures']" icon="play" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ═══ الشرائح ═══ --}}
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-hairline bg-canvas">
                <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                    <div>
                        <h3 class="text-ui font-bold text-ink">آخر شرائح الهيرو</h3>
                        <p class="mt-0.5 text-micro text-stone">
                            <span class="tabular-nums">{{ $sliders['active'] }}</span> ظاهرة من
                            <span class="tabular-nums">{{ $sliders['total'] }}</span>
                        </p>
                    </div>

                    @can('view-sliders')
                        <x-admin.btn :href="route('admin.sliders.index')" variant="secondary">
                            عرض الكل
                        </x-admin.btn>
                    @endcan
                </div>

                @if ($latestSliders->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <p class="text-ui text-stone">لا توجد شرائح بعد.</p>
                    </div>
                @else
                    <ul class="divide-y divide-hairline">
                        @foreach ($latestSliders as $slider)
                            <li class="flex items-center gap-4 px-5 py-3">
                                <span class="tabular-nums text-caption text-stone">{{ $loop->iteration }}</span>

                                @if ($slider->image)
                                    <img src="{{ $slider->image->url() }}"
                                         alt="{{ $slider->image->alt_text }}"
                                         loading="lazy"
                                         class="h-11 w-16 shrink-0 rounded-lg object-cover ring-1 ring-hairline">
                                @else
                                    <span class="grid h-11 w-16 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                                        <x-icon name="eye-off" class="size-4" />
                                    </span>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-ui font-semibold text-ink">{{ $slider->title }}</p>
                                    @if ($slider->badge_text)
                                        <p class="truncate text-micro text-stone">{{ $slider->badge_text }}</p>
                                    @endif
                                </div>

                                <x-admin.status-badge :status="$slider->is_active ? 'active' : 'hidden'" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- ═══ العمود الجانبي ═══ --}}
        <div class="space-y-6">

            @if ($sliders['trashed'])
                {{-- accent محجوز للمؤشّر الذي يتطلّب إجراءً: السلّة غير الفارغة
                     هي الوحيدة هنا. --}}
                <x-admin.stat-card
                    label="في سلّة المحذوفات"
                    :value="$sliders['trashed']"
                    hint="تُستعاد بصورتها أو تُمحى نهائياً."
                    icon="trash"
                    accent
                    :href="route('admin.trash.index')" />
            @endif

            <div class="rounded-xl border border-hairline bg-canvas">
                <div class="border-b border-hairline px-5 py-4">
                    <h3 class="text-ui font-bold text-ink">الفروع</h3>
                    <p class="mt-0.5 text-micro text-stone">الطلبة والمواد في كل فرع.</p>
                </div>

                <ul class="divide-y divide-hairline">
                    @foreach ($branches as $branch)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="flex min-w-0 items-center gap-2.5">
                                <x-icon :name="$branch->icon ?: 'circle'" class="size-4 shrink-0 text-stone" />
                                <span class="truncate text-ui text-ink">{{ $branch->name }}</span>
                            </span>

                            <span class="flex shrink-0 items-center gap-3 text-micro tabular-nums text-stone">
                                <span>{{ $branch->students_count }} طالب</span>
                                <span class="text-hairline-strong">·</span>
                                <span>{{ $branch->subjects_count }} مادة</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

</x-layouts.admin>
