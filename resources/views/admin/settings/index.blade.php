@php
    /*
     * الإعدادات — صفحة واحدة، شريط تبويبات، لوح لكل تبويب.
     *
     * التبويب المفتوح يأتي من `?tab=` (يضبطه الخادم بعد الحفظ) وإلّا فأول
     * تبويب. الـJS يبدّل بعدها بلا رحلة خادم.
     */
    $active = array_key_exists(request()->query('tab'), $groups)
        ? request()->query('tab')
        : array_key_first($groups);
@endphp

<x-layouts.admin title="الإعدادات" breadcrumbs="محتوى الصفحة الرئيسية / الإعدادات">

    <x-admin.page-head
        title="الإعدادات"
        lede="إعدادات محتوى الصفحة الرئيسية." />

    {{--
        شريط التبويبات — أزرار لا روابط: التبديل يحدث على العميل بلا تنقّل.

        🔴 المؤشّر النشط حدّ سفلي بسماكة 2 لا خلفية ممتلئة، و`-mb-px` يجعله
        يجلس فوق حدّ الشريط تماماً فلا يظهر خطّان بسماكة مضاعفة.

        ⚠️ ينزلق أفقياً على الشاشات الضيّقة: `overflow-x-auto` على الشريط
        و`shrink-0` على كل تبويب — بدونهما تلتفّ التبويبات أو تُقصّ.
    --}}
    <div data-settings-tabs class="mt-6">
        <div class="border-b border-hairline">
            <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="أقسام الإعدادات" role="tablist">
                @foreach ($groups as $key => $group)
                    <button type="button"
                            role="tab"
                            data-settings-tab="{{ $key }}"
                            id="settings-tab-{{ $key }}"
                            aria-controls="settings-panel-{{ $key }}"
                            aria-selected="{{ $key === $active ? 'true' : 'false' }}"
                            @class([
                                'flex shrink-0 cursor-pointer items-center gap-2 border-b-2 px-4 py-3 text-ui font-medium transition',
                                'border-accent text-accent-deep' => $key === $active,
                                'border-transparent text-steel hover:border-hairline-strong hover:text-ink' => $key !== $active,
                            ])>
                        <x-icon :name="$group['icon']" class="size-4 shrink-0" />
                        {{ $group['label'] }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{--
            🔴 كل الألواح تُرسَل مرئية، والـJS يخفي غير النشط عند التحميل.
            العكس (إخفاء خادمي) يجعل الصفحة عديمة الفائدة تماماً بلا
            JavaScript — هنا أسوأ حالة أن تظهر النماذج تحت بعضها، وكلها
            تعمل.
        --}}
        @foreach ($groups as $key => $group)
            <div data-settings-panel="{{ $key }}"
                 id="settings-panel-{{ $key }}"
                 role="tabpanel"
                 aria-labelledby="settings-tab-{{ $key }}"
                 class="mt-6">
                @include('admin.settings.tabs._'.$group['partial'], [
                    'settings' => $settings,
                    'action' => route($group['route']),
                ])
            </div>
        @endforeach
    </div>

</x-layouts.admin>
