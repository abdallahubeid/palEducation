@props([
    'poster' => null,
    'title'  => '',
])

{{--
    بديل مصمّم بانتظار قرار مزوّد الفيديو (م-5). الحاوية LTR إجبارياً
    بصرف النظر عن القرار — شريط الزمن يمشي يساراً←يميناً عالمياً.
    data-video-play / data-video-status هي الواجهة التي سيربط بها
    الـSDK الحقيقي لاحقاً (حدث ended أو ما يعادله عند بني/فيميو).
--}}
<div dir="ltr"
     data-video-player
     class="group relative aspect-video w-full overflow-hidden rounded-xl bg-canvas-dark">

    @if ($poster)
        <img src="{{ $poster }}" alt="{{ $title }}" class="absolute inset-0 size-full object-cover opacity-35">
    @endif

    <div class="absolute inset-0 bg-linear-to-bl from-deep-from/90 to-deep-to/90"></div>

    <div class="relative grid size-full place-items-center">
        <div class="flex flex-col items-center gap-4 px-6 text-center">
            <button type="button"
                    data-video-play
                    aria-label="{{ __('student.play_lecture') }}"
                    class="grid size-20 place-items-center rounded-full bg-accent text-on-primary
                           transition duration-300 group-[.is-playing]:hidden hover:scale-110">
                <x-icon name="play" class="size-8" />
            </button>

            <span class="hidden items-center gap-2 text-on-dark group-[.is-playing]:flex" aria-hidden="true">
                <span class="size-2 animate-pulse rounded-full bg-accent"></span>
                <span class="size-2 animate-pulse rounded-full bg-accent [animation-delay:150ms]"></span>
                <span class="size-2 animate-pulse rounded-full bg-accent [animation-delay:300ms]"></span>
            </span>

            <p data-video-status
               data-playing-label="{{ __('student.video_playing_demo') }}"
               class="max-w-xs text-caption text-on-dark/60">
                {{ __('student.video_pending') }}
            </p>
        </div>
    </div>
</div>
