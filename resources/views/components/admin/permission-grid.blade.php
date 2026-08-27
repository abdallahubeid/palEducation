@props([
    'groups' => [],
    'assigned' => [],
    'locked' => [],
    'toggleUrl' => null,      // مسار تبديل صلاحية واحدة — null = وضع النموذج
    'groupUrl' => null,       // مسار تبديل قسم كامل
])

@php
    /*
     * شبكة الصلاحيات — عمودان متوازنان، قسم لكل وحدة.
     *
     * وضعان في مكوّن واحد:
     *   toggleUrl موجود  → حفظ فوري بـAlpine (شاشة التعديل)
     *   toggleUrl = null → خانات داخل النموذج تُحفظ مع الإرسال (شاشة الإنشاء،
     *                      حيث لا دور بعد فلا مسار لحفظ فوري عليه)
     *
     * 🔴 حالة كل قسم تُحسب في Alpine من الصلاحيات نفسها لا تُخزَّن منفصلة:
     * مفتاح قسم بحالة مستقلّة يكذب لحظة تغيير صلاحية مفردة تحته.
     */
    $state = collect($groups)->mapWithKeys(fn (array $group, string $key) => [
        $key => collect($group['permissions'])->keys()->mapWithKeys(fn (string $p) => [
            $p => in_array($p, $assigned, true) || in_array($p, $locked, true),
        ])->all(),
    ])->all();
@endphp

<div
    x-data="permissionGrid({
        state: {{ Js::from($state) }},
        locked: {{ Js::from($locked) }},
        toggleUrl: {{ Js::from($toggleUrl) }},
        groupUrl: {{ Js::from($groupUrl) }},
        csrf: {{ Js::from(csrf_token()) }},
    })"
    {{ $attributes->class(['grid grid-cols-1 gap-5 md:grid-cols-2']) }}
>
    @foreach ($groups as $key => $group)
        <section class="flex flex-col rounded-xl border border-hairline bg-canvas">

            {{-- ترويسة القسم: العنوان وعدّاد ومفتاح تشغيل الكل --}}
            <header class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-accent/12 text-accent">
                        <x-icon :name="$group['icon']" class="size-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-ui font-bold text-ink">{{ $group['label'] }}</p>
                        <p class="text-micro tabular-nums text-stone">
                            <span x-text="count('{{ $key }}')"></span> /
                            {{ count($group['permissions']) }}
                        </p>
                    </div>
                </div>

                {{--
                    مفتاح القسم — ثنائي الحالة بحتاً:

                    🔴 حالته **محسوبة** من الأبناء لا مخزَّنة منفصلة (`all()`).
                    فالتزامن في الاتجاهين مجاني: تشغيله يشغّل الكل، وإطفاء ابن
                    واحد يُطفئه فوراً بلا مستمع إضافي ولا مزامنة يدوية.

                    ⚠️ لا حالة ثالثة «بعض لا كل»: العدّاد فوقه (٢/٤) يحمل هذا
                    المعنى، والمفتاح يبقى إجابة واحدة لا لبس فيها.
                --}}
                <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition"
                       :class="all('{{ $key }}') ? 'bg-accent' : 'bg-hairline-strong'">
                    <span class="sr-only">تشغيل كل صلاحيات {{ $group['label'] }}</span>
                    <input type="checkbox" class="peer sr-only"
                           :checked="all('{{ $key }}')"
                           @change="toggleGroup('{{ $key }}', $event.target.checked)">
                    <span class="absolute inset-y-0.5 size-5 rounded-full bg-canvas shadow-subtle transition-[inset-inline-start]"
                          :class="all('{{ $key }}') ? 'start-[1.375rem]' : 'start-0.5'"></span>
                </label>
            </header>

            {{-- الصلاحيات المفردة --}}
            <div class="divide-y divide-hairline">
                @foreach ($group['permissions'] as $permission => $label)
                    @php $isLocked = in_array($permission, $locked, true); @endphp

                    <div class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="text-ui text-ink">{{ $label }}</p>
                            {{-- الاسم البرمجي LTR: مفاتيح النظام لاتينية دائماً --}}
                            <p class="mt-0.5 text-micro text-stone" dir="ltr">{{ $permission }}</p>

                            @if ($isLocked)
                                <p class="mt-1 inline-flex items-center gap-1.5 text-micro text-warn-deep">
                                    <x-icon name="lock" class="size-3.5" />
                                    مقفلة — نزعها يُغلق هذه الشاشة على كل أدمن
                                </p>
                            @endif
                        </div>

                        <label @class([
                            'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition',
                            'cursor-pointer' => ! $isLocked,
                            'cursor-not-allowed' => $isLocked,
                        ])
                               :class="state['{{ $key }}']['{{ $permission }}'] ? 'bg-accent' : 'bg-hairline-strong'">
                            <span class="sr-only">{{ $label }}</span>

                            {{-- name فقط في وضع النموذج: في وضع الحفظ الفوري
                                 لا نريد إرسال الخانات مع أي نموذج محيط. --}}
                            <input type="checkbox"
                                   @if (! $toggleUrl) name="permissions[]" value="{{ $permission }}" @endif
                                   class="peer sr-only"
                                   @disabled($isLocked)
                                   :checked="state['{{ $key }}']['{{ $permission }}']"
                                   @change="toggle('{{ $key }}', '{{ $permission }}', $event.target.checked)">

                            {{-- الانزلاق عبر start المنطقية لا translate:
                                 تنعكس مع dir تلقائياً بلا صنف rtl إضافي. --}}
                            <span class="absolute inset-y-0.5 size-5 rounded-full bg-canvas shadow-subtle transition-[inset-inline-start]"
                                  :class="state['{{ $key }}']['{{ $permission }}'] ? 'start-[1.375rem]' : 'start-0.5'"></span>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
