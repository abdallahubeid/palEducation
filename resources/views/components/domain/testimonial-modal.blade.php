@props(['isStudent' => false])

{{--
    Review submission modal.

    🔴 Alpine on a PUBLIC page. Until now Alpine was admin-only by
    convention, and everything public was vanilla — that convention is
    changed here deliberately, not by accident. Alpine already ships in the
    shared bundle, so this costs nothing extra to load.

    ⚠️ Progressive, not required. The form is a real POST to a real route:
    with no JS the dialog stays open in the page flow and submitting reloads
    with a flash. Alpine only adds the overlay, the star picker and the async
    submit.

    🔴 Nothing identifying the reviewer is in this form. The student comes
    from the session server-side — a posted student_id would let any signed
    in account file a review under someone else's name.
--}}
<div
    x-data="{
        open: false,
        rate: 5,
        hovered: 0,
        description: '',
        busy: false,
        done: false,
        error: '',

        get stars() { return this.hovered || this.rate },

        close() {
            this.open = false;
            /* Reset only after the leave transition, so the panel does not
               visibly blank out while it is still fading. */
            setTimeout(() => { this.error = ''; if (this.done) { this.done = false; this.description = ''; } }, 250);
        },

        async submit($el) {
            if (this.busy) return;
            this.busy = true;
            this.error = '';

            try {
                const response = await fetch($el.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $el.querySelector('[name=_token]').value,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ rate: this.rate, description: this.description }),
                });

                if (response.ok) { this.done = true; return; }

                /* 422 carries field messages; anything else is a state the
                   user cannot act on, so it gets one plain sentence. */
                if (response.status === 422) {
                    const body = await response.json();
                    this.error = Object.values(body.errors ?? {}).flat()[0] ?? 'تعذّر إرسال الرأي.';
                } else if (response.status === 429) {
                    this.error = 'أرسلت عدة محاولات متتالية. انتظر دقيقة ثم أعد المحاولة.';
                } else {
                    this.error = 'تعذّر إرسال الرأي، حاول مرة أخرى.';
                }
            } catch {
                this.error = 'تعذّر الاتصال. تحقّق من الشبكة ثم أعد المحاولة.';
            } finally {
                this.busy = false;
            }
        },
    }"
    @open-testimonial-modal.window="open = true"
    @keydown.escape.window="open && close()"
