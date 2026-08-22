@php
    $subjects = ['math' => 'الرياضيات', 'physics' => 'الفيزياء', 'business-math' => 'رياضيات تجارية'];
@endphp

<x-layouts.teacher :title="__('teacher.file_upload_title')">

    <div class="mx-auto max-w-3xl">

        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('teacher.file_upload_title') }}</h1>
            <p class="measure mt-1 text-ui text-steel">{{ __('teacher.file_upload_subtitle') }}</p>
        </div>

        <form data-demo-submit="{{ route('teacher.lectures') }}" class="mt-6 flex flex-col gap-6">

            <x-ui.file-dropzone
                name="library_file"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg"
                icon="folder"
                :title="__('teacher.file_dropzone_title')"
                :hint="__('teacher.file_dropzone_hint')"
                :max-label="__('teacher.file_dropzone_max')"
                simulate />

            <div class="tile flex flex-col gap-4 p-5 sm:p-6">
                <x-ui.select
                    name="subject"
                    :label="__('teacher.file_subject_label')"
                    :placeholder="__('teacher.file_subject_placeholder')"
                    :options="$subjects"
                    required />

                <x-ui.input
                    name="title"
                    :label="__('teacher.file_title_label')"
                    :placeholder="__('teacher.file_title_placeholder')"
                    required />

                <x-ui.textarea
                    name="description"
                    :label="__('teacher.file_desc_label')"
                    :placeholder="__('teacher.file_desc_placeholder')"
                    :hint="__('teacher.file_desc_hint')"
                    :rows="3" />
            </div>

            {{-- الثانوي أولاً ثم الأساسي — نفس ترتيب ui/confirm-dialog --}}
            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <x-ui.button variant="secondary" size="md"
                             :href="route('teacher.dashboard')"
                             class="w-full sm:w-auto">
                    {{ __('teacher.file_cancel') }}
                </x-ui.button>

                <x-ui.button type="submit" size="md" class="w-full sm:w-auto sm:min-w-44">
                    {{ __('teacher.file_submit') }}
                </x-ui.button>
            </div>
        </form>
    </div>

</x-layouts.teacher>
