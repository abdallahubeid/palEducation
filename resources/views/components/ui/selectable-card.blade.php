@props([
    'name'        => '',
    'value'       => '',
    'title'       => '',
    'description' => null,
    'icon'        => 'circle',
    'tone'        => 'accent',   // accent | tag | amber | warn
    'checked'     => false,
])

@php
    $id = $attributes->get('id') ?: 'pick-' . $name . '-' . $value;

    // 🔴 أصناف كاملة حرفياً — لا تجميع نصي من متغيّر (الفاحص لا يُنفّذ PHP)
    $tones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'amber'  => 'bg-amber/12 text-amber-deep',
        'warn'   => 'bg-warn/14 text-warn-deep',
    ];
    $chip = $tones[$tone] ?? $tones['accent'];
@endphp

{{--
    🔴 بطاقة اختيار بدلالة radio — بلا .tile إطلاقاً.
    .tile صنف غير طبقي يهزم peer-checked: مهما كانت الخصوصية، وهو الفخّ
    نفسه الذي كسر QuizOption مرّتين. المظهر (سطح أبيض + نصف xl + ارتفاع)
    مُعاد هنا بأصناف Tailwind خام فقط: bg-canvas + rounded-xl + shadow.

    الحالة المختارة تُعلَن بثلاث إشارات لا بلون وحده: حلقة، خلفية باهتة،
    وعلامة صح ظاهرة — كي تُقرأ بلا اعتماد على تمييز الألوان.
--}}
<label for="{{ $id }}"
       {{ $attributes->except('id')->merge(['class' => 'group relative block cursor-pointer']) }}>
    <input type="radio"
           id="{{ $id }}"
           name="{{ $name }}"
           value="{{ $value }}"
           @checked($checked)
           class="peer sr-only">

    <span class="flex h-full flex-col gap-4 rounded-xl bg-canvas p-6 shadow-subtle ring-1 ring-hairline transition
                 group-hover:ring-hairline-strong
                 peer-checked:bg-accent-soft peer-checked:ring-2 peer-checked:ring-accent
                 peer-checked:[&_[data-pick-mark]]:scale-100
                 peer-focus-visible:shadow-[0_0_0_3px_rgb(82_95_225_/_0.32)]">

        <span class="flex items-start justify-between gap-3">
            <span class="grid size-12 shrink-0 place-items-center rounded-md {{ $chip }}">
                <x-icon :name="$icon" class="size-6" />
            </span>

            <span data-pick-mark
                  class="grid size-6 shrink-0 scale-0 place-items-center rounded-full bg-accent text-on-primary transition-transform">
                <x-icon name="check" class="size-3.5" />
            </span>
        </span>

        <span class="block">
            <span class="block text-h5 font-semibold text-ink">{{ $title }}</span>
            @if ($description)
                <span class="mt-1.5 block text-caption leading-relaxed text-steel">{{ $description }}</span>
            @endif
        </span>

        {{ $slot }}
    </span>
</label>
