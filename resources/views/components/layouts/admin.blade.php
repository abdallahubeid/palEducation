@props([
    'title' => null,
    'breadcrumbs' => null,
])

@php
    $undo = session('admin.undo');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}"
      class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' · ' . __('admin.panel') : __('admin.panel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
    قشرة لوحة التحكّم — منقولة عن أسلوب لوحة veyra:
    شريط جانبي قابل للطيّ · شريط علوي لاصق بارتفاع 16 · لوح محتوى يمرّر وحده.

    ⚠️ بلا Alpine: Livewire 3 غير مثبّت في هذا المشروع وAlpine يأتي مضمّناً
    معه، فأي x-data لن يعمل. الطيّ والدرج والقوائم كلها vanilla عبر سمات
    data-* في resources/js/app.js — نفس عقد بقية المشروع.
--}}
<body class="h-full overflow-hidden bg-ground font-sans text-steel antialiased">

    {{-- الإشعارات فوق كل شيء، خارج اللوح الذي يمرّر — فلا تنزلق مع المحتوى --}}
    <x-admin.flash />

    <div data-admin-shell class="flex h-full w-full">

        <x-layouts.partials.admin-sidebar />

        <div class="flex h-full min-h-0 w-full min-w-0 flex-1 flex-col">

            <x-layouts.partials.admin-topbar :title="$title" :breadcrumbs="$breadcrumbs" />

            {{--
                🔴 زرّ «تراجع» بعد الحذف — نموذج PATCH حقيقي بـCSRF.
                الاستعادة تغيّر حالة الخادم، ومسار GET أو رابط داخل توست
                يحقنه JS بلا رمز CSRF ليس بديلاً: الأول يُنفَّذ بجالب مسبق
                في المتصفّح، والثاني يُرفض.
            --}}
            @if ($undo)
                <div class="border-b border-warn/25 bg-warn/10 px-4 py-2.5 sm:px-6">
                    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 text-caption font-medium text-warn-deep">
                            <x-icon name="alert" class="size-4 shrink-0" />
                            تم حذف العنصر. يمكنك التراجع الآن.
                        </span>

                        <form method="POST" action="{{ $undo['url'] }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border
                                           border-warn/40 bg-canvas px-3 py-1.5 text-caption font-semibold
                                           text-warn-deep transition hover:bg-warn/10">
                                <x-icon name="undo" class="size-4 rtl:-scale-x-100" />
                                {{ $undo['label'] }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <main data-admin-main
                  class="min-h-0 w-full min-w-0 flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8">
                <div class="mx-auto max-w-7xl">

                    {{-- ملخّص أخطاء النموذج — أعلى المحتوى كما في لوحة veyra --}}
                    @if ($errors->any())
                        <div class="mb-6 rounded-xl border border-error/30 bg-error/8 px-4 py-3">
                            <ul class="list-disc space-y-0.5 ps-5 text-caption text-error-deep">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    {{--
        🔴 الإشعارات تُرسَم على الخادم عبر x-admin.flash أعلاه — لا
        @flasher_render ولا حقن تلقائي ولا أصول من public/vendor.
        السبب الكامل مشروح في المكوّن نفسه. `inject_assets` مُطفأ في
        config/flasher.php حتى لا يُحقن سكربت ميّت قبل </body>.
    --}}

</body>
</html>
