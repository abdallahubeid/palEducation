@php
    /*
     * Footer — newsletter copy, the legal lines, and four link columns.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 Each column's links live in ONE textarea as `Label | /href` per
     * line, not as eight separate inputs per column. The list has no
     * identity of its own, a field per link would mean twenty-eight boxes to
     * scroll past, and pasting a column from a document just works.
     */
    $defaults = \App\Models\Setting::FOOTER_DEFAULTS;
    $value = fn (string $f) => old('footer.'.$f, $settings['footer.'.$f] ?? $defaults['footer.'.$f]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        {{-- ═══ Newsletter ═══ --}}
        <div class="space-y-5">
            <div>
                <h3 class="text-ui font-bold text-ink">النشرة البريدية</h3>
                <p class="{{ $hintClass }}">العمود الأول من التذييل.</p>
            </div>

            <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
                <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
                <p class="text-caption text-warn-deep">التذييل يظهر في كل صفحة عامة، لكل زائر.</p>
            </div>

            <div>
                <label for="footer_news_title" class="{{ $labelClass }}">
                    العنوان <span class="text-error-deep">*</span>
                </label>
                <input type="text" id="footer_news_title" name="footer[newsletter_title]" maxlength="120"
                       value="{{ $value('newsletter_title') }}" class="{{ $inputClass }}">
                @error('footer.newsletter_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="footer_news_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
                <textarea id="footer_news_sub" name="footer[newsletter_sub_title]" rows="3" maxlength="400"
                          class="{{ $inputClass }} leading-relaxed">{{ $value('newsletter_sub_title') }}</textarea>
                @error('footer.newsletter_sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ═══ Link columns ═══ --}}
        @php
            $columnLabels = ['العمود الأول', 'العمود الثاني', 'العمود الثالث', 'العمود الرابع'];
        @endphp

        <div class="space-y-5 border-t border-hairline pt-6">
            <div>
                <h3 class="text-ui font-bold text-ink">أعمدة الروابط</h3>
                {{-- 🔁 Explicit fields, not a pipe-separated textarea. Every
                     destination is validated on save now, so a bad one is
                     refused with a message instead of quietly rewritten. --}}
                <p class="{{ $hintClass }}">
                    كل رابط حقلان: نصّه ووجهته. اترك الاثنين فارغين لتخطّي الصفّ،
                    وعموداً بلا عنوان أو بلا روابط لا يُعرض إطلاقاً.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                @foreach (\App\Models\Setting::FOOTER_COLUMNS as $index => $column)
                    <div class="space-y-4 rounded-xl border border-hairline bg-ground p-4 sm:p-5">

                        <h4 class="text-caption font-bold text-ink">{{ $columnLabels[$index] }}</h4>

                        <div>
                            <label for="footer_{{ $column }}_title" class="{{ $labelClass }}">عنوان العمود</label>
                            <input type="text" id="footer_{{ $column }}_title"
                                   name="footer[{{ $column }}_title]" maxlength="80"
                                   value="{{ $value($column.'_title') }}" class="{{ $inputClass }}">
                            @error('footer.'.$column.'_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                        </div>

                        @for ($slot = 1; $slot <= \App\Models\Setting::FOOTER_LINKS_PER_COLUMN; $slot++)
                            @php
                                $textKey = $column.'_link'.$slot.'_text';
                                $urlKey = $column.'_link'.$slot.'_url';
                            @endphp

                            <div class="grid gap-3 border-t border-hairline-soft pt-4 sm:grid-cols-2">
                                <div>
                                    <label for="footer_{{ $textKey }}" class="{{ $labelClass }}">
                                        نصّ الرابط {{ $slot }}
                                    </label>
                                    <input type="text" id="footer_{{ $textKey }}"
                                           name="footer[{{ $textKey }}]" maxlength="80"
                                           value="{{ $value($textKey) }}" class="{{ $inputClass }}">
                                    @error('footer.'.$textKey)<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="footer_{{ $urlKey }}" class="{{ $labelClass }}">
                                        وجهة الرابط {{ $slot }}
                                    </label>
                                    {{-- dir=ltr on the field alone: the target is
                                         latin while its label is Arabic. --}}
                                    <input type="text" id="footer_{{ $urlKey }}" dir="ltr"
                                           name="footer[{{ $urlKey }}]" maxlength="500" placeholder="/path"
                                           value="{{ $value($urlKey) }}" class="{{ $inputClass }}">
                                    @error('footer.'.$urlKey)<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endfor
                    </div>
                @endforeach
            </div>
        </div>
        {{-- ═══ Legal lines ═══ --}}
        <div class="grid gap-5 border-t border-hairline pt-6 sm:grid-cols-2">
            <div>
                <label for="footer_copyright" class="{{ $labelClass }}">
                    سطر الحقوق <span class="text-error-deep">*</span>
                </label>
                <input type="text" id="footer_copyright" name="footer[copyright_text]" maxlength="200"
                       value="{{ $value('copyright_text') }}" class="{{ $inputClass }}">
                {{-- 🔴 :year is substituted at render time. A hardcoded year
                     is correct for a few months and wrong forever after. --}}
                <p class="{{ $hintClass }}">
                    اكتب <bdi dir="ltr">:year</bdi> مكان السنة لتبقى محدَّثة تلقائياً.
                </p>
                @error('footer.copyright_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="footer_made_in" class="{{ $labelClass }}">سطر «صُنع في»</label>
                <input type="text" id="footer_made_in" name="footer[made_in_text]" maxlength="120"
                       value="{{ $value('made_in_text') }}" class="{{ $inputClass }}">
                @error('footer.made_in_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
