<x-layouts.admin title="تعديل خبر" breadcrumbs="المحتوى / الأخبار / تعديل">

    <x-admin.page-head :title="$news->title" lede="تعديل خبر منشور." />

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
        @include('admin.news._form', [
            'action' => route('admin.news.update', $news),
            'method' => 'PUT',
            'news' => $news,
        ])
    </div>

</x-layouts.admin>
