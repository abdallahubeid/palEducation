@php
    /*
     * Shared create/edit form.
     *
     * Expected: $action · $method ('POST'|'PUT') · $news (News|null)
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    /*
     * ⚠️ old() first in every field. Without it a validation failure hands
     * back the SAVED values and silently discards what the editor typed —
     * on this screen that can be a whole article.
     */
    $current = $news?->published_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      class="grid gap-6 lg:grid-cols-3">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    {{-- ═══ The article ═══ --}}
    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:col-span-2 lg:p-8">

        <div>
            <label for="title" class="{{ $labelClass }}">العنوان <span class="text-error-deep">*</span></label>
            <input type="text" id="title" name="title" maxlength="190" required
                   value="{{ old('title', $news?->title) }}"
                   placeholder="مثال: إعلان مواعيد امتحانات التوجيهي"
                   class="{{ $inputClass }}">
            @error('title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror

            @if ($news)
                {{-- 🔴 Shown because it is the article's public URL. It follows
                     the title, but only when the title changes — an edit that
                     fixed a typo must not break links already shared. --}}
                <p class="mt-1.5 text-micro text-stone">
                    الرابط الحالي: <bdi dir="ltr" class="font-latin">/news/{{ $news->slug }}</bdi>
                </p>
            @endif
        </div>

        <div>
            <label for="summary" class="{{ $labelClass }}">الملخّص</label>
            <textarea id="summary" name="summary" rows="3" maxlength="500"
                      placeholder="سطران يظهران على البطاقة في صفحة الأخبار."
                      class="{{ $inputClass }} leading-relaxed">{{ old('summary', $news?->summary) }}</textarea>
            @error('summary')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="content" class="{{ $labelClass }}">النصّ <span class="text-error-deep">*</span></label>
            <textarea id="content" name="content" rows="16" required
                      placeholder="اكتب نصّ الخبر هنا…"
                      class="{{ $inputClass }} leading-[1.9]">{{ old('content', $news?->content) }}</textarea>
            {{-- ⚠️ Says plainly that this is plain text: the public page
                 escapes it and converts newlines, so pasted markup arrives as
                 visible characters rather than as formatting. --}}
            <p class="mt-1.5 text-micro text-stone">
                نصّ عادي — تُحفَظ الأسطر الجديدة كما هي، ولا تُفسَّر وسوم HTML.
            </p>
            @error('content')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- ═══ Publishing ═══ --}}
    <aside class="lg:col-span-1">
        <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6">

            <h3 class="text-ui font-bold text-ink">النشر</h3>

            <div>
                <label class="flex cursor-pointer items-start gap-2.5">
                    {{-- 🔴 A hidden 0 before the checkbox: an unchecked box
                         sends NOTHING, and without this the toggle would be
                         one-way — switchable on and never off. --}}
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" id="is_published" name="is_published" value="1"
                           @checked(old('is_published', $news?->is_published ?? true))
                           class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-hairline-strong
                                  text-accent focus:ring-2 focus:ring-accent/25">
                    <span class="min-w-0">
                        <span class="block text-caption font-semibold text-ink">منشور</span>
                        <span class="mt-0.5 block text-micro text-stone">
                            يظهر للزوّار متى حلّ تاريخ النشر.
                        </span>
                    </span>
                </label>
            </div>

            <div>
                <label for="published_at" class="{{ $labelClass }}">تاريخ النشر</label>
                {{-- Forced LTR: date inputs run left-to-right in every locale. --}}
                <input type="datetime-local" id="published_at" name="published_at" dir="ltr"
                       value="{{ old('published_at', $current) }}"
                       class="{{ $inputClass }} font-latin">
                <p class="mt-1.5 text-micro text-stone">
                    تاريخ في المستقبل يجعل الخبر مجدولاً — محفوظاً ولا يظهر حتى موعده.
                </p>
                @error('published_at')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="image" class="{{ $labelClass }}">صورة الغلاف</label>

                @if ($news?->cover_url)
                    {{-- A bounded preview, not a full-width image filling the
                         panel. --}}
                    <div class="mb-3 overflow-hidden rounded-lg border border-hairline">
                        <img src="{{ $news->cover_url }}" alt="" class="aspect-video w-full object-cover">
                    </div>
                @endif

                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                       class="{{ $inputClass }} file:me-3 file:rounded-lg file:border-0 file:bg-surface
                              file:px-3 file:py-1.5 file:text-caption file:font-semibold file:text-steel">
                <p class="mt-1.5 text-micro text-stone">
                    JPG · PNG · WebP · 4 ميغابايت كحدّ أقصى.
                    @if ($news?->cover_url) اتركه فارغاً للإبقاء على الصورة الحالية. @endif
                </p>
                @error('image')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            @if ($news)
                <div class="border-t border-hairline pt-4">
                    <p class="text-micro text-stone">الكاتب</p>
                    {{-- ⚠️ Read-only. The author is taken from the session on
                         creation and never re-assigned: a posted author_id
                         would let anyone attribute an article to a colleague. --}}
                    <p class="mt-0.5 text-caption font-semibold text-ink">{{ $news->authorName() }}</p>
                </div>
            @endif

            <div class="flex flex-wrap justify-end gap-2 border-t border-hairline pt-5">
                <x-admin.btn :href="route('admin.news.index')" variant="ghost">إلغاء</x-admin.btn>
                <x-admin.btn variant="primary">{{ $news ? 'حفظ التغييرات' : 'نشر الخبر' }}</x-admin.btn>
            </div>
        </div>
    </aside>
</form>
