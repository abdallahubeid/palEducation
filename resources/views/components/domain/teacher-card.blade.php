@props([
    'name'     => '',
    'subject'  => '',
    'branch'   => '',
    'initials' => '',
    'photo'    => null,      // صورة الأستاذ عند توفّرها
    'lectures' => 0,
    'students' => 0,
    'tone'     => 'mint',
])

@php
    $tones = [
        'mint'   => ['wash' => 'bg-mint/10',   'strip' => 'bg-mint/8',   'text' => 'text-mint-deep'],
        'tag'    => ['wash' => 'bg-tag/10',    'strip' => 'bg-tag/8',    'text' => 'text-tag'],
        'orange' => ['wash' => 'bg-orange/10', 'strip' => 'bg-orange/8', 'text' => 'text-orange-deep'],
        'warn'   => ['wash' => 'bg-warn/12',   'strip' => 'bg-warn/10',  'text' => 'text-warn'],
    ];
    $t = $tones[$tone] ?? $tones['mint'];

    // روابط ذات معنى بدل أيقونات تواصل وهمية
    $actions = [
        ['icon' => 'users',  'label' => __('home.teacher_profile')],
        ['icon' => 'play',   'label' => __('home.teacher_lectures')],
        ['icon' => 'folder', 'label' => __('home.teacher_files')],
    ];
@endphp

{{-- بنية team-item من القالب: عمود [صورة · اسم · دور] بجانبه شريط أيقونات بكامل الارتفاع --}}
<article {{ $attributes->merge(['class' => 'tile group flex h-full gap-3.5 p-3.5']) }}>

    {{-- العمود: الصورة ثم الاسم ثم المادة --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <div class="aspect-square overflow-hidden rounded-lg {{ $t['wash'] }}">
            @if ($photo)
                <img src="{{ $photo }}"
                     alt="{{ $name }}"
                     loading="lazy"
                     decoding="async"
                     class="size-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <span class="grid size-full place-items-center text-h2 font-bold {{ $t['text'] }} opacity-60">
                    {{ $initials }}
                </span>
            @endif
        </div>

        <h3 class="mt-3.5 truncate text-h5 font-semibold text-ink">{{ $name }}</h3>
        <p class="mt-0.5 text-caption {{ $t['text'] }}">{{ $subject }}</p>

        <div class="mt-2.5 flex items-center gap-2 text-micro text-stone">
            <x-ui.badge variant="neutral" shape="chip">{{ $branch }}</x-ui.badge>
            <span><span class="num font-medium text-slate">{{ $lectures }}</span> {{ __('home.lectures_unit') }}</span>
        </div>
    </div>

    {{-- الشريط العمودي بكامل الارتفاع --}}
    {{-- 44px على اللمس، 36px من lg حيث المؤشّر دقيق --}}
    <div class="flex w-16 shrink-0 flex-col items-center justify-center gap-2 rounded-lg {{ $t['strip'] }} py-3 lg:w-[54px]">
        @foreach ($actions as $action)
            <a href="#"
               aria-label="{{ $action['label'] }} — {{ $name }}"
               class="grid size-11 place-items-center rounded-md bg-primary text-on-primary
                      transition hover:bg-mint hover:text-primary lg:size-9">
                <x-icon :name="$action['icon']" class="size-3.5" />
            </a>
        @endforeach
    </div>
</article>
