{{--
    The unsubscribe confirmation.

    🔴 This page changes NOTHING. Mail clients and security scanners fetch
    every link in a message before a human sees it, so the opt-out has to
    happen on the POST below, never on the GET that renders this.
--}}
<x-layouts.centered :title="__('newsletter.unsubscribe_title')" width="narrow">

    <div class="rounded-2xl border border-hairline bg-canvas p-6 text-center sm:p-8">

        <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
            <x-icon name="mail" class="size-5" />
        </span>

        @if ($alreadyDone)
            {{-- The second click on the same link. Says so plainly instead of
                 offering a button that would do nothing. --}}
            <h1 class="mt-5 text-h5 font-bold text-ink">اشتراكك ملغى بالفعل</h1>

            <p class="mt-3 text-ui leading-[1.9] text-steel">
                لن تصلك أي نشرة بريدية على
                <bdi dir="ltr" class="font-latin">{{ $subscriber->email }}</bdi>.
            </p>
        @else
            <h1 class="mt-5 text-h5 font-bold text-ink">إلغاء الاشتراك في النشرة</h1>

            <p class="mt-3 text-ui leading-[1.9] text-steel">
                ستتوقّف النشرة البريدية عن الوصول إلى
                <bdi dir="ltr" class="font-latin">{{ $subscriber->email }}</bdi>.
            </p>

            {{-- ⚠️ The signed URL is carried into the POST as well: the action
                 is as protected as the page that offers it, and neither step
                 needs a login — someone stopping mail is usually not a user
                 of the platform at all. --}}
            <form method="POST" action="{{ url()->signedRoute('newsletter.unsubscribe.confirm', ['subscriber' => $subscriber->id]) }}"
                  class="mt-6">
                @csrf
                <x-ui.button type="submit" class="w-full">تأكيد إلغاء الاشتراك</x-ui.button>
            </form>

            <p class="mt-4 text-caption text-stone">
                ضغطت على الرابط بالخطأ؟ أغلق هذه الصفحة ولن يتغيّر شيء.
            </p>
        @endif

        <a href="{{ route('home') }}"
           class="mt-6 inline-block text-caption font-semibold text-accent-deep transition hover:underline">
            العودة إلى الموقع
        </a>
    </div>

</x-layouts.centered>
