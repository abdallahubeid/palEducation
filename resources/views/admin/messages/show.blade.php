<x-layouts.admin title="الرسالة" breadcrumbs="النظام / رسائل التواصل / عرض">

    <x-admin.page-head :title="$message->name" :lede="$message->subject_type->label()">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.messages.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">

        {{-- ═══ The message and the reply form ═══ --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">
                <h3 class="text-ui font-bold text-ink">نصّ الرسالة</h3>

                {{-- whitespace-pre-line so the sender's own paragraph breaks
                     survive; the value is still escaped by Blade. --}}
                <p class="mt-4 whitespace-pre-line text-body leading-[1.9] text-steel">{{ $message->message }}</p>
            </div>

            @if ($message->isReplied())
                {{-- The reply already sent, shown as a record rather than an
                     editable field: it is already in someone's inbox, and an
                     edit here would change the archive without changing what
                     they received. --}}
                <div class="rounded-xl border border-accent/25 bg-accent/8 p-5 sm:p-6 lg:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-ui font-bold text-ink">الردّ المُرسَل</h3>
                        <span class="text-micro text-stone" dir="ltr">
                            {{ $message->replied_at->format('Y-m-d H:i') }}
                        </span>
                    </div>

                    <p class="mt-4 whitespace-pre-line text-body leading-[1.9] text-steel">{{ $message->reply_message }}</p>

                    @if ($message->admin)
                        <p class="mt-4 text-micro text-stone">
                            أرسله: {{ $message->admin->displayName() ?: $message->admin->email }}
                        </p>
                    @endif
                </div>
            @endif

            @can('reply-messages')
                <form method="POST" action="{{ route('admin.messages.reply', $message) }}"
                      class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">
                    @csrf

                    <h3 class="text-ui font-bold text-ink">
                        {{ $message->isReplied() ? 'إرسال ردّ آخر' : 'الردّ على الرسالة' }}
                    </h3>

                    {{-- Says plainly that submitting sends an email. Nothing
                         else on the screen would tell an admin that this
                         button leaves the building. --}}
                    <p class="mt-1.5 text-micro text-stone">
                        يُرسَل الردّ بريداً إلى <bdi dir="ltr">{{ $message->email }}</bdi>.
                    </p>

                    <div class="mt-5">
                        <label for="reply_message" class="mb-1.5 block text-caption font-medium text-steel">
                            نصّ الردّ <span class="text-error-deep">*</span>
                        </label>
                        <textarea id="reply_message" name="reply_message" rows="7" minlength="10" maxlength="2000"
                                  class="w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui
                                         leading-relaxed text-ink transition placeholder:text-muted
                                         focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25"
                                  placeholder="اكتب ردّك هنا…">{{ old('reply_message') }}</textarea>
                        @error('reply_message')
                            <p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-admin.btn variant="primary">إرسال الردّ</x-admin.btn>
                    </div>
                </form>
            @endcan
        </div>

        {{-- ═══ Sender details ═══ --}}
        <aside class="space-y-4 rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
            <h3 class="text-ui font-bold text-ink">بيانات المرسِل</h3>

            <dl class="space-y-4 text-ui">
                <div>
                    <dt class="text-caption text-stone">الاسم</dt>
                    <dd class="mt-0.5 font-semibold text-ink">{{ $message->name }}</dd>
                </div>

                <div>
                    <dt class="text-caption text-stone">البريد</dt>
                    {{-- A live mailto so a reply can also be sent by hand. --}}
                    <dd class="mt-0.5">
                        <a href="mailto:{{ $message->email }}"
                           class="font-semibold text-accent-deep transition hover:underline">
                            <bdi dir="ltr">{{ $message->email }}</bdi>
                        </a>
                    </dd>
                </div>

                @if ($message->phone)
                    <div>
                        <dt class="text-caption text-stone">الهاتف</dt>
                        <dd class="mt-0.5">
                            <a href="tel:{{ \App\Models\Setting::dialable($message->phone) }}"
                               class="font-semibold text-accent-deep transition hover:underline">
                                <bdi dir="ltr" class="num">{{ $message->phone }}</bdi>
                            </a>
                        </dd>
                    </div>
                @endif

                <div>
                    <dt class="text-caption text-stone">الموضوع</dt>
                    <dd class="mt-0.5 text-steel">{{ $message->subject_type->label() }}</dd>
                </div>

                <div>
                    <dt class="text-caption text-stone">وصلت</dt>
                    <dd class="mt-0.5 tabular-nums text-steel" dir="ltr">
                        {{ $message->created_at->format('Y-m-d H:i') }}
                    </dd>
                </div>
            </dl>
        </aside>
    </div>

</x-layouts.admin>
