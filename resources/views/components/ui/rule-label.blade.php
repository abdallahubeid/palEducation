@props([
    'on' => 'light',   // light | dark — لون الخلفية التي تجلس عليها الشارة
])

@php
    $ink  = $on === 'dark' ? 'text-accent-on-dark' : 'text-accent-deep';
    $line = $on === 'dark' ? 'bg-accent-on-dark' : 'bg-accent';
    $bg   = $on === 'dark' ? 'bg-deep-from' : 'bg-ground';
@endphp

{{--
    نمط .section-title من القالب: نصّ يعبره خطّان — واحد قرب أعلاه
    وآخر قرب أسفله — يمتدّان خارج عرض النص.
    نُفِّذ بـ inset-inline فينعكس تلقائياً في RTL (القالب يستخدم left ثابتاً).
--}}
{{--
    w-fit + self-start إلزاميان: داخل حاوية flex-col يتمدّد inline-block
    لكامل العرض، فيمتد خطّه 60px خارجها ويُنتج تمريراً أفقياً.
--}}
<span {{ $attributes->merge(['class' => "relative inline-block w-fit max-w-full self-start text-micro font-semibold {$ink}"]) }}>
    <span class="rule-line--top pointer-events-none absolute top-[5px] h-0.5 {{ $line }}"
          aria-hidden="true"></span>

    <span class="rule-line--bottom pointer-events-none absolute bottom-[5px] h-0.5 {{ $line }}"
          aria-hidden="true"></span>

    <span class="relative {{ $bg }} pe-2">{{ $slot }}</span>
</span>
