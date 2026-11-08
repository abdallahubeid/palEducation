<x-layouts.admin title="إضافة مادة" breadcrumbs="المحتوى الأكاديمي / المواد / إضافة">

    <x-admin.page-head title="إضافة مادة" lede="المادة تُعلَّق بها المحاضرات والملفات والمعلّمون." />

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-error/25 bg-error/8 px-4 py-3">
            <ul class="space-y-1 text-caption text-error-deep">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6">
        @include('admin.subjects._form', [
            'action' => route('admin.subjects.store'),
            'method' => 'POST',
            'subject' => null,
        ])
    </div>

</x-layouts.admin>
