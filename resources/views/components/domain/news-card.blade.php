@props([
    'title'    => '',
    'excerpt'  => '',
    'date'     => '',          // ISO للـdatetime
    'dateLabel' => '',         // نص معروض
    'category' => null,
    'image'    => null,
    'href'     => '#',
    'featured' => false,       // بطاقة الصدارة — أعرض وأكبر
])

{{--
    بطاقة خبر. الصورة تمرّ عبر media-slot فتسقط إلى خانة مصمّمة عند
    غياب الملف — لا صورة مكسورة ولا فراغ.

    التاريخ داخل <time dir="ltr"> — الأرقام لا تنعكس بأي لغة.
--}}
<article {{ $attributes->merge(['class' => 'tile group flex h-full flex-col overflow-hidden']) }}>
    <a href="{{ $href }}" class="flex h-full flex-col">

        {{-- media-slot يفرض النسبة بنفسه — لا تُغلَّف بحاوية aspect ثانية --}}
        <div class="relative overflow-hidden">
            <x-ui.media-slot :src="$image" :alt="$title" icon="compass"
                             :ratio="$featured ? '16/9' : '16/10'"
                             class="w-full transition duration-500 group-hover:scale-105" />

            @if ($category)
                <span class="absolute start-3 top-3 inline-flex items-center rounded-full bg-canvas/95 px-3 py-1
                             text-micro font-semibold text-accent-deep shadow-subtle">
                    {{ $category }}
                </span>
            @endif
        </div>

        <div @class(['flex flex-1 flex-col p-5', 'sm:p-7' => $featured])>
            <time datetime="{{ $date }}" dir="ltr" class="num text-caption text-stone">{{ $dateLabel }}</time>

            <h3 @class([
                'mt-2 font-semibold text-ink transition group-hover:text-accent-deep',
                'text-h5' => ! $featured,
                'text-h3' => $featured,
            ])>{{ $title }}</h3>

            {{-- المقتطف نص يُقرأ فعلاً: 16px وارتفاع سطر 1.75 --}}
            <p class="mt-2.5 flex-1 text-body leading-[1.75] text-steel">{{ $excerpt }}</p>

            <span class="tile__link mt-4 text-ui font-semibold text-ink">
                {{ __('public.news_read_more') }}
                <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
            </span>
        </div>
    </a>
</article>
