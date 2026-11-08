<x-layouts.admin title="إرسال نشرة" breadcrumbs="النشرة البريدية / إرسال نشرة">

    <x-admin.page-head
        title="إرسال نشرة بريدية"
        lede="تُرسَل الرسالة في الخلفية، واحدة لكل مستلم، مع رابط إلغاء اشتراك خاص به.">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.newsletter.subscribers.index')" variant="secondary">
                المشتركون
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @if ($subscribers->isEmpty())
        {{-- 🔴 No form at all when there is nobody to mail. A compose screen
             whose send button can never succeed wastes the writing. --}}
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="mail" class="size-5" />
            </span>
            <p class="mt-4 text-ui font-semibold text-ink">لا مشتركين نشطين</p>
            <p class="mt-1 text-caption text-stone">
                لا يمكن إرسال نشرة قبل أن يشترك أحد من تذييل الموقع.
            </p>
        </div>
    @else
        @if ($source)
            {{-- 🔴 Says plainly that this is a NEW campaign, not a resend of
                 the old one. Without it an admin could reasonably believe the
                 original is being re-delivered to its original recipients. --}}
            <div class="mt-6 rounded-xl border border-accent/25 bg-accent/8 px-4 py-3">
                <p class="text-caption text-ink">
                    نسخة من حملة «{{ $source->subject }}» — تُرسَل كحملة جديدة إلى من تختاره الآن.
                </p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-error/25 bg-error/8 px-4 py-3">
                <ul class="space-y-1 text-caption text-error-deep">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{--
            x-data carries the initial recipient count so the confirmation
            text is correct before any interaction. The confirm dialog reads
            data-confirm at submit time, so the count is written into that
            attribute reactively — a static string would say "0 recipients"
            on the very first send.
        --}}
        <form method="POST" action="{{ route('admin.newsletter.send.store') }}"
              x-data="campaignAudience({{ $subscribers->count() }})"
              :data-confirm-title="`إرسال النشرة إلى ${count} مشترك؟`"
              data-confirm="تُرسَل في الخلفية ولا يمكن سحبها بعد المغادرة."
              data-confirm-action="نعم، أرسِل"
              @submit="guard($event)"
              class="mt-6 grid gap-6 lg:grid-cols-3">
            @csrf

            {{-- ═══ The message ═══ --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">
                    <h3 class="text-ui font-bold text-ink">نصّ النشرة</h3>

                    <div class="mt-5">
                        <label for="subject" class="mb-1.5 block text-caption font-medium text-steel">
                            عنوان الرسالة <span class="text-error-deep">*</span>
                        </label>
                        <input id="subject" name="subject" type="text" maxlength="190" required
                               value="{{ old('subject', $source?->subject) }}"
                               placeholder="مثال: مستجدّات الفصل الثاني"
                               class="h-11 w-full rounded-xl border border-hairline bg-canvas px-3 text-ui
                                      text-ink transition placeholder:text-muted focus:border-accent
                                      focus:outline-none focus:ring-2 focus:ring-accent/25">
                        @error('subject')
                            <p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-5">
                        <label for="body" class="mb-1.5 block text-caption font-medium text-steel">
                            المحتوى <span class="text-error-deep">*</span>
                        </label>
                        <textarea id="body" name="body" rows="12" minlength="20" maxlength="20000" required
                                  placeholder="اكتب محتوى النشرة هنا…"
                                  class="w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui
                                         leading-[1.9] text-ink transition placeholder:text-muted
                                         focus:border-accent focus:outline-none
                                         focus:ring-2 focus:ring-accent/25">{{ old('body', $source?->body) }}</textarea>
                        {{-- ⚠️ Says plainly that the body is plain text. The
                             mail view escapes it and converts newlines, so
                             pasted markup arrives as visible characters
                             rather than as formatting. --}}
                        <p class="mt-1.5 text-micro text-stone">
                            نصّ عادي — تُحفَظ الأسطر الجديدة كما هي، ولا تُفسَّر وسوم HTML.
                        </p>
                        @error('body')
                            <p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ═══ Audience ═══ --}}
            <aside class="lg:col-span-1">
                <div class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-ui font-bold text-ink">المستلمون</h3>
                        <span class="rounded-md bg-accent/10 px-2 py-1 text-micro font-semibold text-accent-deep">
                            <span class="tabular-nums" x-text="count"></span> / {{ $subscribers->count() }}
                        </span>
                    </div>

                    <label class="mt-4 flex cursor-pointer items-center gap-2 rounded-lg bg-surface px-3 py-2
                                  text-caption font-semibold text-steel">
                        <input type="checkbox" checked
                               @change="toggleAll($event.target.checked)"
                               :checked="allChecked" :indeterminate="count > 0 && ! allChecked"
                               class="size-4 cursor-pointer rounded border-hairline-strong text-accent
                                      focus:ring-2 focus:ring-accent/25">
                        تحديد الكل
                    </label>

                    {{-- Filters the rendered list only — it never changes what
                         is checked, so narrowing the search cannot silently
                         drop someone from the send. --}}
                    <div class="mt-3">
                        <label for="audience-search" class="sr-only">تصفية المستلمين</label>
                        <input id="audience-search" type="search" dir="ltr" placeholder="تصفية بالبريد…"
                               @input="filter($event.target.value)"
                               class="h-9 w-full rounded-lg border border-hairline bg-canvas px-3 font-latin
                                      text-caption text-ink transition placeholder:text-muted
                                      focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                    </div>

                    {{-- max-h + overflow: the list is as long as the mailing
                         list, and an unbounded column would push the send
                         button off the bottom of the page. --}}
                    <ul class="mt-3 max-h-96 space-y-1.5 overflow-y-auto pe-1">
                        @foreach ($subscribers as $subscriber)
                            <li data-audience-row data-email="{{ $subscriber->email }}">
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border
                                              border-hairline px-3 py-2 transition hover:bg-surface/60
                                              has-checked:border-accent/40 has-checked:bg-accent/8">
                                    {{-- Checked by default: the ordinary case
                                         is mailing the whole list, and making
                                         that the starting state means the
                                         common send is one click. --}}
                                    <input type="checkbox" name="recipients[]" value="{{ $subscriber->id }}"
                                           checked data-audience-check @change="recount"
                                           class="mt-0.5 size-4 shrink-0 cursor-pointer rounded
                                                  border-hairline-strong text-accent
                                                  focus:ring-2 focus:ring-accent/25">
                                    <span class="min-w-0">
                                        <bdi dir="ltr" class="block truncate font-latin text-caption text-ink">{{ $subscriber->email }}</bdi>
                                        <span class="mt-0.5 block text-micro tabular-nums text-stone" dir="ltr">
                                            {{ $subscriber->subscribed_at?->format('Y-m-d') ?? '—' }}
                                        </span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Shown when the filter matches nothing, so an empty box
                         is not mistaken for an empty list. --}}
                    <p data-audience-empty hidden class="mt-3 text-center text-caption text-stone">
                        لا مشترك يطابق البحث.
                    </p>

                    {{-- 🔴 Blocks the submit when nothing is checked. The
                         server refuses it too; this exists so the admin does
                         not lose a written newsletter to a round trip. --}}
                    <p x-show="count === 0" x-cloak class="mt-3 text-caption text-error-deep">
                        اختر مستلماً واحداً على الأقل.
                    </p>

                    <div class="mt-5">
                        <x-admin.btn variant="primary" class="w-full" ::disabled="count === 0">
                            إرسال النشرة
                        </x-admin.btn>
                    </div>
                </div>
            </aside>
        </form>
    @endif

</x-layouts.admin>
