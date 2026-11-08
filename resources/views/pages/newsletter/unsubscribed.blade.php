{{--
    The result page.

    ⚠️ A route of its own rather than a flash on the confirmation page: the
    signed link is spent, and re-rendering a page that still carried a POST
    form would invite a second unsubscribe of an already-unsubscribed address.
--}}
<x-layouts.centered :title="__('newsletter.unsubscribed_title')" width="narrow">

    <div class="rounded-2xl border border-hairline bg-canvas p-6 text-center sm:p-8">

        <span class="mx-auto grid size-12 place-items-center rounded-full bg-accent/10 text-accent-deep">
            <x-icon name="check" class="size-5" />
        </span>

        <h1 class="mt-5 text-h5 font-bold text-ink">تمّ إلغاء اشتراكك</h1>

        <p class="mt-3 text-ui leading-[1.9] text-steel">
            لن تصلك نشرات بريدية بعد الآن. يمكنك الاشتراك مجدداً في أي وقت من تذييل الموقع.
        </p>

        <a href="{{ route('home') }}"
           class="mt-6 inline-block text-caption font-semibold text-accent-deep transition hover:underline">
            العودة إلى الموقع
        </a>
    </div>

</x-layouts.centered>
