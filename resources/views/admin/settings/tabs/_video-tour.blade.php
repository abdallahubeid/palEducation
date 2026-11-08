@php
    /*
     * "Quick tour" panel — header text and the intro video file.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * ⏳ Decision M-5 (video provider) is still open. This uploads to the
     * public disk, which is right for a marketing trailer and wrong for
     * lecture content — those stay on the private disk behind a controller.
     */
    $defaults = \App\Models\Setting::VIDEO_TOUR_DEFAULTS;
    $value = fn (string $f) => old('video_tour.'.$f, $settings['video_tour.'.$f] ?? $defaults['video_tour.'.$f]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    $currentVideo = $settings['video_tour.video_path'] ?? '';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">قسم «جولة سريعة»</h3>
            <p class="mt-1.5 text-micro text-stone">الشريط الداكن الذي يفتح الفيديو التعريفي.</p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="video_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="video_badge" name="video_tour[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('video_tour.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="video_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="video_title" name="video_tour[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('video_tour.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="video_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="video_sub" name="video_tour[sub_title]" rows="3" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            @error('video_tour.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="video_file" class="{{ $labelClass }}">ملف الفيديو</label>

            @if (filled($currentVideo))
                {{-- Player forced LTR: the timeline runs left-to-right in
                     every locale, and mirroring it confuses every user. --}}
                {{-- Same constraint as the image preview: a bounded card,
                     not a full-width player filling the settings panel. --}}
                <div dir="ltr" class="mb-3 max-w-md overflow-hidden rounded-lg border border-hairline">
                    <video src="{{ asset(ltrim($currentVideo, '/')) }}" controls preload="metadata"
                           class="aspect-video w-full bg-canvas-dark"></video>
                </div>
            @else
                <p class="mb-3 rounded-xl border border-dashed border-hairline-strong px-4 py-6 text-center text-micro text-stone">
                    لا يوجد فيديو بعد — يعرض القسم لوح تشغيل بلا محتوى.
                </p>
            @endif

            <input type="file" id="video_file" name="video_tour[video_path]" accept="video/mp4,video/webm"
                   class="{{ $inputClass }} file:me-3 file:rounded-lg file:border-0 file:bg-surface
                          file:px-3 file:py-1.5 file:text-caption file:font-semibold file:text-steel">
            <p class="mt-1.5 text-micro text-stone">MP4 أو WebM · 50 ميغابايت كحدّ أقصى. اتركه فارغاً للإبقاء على الفيديو الحالي.</p>
            @error('video_tour.video_path')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
