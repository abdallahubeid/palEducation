@props([
    'headers' => [],   // ['label' => '…', 'align' => 'end', 'width' => 'w-20']
])

{{--
    غلاف جدول — نفس بنية جداول veyra: بطاقة rounded-xl بحدّ، ترويسة على
    surface، وصفوف مفصولة بـdivide-y.

    🔴 عمود «#» يُحقن هنا لا في كل شاشة: القاعدة أن كل جدول يبدأ برقم صفّ،
    وتركها لكل استدعاء يعني أن جدولاً واحداً منسيّاً يكسر الاتساق.

    overflow-x-auto على الغلاف وحده — الصفحة نفسها لا تنزلق أفقياً أبداً.
--}}
<div {{ $attributes->class(['w-full overflow-x-auto rounded-xl border border-hairline bg-canvas']) }}>
    <table class="min-w-full divide-y divide-hairline text-ui">
        <thead class="bg-surface">
            <tr>
                <th scope="col" class="w-14 px-4 py-3 text-start text-caption font-semibold text-stone">#</th>

                @foreach ($headers as $header)
                    <th scope="col"
                        @class([
                            'px-4 py-3 text-caption font-semibold text-stone',
                            'text-end' => ($header['align'] ?? 'start') === 'end',
                            'text-start' => ($header['align'] ?? 'start') !== 'end',
                            $header['width'] ?? '',
                        ])>{{ $header['label'] }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody class="divide-y divide-hairline">
            {{ $slot }}
        </tbody>
    </table>
</div>
