@php
    /*
     * "About us" panel.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * enctype is required here: without it the browser posts the file name
     * as plain text and $request->file() returns null — a silent no-op.
     */
    $defaults = \App\Models\Setting::ABOUT_DEFAULTS;
    $value = fn (string $f) => old('about.'.$f, $settings['about.'.$f] ?? $defaults['about.'.$f]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    $currentImage = $settings['about.img'] ?? $defaults['about.img'];
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">قسم «من نحن»</h3>
            <p class="mt-1.5 text-micro text-stone">النصّ والصورة والصندوق الملوّن أسفل القسم.</p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="about_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="about_badge" name="about[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('about.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="about_title1" class="{{ $labelClass }}">العنوان الرئيسي</label>
            <input type="text" id="about_title1" name="about[title1]" maxlength="150"
                   value="{{ $value('title1') }}" class="{{ $inputClass }}">
            @error('about.title1')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="about_content1" class="{{ $labelClass }}">النصّ الرئيسي</label>
            <textarea id="about_content1" name="about[content1]" rows="4" maxlength="1000"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('content1') }}</textarea>
            @error('about.content1')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="about_title2" class="{{ $labelClass }}">عنوان «رسالتنا»</label>
                <input type="text" id="about_title2" name="about[title2]" maxlength="150"
                       value="{{ $value('title2') }}" class="{{ $inputClass }}">
                @error('about.title2')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="about_title_box" class="{{ $labelClass }}">نصّ الصندوق الملوّن</label>
                <input type="text" id="about_title_box" name="about[title_box]" maxlength="300"
                       value="{{ $value('title_box') }}" class="{{ $inputClass }}">
                @error('about.title_box')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="about_content2" class="{{ $labelClass }}">نصّ «رسالتنا»</label>
            <textarea id="about_content2" name="about[content2]" rows="3" maxlength="1000"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('content2') }}</textarea>
            @error('about.content2')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <h3 class="text-ui font-bold text-ink">الزرّ والصورة</h3>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="about_btn_text" class="{{ $labelClass }}">نصّ الزرّ</label>
                <input type="text" id="about_btn_text" name="about[btn_text]" maxlength="40"
                       value="{{ $value('btn_text') }}" class="{{ $inputClass }}">
                @error('about.btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="about_btn_link" class="{{ $labelClass }}">وجهة الزرّ</label>
                {{-- type=text not url: /register and #pricing are valid
                     targets that the browser's url validator rejects. --}}
                <input type="text" id="about_btn_link" name="about[btn_link]" dir="ltr" maxlength="500"
                       placeholder="/register" value="{{ $value('btn_link') }}" class="{{ $inputClass }}">
                @error('about.btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="about_img" class="{{ $labelClass }}">صورة القسم</label>

            @if (filled($currentImage))
                {{--
                    🔴 Fixed-size preview card, not a full-width banner.

                    The wrapper used to be a bare div with an h-40 w-full
                    image inside, so the preview stretched across the whole
                    settings panel — on a wide screen that is a ~1000px band
                    of photograph in the middle of a form, and a portrait
                    source got squashed into it.

                    size-48 pins BOTH axes so the container never depends on
                    the source's dimensions, and object-cover fills that box
                    at the image's own aspect ratio, cropping rather than
                    distorting. shrink-0 keeps flex from squeezing it.
                --}}
                <div class="mb-3 flex items-start gap-4">
                    <div class="size-48 shrink-0 overflow-hidden rounded-lg border border-hairline bg-surface">
                        <img src="{{ asset(ltrim($currentImage, '/')) }}"
                             alt="معاينة صورة قسم من نحن"
                             loading="lazy"
                             class="size-full object-cover">
                    </div>

                    <div class="min-w-0 pt-1">
                        <p class="text-caption font-medium text-steel">الصورة الحالية</p>
                        {{-- The path is latin inside Arabic text — isolated
                             with bdi, and wrapped so a long one cannot push
                             the panel sideways. --}}
                        <p class="mt-1 text-micro break-all text-stone">
                            <bdi dir="ltr">{{ $currentImage }}</bdi>
                        </p>
                    </div>
                </div>
            @endif

            {{-- Leaving this empty keeps the current image — the controller
                 only writes the key when a file actually arrives. --}}
            <input type="file" id="about_img" name="about[img]" accept="image/*"
                   class="{{ $inputClass }} file:me-3 file:rounded-lg file:border-0 file:bg-surface
                          file:px-3 file:py-1.5 file:text-caption file:font-semibold file:text-steel">
            <p class="mt-1.5 text-micro text-stone">JPG أو PNG أو WebP · 4 ميغابايت كحدّ أقصى. اتركه فارغاً للإبقاء على الصورة الحالية.</p>
            @error('about.img')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
