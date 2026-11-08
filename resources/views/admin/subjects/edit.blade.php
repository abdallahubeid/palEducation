<x-layouts.admin title="تعديل مادة" breadcrumbs="المحتوى الأكاديمي / المواد / تعديل">

    <x-admin.page-head :title="$subject->name" lede="تعديل بيانات المادة وإسناد معلّميها." />

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
            'action' => route('admin.subjects.update', $subject),
            'method' => 'PUT',
            'subject' => $subject,
        ])
    </div>

</x-layouts.admin>
