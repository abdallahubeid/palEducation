@php
    /*
     * ═══════════════════════════════════════════════════════════════════
     * إشعارات لوحة التحكّم — تُرسَم على الخادم لا بجافاسكربت الحزمة
     * ═══════════════════════════════════════════════════════════════════
     *
     * 🔴 لماذا لا نعتمد على مُصيِّر php-flasher؟ لأنه فشل هنا ثلاث مرّات
     * لأسباب مختلفة، وكلها صامتة:
     *   ١) أصوله لم تكن منشورة → 404 على flasher.min.js
     *   ٢) @flasher_render يستهلك الأظرف بلا echo → "envelopes":[]
     *   ٣) الحمولة تُحقن بـ "styles":[] → ملف CSS لا يُحمَّل أبداً،
     *      فالتوست يُرسَم بلا تنسيق أو لا يُرى إطلاقاً
     *
     * كلها أعطال في **طبقة العرض** لا في التخزين: الأظرف كانت تصل الجلسة
     * سليمة في كل مرّة. فالحلّ أن نقرأها هنا ونرسمها بأنفسنا — وهو نفس ما
     * تفعله لوحة veyra (FlashNotifier مخصّص يُرسَم في القالب).
     *
     * المكسب: صفر أصول خارجية · صفر CDN · صفر nonce · يعمل بلا شبكة.
     * وصيغة النداء في الكنترولرز لم تتغيّر: flash()->success('…').
     *
     * ⚠️ clear() إجباري بعد القراءة: بدونه يبقى الظرف في الجلسة ويُعرَض
     * ثانيةً في كل صفحة تالية.
     */
    $storage = app('flasher.storage_manager');
    $envelopes = $storage->all();
    $storage->clear();

    // كل صنف مكتوب حرفياً كاملاً — فاحص Tailwind لا ينفّذ PHP، فالتجميع
    // النصّي ("bg-{$tone}/10") لا يولّد أي CSS. فخّ وقع مرّتين هنا.
    $tones = [
        'success' => ['bg' => 'bg-accent/10', 'ring' => 'ring-accent/25', 'text' => 'text-accent-deep', 'icon' => 'check-circle'],
        'info' => ['bg' => 'bg-tag/10', 'ring' => 'ring-tag/25', 'text' => 'text-tag', 'icon' => 'bell'],
        'warning' => ['bg' => 'bg-warn/10', 'ring' => 'ring-warn/25', 'text' => 'text-warn-deep', 'icon' => 'alert'],
        'error' => ['bg' => 'bg-error/10', 'ring' => 'ring-error/25', 'text' => 'text-error-deep', 'icon' => 'alert'],
    ];
@endphp

@if (count($envelopes))
    {{-- role=status لا alert: الإشعار يُعلَن بلا مقاطعة ما يقرؤه المستخدم --}}
    <div data-flash
         role="status"
         aria-live="polite"
         class="pointer-events-none fixed inset-x-0 top-20 z-[60] flex flex-col items-center gap-2 px-4">

        @foreach ($envelopes as $envelope)
            @php $tone = $tones[$envelope->getType()] ?? $tones['info']; @endphp

            <div data-flash-item
                 class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl
                        {{ $tone['bg'] }} px-4 py-3 shadow-card ring-1 {{ $tone['ring'] }}
                        backdrop-blur-sm transition duration-300">

                <x-icon :name="$tone['icon']" class="mt-0.5 size-5 shrink-0 {{ $tone['text'] }}" />

                <p class="min-w-0 flex-1 text-ui font-medium {{ $tone['text'] }}">
                    {{ $envelope->getMessage() }}
                </p>

                <button type="button" data-flash-close
                        aria-label="إغلاق"
                        class="shrink-0 cursor-pointer rounded-md p-0.5 opacity-60 transition
                               hover:opacity-100 {{ $tone['text'] }}">
                    <x-icon name="close" class="size-4" />
                </button>
            </div>
        @endforeach
    </div>
@endif
