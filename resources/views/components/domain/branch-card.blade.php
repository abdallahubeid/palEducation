@props([
    'name'     => '',
    'summary'  => '',
    'subjects' => 0,
    'teachers' => 0,
    'icon'     => 'beaker',
    'tone'     => 'accent',   // accent | tag | amber | warn — كلها من اللوحة نفسها
    'href'     => '#',
])

@php
    // هوية لونية لكل فرع — بلا إدخال لون جديد للنظام
    $tones = [
        'accent'   => ['chip' => 'bg-accent/14 text-accent-deep',     'line' => 'bg-accent',   'hover' => 'group-hover:bg-accent group-hover:text-on-primary'],
        'tag'    => ['chip' => 'bg-tag/12 text-tag',            'line' => 'bg-tag',    'hover' => 'group-hover:bg-tag group-hover:text-on-dark'],
        'amber' => ['chip' => 'bg-amber/12 text-amber-deep', 'line' => 'bg-amber', 'hover' => 'group-hover:bg-amber group-hover:text-ink'],
        'warn'   => ['chip' => 'bg-warn/14 text-warn',          'line' => 'bg-warn',   'hover' => 'group-hover:bg-warn group-hover:text-on-dark'],
    ];
    $t = $tones[$tone] ?? $tones['accent'];
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => 'tile group relative flex h-full flex-col overflow-hidden p-7'
   ]) }}>

    {{-- شريط الهوية — يمتد عند التحويم --}}
    <span class="absolute inset-x-0 top-0 h-1 w-0 {{ $t['line'] }}
                 transition-all duration-500 group-hover:w-full" aria-hidden="true"></span>

    <span class="tile__icon {{ $t['chip'] }} {{ $t['hover'] }}">
        <x-icon :name="$icon" class="size-7" />
    </span>

    <h3 class="mt-6 text-h4 font-semibold text-ink">{{ $name }}</h3>

    <p class="mt-3 flex-1 text-caption leading-relaxed text-steel">{{ $summary }}</p>

    <div class="mt-6 flex items-center gap-3 text-micro text-stone">
        <span><span class="num font-medium text-slate">{{ $subjects }}</span> {{ __('home.subjects_unit') }}</span>
        <span class="size-1 rounded-full bg-hairline-strong" aria-hidden="true"></span>
        <span><span class="num font-medium text-slate">{{ $teachers }}</span> {{ __('home.teachers_unit') }}</span>
    </div>

    <span class="tile__link mt-4 text-ui font-semibold text-ink">
        {{ __('home.browse_branch') }}
        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
    </span>
</a>