>
    {{-- Overlay. x-cloak keeps it hidden until Alpine boots, otherwise it
         flashes across the page on every load. --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-[70] grid place-items-center overflow-y-auto p-4 sm:p-6"
         role="dialog" aria-modal="true" aria-labelledby="testimonial-modal-title">

        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="close()"
             class="fixed inset-0 bg-ink/50 backdrop-blur-sm"
             aria-hidden="true"></div>

        {{-- Panel --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-lg rounded-xl bg-canvas p-6 shadow-2xl sm:p-8">

            <button type="button" @click="close()"
                    class="absolute end-4 top-4 cursor-pointer rounded-lg p-1.5 text-stone
                           transition hover:bg-surface hover:text-ink"
                    aria-label="إغلاق">
                <x-icon name="close" class="size-5" />
            </button>

            @if ($isStudent)
                {{-- ═══ Signed-in student: the form ═══ --}}
                <template x-if="! done">
                    <div>
                        <h3 id="testimonial-modal-title" class="text-h4 font-bold text-ink">شاركنا رأيك</h3>
                        <p class="mt-2 text-caption text-steel">
                            {{-- Says plainly that nothing appears instantly, so
                                 a student does not refresh looking for it. --}}
                            اسمك وفرعك ومدينتك تُؤخذ من حسابك. يُنشر الرأي بعد مراجعته.
                        </p>

                        <form method="POST" action="{{ route('testimonials.store') }}"
                              @submit.prevent="submit($el)" class="mt-6 space-y-5">
                            @csrf

                            <div>
                                <span class="mb-2 block text-caption font-medium text-steel">تقييمك</span>

                                {{-- 🔴 Real radio inputs under the stars, not
                                     divs: the control stays keyboard reachable
                                     and submits without JS. --}}
                                <div class="flex items-center gap-1" @mouseleave="hovered = 0">
                                    @for ($star = 1; $star <= \App\Models\Testimonial::MAX_RATE; $star++)
                                        <label class="cursor-pointer p-0.5"
                                               @mouseenter="hovered = {{ $star }}">
                                            <input type="radio" name="rate" value="{{ $star }}"
                                                   x-model.number="rate" class="peer sr-only">
                                            <span class="sr-only">{{ $star }} من {{ \App\Models\Testimonial::MAX_RATE }}</span>
                                            <svg class="size-8 transition peer-focus-visible:ring-2
                                                        peer-focus-visible:ring-accent/40 rounded"
                                                 :class="stars >= {{ $star }} ? 'text-accent' : 'text-hairline-strong'"
                                                 viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="m12 2 3 6.5 7 .9-5 4.9 1.2 7L12 18l-6.2 3.3L7 14.3l-5-4.9 7-.9z" />
                                            </svg>
                                        </label>
                                    @endfor

                                    <span class="ms-2 num text-caption text-stone" x-text="rate + ' / 5'"></span>
                                </div>
                            </div>

                            <div>
                                <label for="testimonial-description"
                                       class="mb-1.5 block text-caption font-medium text-steel">رأيك</label>
                                <textarea id="testimonial-description" name="description"
                                          x-model="description" rows="5" minlength="20" maxlength="1000" required
                                          placeholder="ما الذي تغيّر في دراستك بعد استخدام المنصة؟"
                                          class="w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5
                                                 text-ui leading-relaxed text-ink transition placeholder:text-muted
                                                 focus:border-accent focus:outline-none focus:ring-2
                                                 focus:ring-accent/25"></textarea>
                                <p class="mt-1.5 text-micro text-stone">
                                    <span class="num" x-text="description.length"></span> / 1000
                                </p>
                            </div>

                            <p x-show="error" x-cloak x-text="error"
                               class="rounded-xl border border-error/25 bg-error/10 px-4 py-3 text-caption text-error-deep"></p>

                            <div class="flex justify-end gap-3">
                                <x-ui.button variant="secondary" size="md" type="button" x-on:click="close()">
                                    إلغاء
                                </x-ui.button>

                                <button type="submit" :disabled="busy"
                                        class="inline-flex h-12 cursor-pointer items-center justify-center gap-2
                                               rounded-full bg-accent px-6 text-ui font-semibold text-on-primary
                                               transition hover:bg-accent-deep disabled:pointer-events-none
                                               disabled:opacity-50 lg:h-11">
                                    <span x-text="busy ? 'جارٍ الإرسال…' : 'إرسال الرأي'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </template>

                {{-- Confirmation, in place of the form --}}
                <template x-if="done">
                    <div class="py-6 text-center">
                        <span class="mx-auto grid size-14 place-items-center rounded-full bg-accent/12 text-accent-deep">
                            <x-icon name="check" class="size-7" />
                        </span>
                        <h3 class="mt-4 text-h4 font-bold text-ink">وصلنا رأيك</h3>
                        <p class="mt-2 text-body text-steel">شكراً لك. سيظهر في الصفحة بعد مراجعته.</p>

                        <x-ui.button variant="secondary" size="md" type="button"
                                     x-on:click="close()" class="mt-6">
                            إغلاق
                        </x-ui.button>
                    </div>
                </template>
            @else
                {{-- ═══ Guest: an invitation, not a wall ═══ --}}
                <div class="py-2 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-accent/12 text-accent-deep">
                        <x-icon name="users" class="size-7" />
                    </span>

                    <h3 id="testimonial-modal-title" class="mt-4 text-h4 font-bold text-ink">
                        رأيك يهمّ زملاءك
                    </h3>
                    <p class="measure mx-auto mt-3 text-body text-steel">
                        يرجى تسجيل الدخول أو إنشاء حساب لمشاركة رأيك مع زملائك.
                    </p>

                    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                        <x-ui.button variant="primary" size="md" :href="route('auth.register')">
                            إنشاء حساب
                        </x-ui.button>
                        <x-ui.button variant="secondary" size="md" :href="route('auth.login')">
                            تسجيل الدخول
                        </x-ui.button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
