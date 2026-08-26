<x-layouts.admin title="سلّة المحذوفات" breadcrumbs="النظام / سلّة المحذوفات">

    {{--
        سلّة مركزية — بنية veyra: تبويبات نوع · تحديد جماعي · إجراءات على
        المحدَّد وعلى الكل.

        ⚠️ بلا Alpine (غير مثبّت في هذا المشروع). التحديد vanilla عبر
        data-trash في app.js، والنموذج يعمل بلا JS: كل صفّ يحمل إجراءيه
        المفردين، والإجراءات الجماعية وحدها هي التي تحتاج تحديداً.
    --}}
    <div data-trash class="space-y-6">

        <x-admin.page-head
            title="سلّة المحذوفات"
            lede="العناصر المحذوفة حذفاً ناعماً. تُستعاد بمرفقاتها، أو تُمحى نهائياً بلا رجعة.">
            <x-slot:actions>
                <span class="text-caption tabular-nums text-stone">
                    {{ $items->count() }} / {{ $totalCount }}
                </span>
            </x-slot:actions>
        </x-admin.page-head>

        {{-- ═══ تبويبات النوع ═══ --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.trash.index') }}"
               @class([
                   'rounded-lg px-3 py-1.5 text-caption font-semibold transition',
                   'bg-accent text-on-primary' => blank($activeType),
                   'bg-surface text-steel hover:bg-hairline' => filled($activeType),
               ])>
                الكل
                <span class="tabular-nums">({{ $totalCount }})</span>
            </a>

            @foreach ($types as $key => $config)
                <a href="{{ route('admin.trash.index', ['type' => $key]) }}"
                   @class([
                       'rounded-lg px-3 py-1.5 text-caption font-semibold transition',
                       'bg-accent text-on-primary' => $activeType === $key,
                       'bg-surface text-steel hover:bg-hairline' => $activeType !== $key,
                   ])>
                    {{ $config['label'] }}
                    <span class="tabular-nums">({{ $counts[$key] ?? 0 }})</span>
                </a>
            @endforeach
        </div>

        @if ($items->isEmpty())
            <div class="rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                    <x-icon name="trash" class="size-5" />
                </span>
                <p class="mt-4 text-ui font-semibold text-ink">السلة فارغة</p>
                <p class="mt-1 text-caption text-stone">لا توجد عناصر محذوفة في هذا القسم.</p>
            </div>
        @else
            {{-- ═══ شريط الإجراءات ═══ --}}
            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-hairline bg-canvas p-3">

                {{-- على المحدَّد — يظهر فقط حين يوجد تحديد --}}
                <div data-trash-bulk hidden class="flex flex-wrap items-center gap-2">
                    <span class="text-caption text-stone">
                        المحدَّد: <span data-trash-count class="font-semibold tabular-nums text-ink">0</span>
                    </span>

                    {{-- 🔴 الرموز تُحقَن في هذين النموذجين بـJS من الخانات
                         المؤشّرة: خانة داخل نموذج آخر لا تُرسَل معه. --}}
                    <form method="POST" action="{{ route('admin.trash.restore-selected') }}"
                          data-trash-form="restore">
                        @csrf
                        @method('PATCH')
                        @if ($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
                        <x-admin.btn variant="primary">
                            <x-icon name="undo" class="size-4 rtl:-scale-x-100" />
                            استعادة المحدد
                        </x-admin.btn>
                    </form>

                    <form method="POST" action="{{ route('admin.trash.force-selected') }}"
                          data-trash-form="force"
                          onsubmit="return confirm('حذف نهائي للعناصر المحددة بلا رجعة. متابعة؟')">
                        @csrf
                        @method('DELETE')
                        @if ($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
                        <x-admin.btn variant="danger">حذف المحدد نهائياً</x-admin.btn>
                    </form>

                    <span class="mx-1 h-5 w-px bg-hairline"></span>
                </div>

                {{-- على الكل — متاح دائماً ولا يحتاج تحديداً --}}
                <form method="POST" action="{{ route('admin.trash.restore-all') }}">
                    @csrf
                    @method('PATCH')
                    @if ($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
                    <x-admin.btn variant="secondary">استعادة الكل</x-admin.btn>
                </form>

                <form method="POST" action="{{ route('admin.trash.empty') }}"
                      onsubmit="return confirm('سيُحذف كل المعروض نهائياً بلا رجعة. متابعة؟')">
                    @csrf
                    @method('DELETE')
                    @if ($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
                    <x-admin.btn variant="danger">حذف الكل نهائياً</x-admin.btn>
                </form>
            </div>

            {{-- ═══ الجدول ═══ --}}
            <div class="w-full overflow-x-auto rounded-xl border border-hairline bg-canvas">
                <table class="min-w-full divide-y divide-hairline text-ui">
                    <thead class="bg-surface">
                        <tr>
                            <th scope="col" class="w-12 px-4 py-3 text-start">
                                <input type="checkbox" data-trash-all
                                       aria-label="تحديد الكل"
                                       class="size-4 cursor-pointer rounded border-hairline-strong text-accent focus:ring-accent/40">
                            </th>
                            <th scope="col" class="w-14 px-4 py-3 text-start text-caption font-semibold text-stone">#</th>
                            <th scope="col" class="w-32 px-4 py-3 text-start text-caption font-semibold text-stone">النوع</th>
                            <th scope="col" class="px-4 py-3 text-start text-caption font-semibold text-stone">العنصر</th>
                            <th scope="col" class="w-44 px-4 py-3 text-start text-caption font-semibold text-stone">تاريخ الحذف</th>
                            <th scope="col" class="px-4 py-3 text-end text-caption font-semibold text-stone">إجراءات</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-hairline">
                        @foreach ($items as $item)
                            <tr class="transition hover:bg-surface/60">
                                <td class="px-4 py-3">
                                    <input type="checkbox" data-trash-item value="{{ $item['token'] }}"
                                           aria-label="تحديد {{ $item['title'] }}"
                                           class="size-4 cursor-pointer rounded border-hairline-strong text-accent focus:ring-accent/40">
                                </td>

                                <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $loop->iteration }}</td>

                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-accent/10 px-2 py-1 text-micro font-semibold text-accent-deep">
                                        {{ $item['type_label'] }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <p class="font-semibold text-steel">{{ $item['title'] }}</p>
                                    @if ($item['subtitle'])
                                        <p class="mt-0.5 text-micro text-stone">{{ $item['subtitle'] }}</p>
                                    @endif
                                </td>

                                {{-- التاريخ LTR دائماً ولو كانت الصفحة RTL --}}
                                <td class="px-4 py-3">
                                    <span class="text-caption tabular-nums text-stone" dir="ltr">
                                        {{ $item['deleted_at']?->format('Y-m-d H:i') }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST"
                                              action="{{ route('admin.trash.restore', ['type' => $item['type'], 'id' => $item['id']]) }}">
                                            @csrf
                                            @method('PATCH')
                                            @if ($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
                                            <x-admin.btn variant="secondary">استعادة</x-admin.btn>
                                        </form>

                                        {{-- 🔴 الحذف النهائي وحده يستحقّ تأكيداً:
                                             الحذف الناعم قابل للتراجع، وهذا ليس كذلك. --}}
                                        <form method="POST"
                                              action="{{ route('admin.trash.force-destroy', ['type' => $item['type'], 'id' => $item['id']]) }}"
                                              onsubmit="return confirm('حذف نهائي بلا رجعة. لا يمكن استعادة العنصر ولا مرفقاته بعده. متابعة؟')">
                                            @csrf
                                            @method('DELETE')
                                            @if ($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
                                            <x-admin.btn variant="danger">حذف نهائي</x-admin.btn>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-layouts.admin>
