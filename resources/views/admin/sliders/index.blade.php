<x-layouts.admin title="الهيرو" breadcrumbs="محتوى الصفحة الرئيسية / الهيرو">

    <x-admin.page-head
        title="الهيرو"
        lede="الشرائح التي تظهر في أعلى الصفحة الرئيسية، بالترتيب.">
        <x-slot:actions>
            @can('create-sliders')
                <x-admin.btn :href="route('admin.sliders.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    شريحة جديدة
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    @if ($sliders->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="sparkle" class="size-5" />
            </span>
            <p class="mt-4 text-ui font-semibold text-ink">لا توجد شرائح بعد</p>
            <p class="mt-1 text-caption text-stone">أضف أول شريحة لتظهر في أعلى الصفحة الرئيسية.</p>
        </div>
    @else
        @php
            // 🔴 $loop->iteration يبدأ من 1 في كل صفحة. الإزاحة تجعل الترقيم
            // متّصلاً: الصفّ الأول في الصفحة الثانية يقرأ 16 لا 1.
            $offset = $sliders->firstItem() - 1;
        @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'الصورة', 'width' => 'w-28'],
            ['label' => 'العنوان'],
            ['label' => 'الترتيب', 'width' => 'w-20'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($sliders as $slider)
                <tr class="transition hover:bg-surface/60">

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        @if ($slider->image)
                            <img src="{{ $slider->image->url() }}" alt="{{ $slider->image->alt_text }}"
                                 loading="lazy"
                                 class="h-11 w-16 rounded-lg object-cover ring-1 ring-hairline">
                        @else
                            <span class="grid h-11 w-16 place-items-center rounded-lg bg-surface text-stone">
                                <x-icon name="eye-off" class="size-4" />
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <p class="font-semibold text-ink">{{ $slider->title }}</p>
                        @if ($slider->badge_text)
                            <p class="mt-0.5 text-micro text-stone">{{ $slider->badge_text }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-3 tabular-nums text-stone">{{ $slider->display_order }}</td>

                    <td class="px-4 py-3">
                        {{-- الشارة زرّ لمن يملك التعديل: إخفاء شريحة أشيع عمل
                             يومي، وإجباره على فتح النموذج يجعله ثلاث نقرات. --}}
                        @can('edit-sliders')
                            <form method="POST" action="{{ route('admin.sliders.toggle', $slider) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="تبديل الظهور"
                                        class="cursor-pointer rounded-md transition hover:opacity-70">
                                    <x-admin.status-badge :status="$slider->is_active ? 'active' : 'hidden'" />
                                </button>
                            </form>
                        @else
                            <x-admin.status-badge :status="$slider->is_active ? 'active' : 'hidden'" />
                        @endcan
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-sliders')
                                <x-admin.btn :href="route('admin.sliders.edit', $slider)" variant="secondary">
                                    تعديل
                                </x-admin.btn>
                            @endcan

                            @can('delete-sliders')
                                {{-- 🔴 الحذف POST لا رابط: طلب GET يُنفَّذ بجالب
                                     مسبق في المتصفّح أو بزيارة عابرة. --}}
                                <form method="POST" action="{{ route('admin.sliders.destroy', $slider) }}">
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

        <div class="mt-4">{{ $sliders->links() }}</div>
    @endif

</x-layouts.admin>
