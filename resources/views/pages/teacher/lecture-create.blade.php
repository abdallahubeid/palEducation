@php
    $subjects = ['math' => 'الرياضيات', 'physics' => 'الفيزياء', 'business-math' => 'رياضيات تجارية'];
    $units = ['u1' => 'الوحدة 1 — النهايات والاتصال', 'u2' => 'الوحدة 2 — التفاضل', 'u3' => 'الوحدة 3 — التكامل'];
@endphp

<x-layouts.teacher :title="__('teacher.lecture_upload_title')">

    <div class="mx-auto max-w-3xl"
         data-wizard
         data-step1-title="{{ __('teacher.lecture_step1_title') }}"
         data-step2-title="{{ __('teacher.lecture_step2_title') }}">

        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('teacher.lecture_upload_title') }}</h1>
            <p class="measure mt-1 text-ui text-steel">{{ __('teacher.lecture_upload_subtitle') }}</p>
        </div>

        {{-- مؤشّر الخطوتين — نفس نمط شاشة إنشاء الحساب --}}
        <div class="mt-6">
            <div class="flex items-center gap-2" aria-hidden="true">
                <span data-wizard-bar="1" class="h-1.5 flex-1 rounded-full bg-accent transition-colors"></span>
                <span data-wizard-bar="2" class="h-1.5 flex-1 rounded-full bg-hairline transition-colors"></span>
            </div>

            <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
                <p class="text-caption text-stone" aria-live="polite">
                    <span data-wizard-status
                          data-template="{{ __('teacher.lecture_step_of', ['current' => ':current', 'total' => 2]) }}">{{ __('teacher.lecture_step_of', ['current' => 1, 'total' => 2]) }}</span>
                    <span class="mx-1" aria-hidden="true">·</span>
                    <span data-wizard-title class="font-medium text-steel">{{ __('teacher.lecture_step1_title') }}</span>
                </p>

                {{-- حفظ المسودّة التلقائي — إلزامي: فيديو ثقيل + انقطاع نت = ضياع عمل المعلم --}}
                <span data-draft-saved hidden class="inline-flex items-center gap-1.5 text-caption text-accent-deep">
                    <x-icon name="check-circle" class="size-4" />
                    {{ __('teacher.lecture_draft_saved') }}
                </span>
            </div>
        </div>

        <form data-demo-submit="{{ route('teacher.lectures') }}" class="mt-6">

            {{-- ══ الخطوة 1 — المحاضرة والفيديو ══ --}}
            <div data-wizard-step="1" class="flex flex-col gap-6">

                <x-ui.file-dropzone
                    name="video"
                    accept="video/mp4,video/webm"
                    icon="play"
                    :title="__('teacher.lecture_video_dropzone_title')"
                    :hint="__('teacher.lecture_video_hint')"
                    :max-label="__('teacher.lecture_video_max')"
                    simulate />

                <div class="tile flex flex-col gap-4 p-5 sm:p-6">
                    <x-ui.input
                        name="title"
                        :label="__('teacher.lecture_title_label')"
                        :placeholder="__('teacher.lecture_title_placeholder')"
                        required />

                    <x-ui.textarea
                        name="description"
                        :label="__('teacher.lecture_desc_label')"
                        :placeholder="__('teacher.lecture_desc_placeholder')"
                        :rows="3" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.select
                            name="subject"
                            :label="__('teacher.lecture_subject_label')"
                            :placeholder="__('teacher.file_subject_placeholder')"
                            :options="$subjects"
                            required />

                        <x-ui.select
                            name="unit"
                            :label="__('teacher.lecture_unit_label')"
                            :placeholder="__('teacher.lecture_unit_placeholder')"
                            :options="$units" />
                    </div>

                    <x-ui.input
                        name="order"
                        type="number"
                        :label="__('teacher.lecture_order_label')"
                        value="4"
                        dir="ltr"
                        class="sm:max-w-48" />
                    <p class="-mt-2 text-caption text-stone">{{ __('teacher.lecture_order_hint') }}</p>
                </div>

                {{-- إعدادات النشر --}}
                <div class="tile flex flex-col gap-1 p-5 sm:p-6">
                    <h2 class="text-h5 font-semibold text-ink">{{ __('teacher.lecture_publish_section') }}</h2>

                    <div class="mt-2 flex flex-col divide-y divide-hairline-soft">
                        <x-ui.toggle name="publish_now" :label="__('teacher.lecture_publish_now')" checked />
                        <p class="-mt-1 pb-2 text-caption text-stone">{{ __('teacher.lecture_publish_now_hint') }}</p>

                        <x-ui.toggle name="allow_download" :label="__('teacher.lecture_allow_download')" />
                        <p class="-mt-1 text-caption text-stone">{{ __('teacher.lecture_allow_download_hint') }}</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="button" data-wizard-next size="md" class="w-full sm:w-auto sm:min-w-56">
                        {{ __('teacher.lecture_next') }}
                        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                    </x-ui.button>
                </div>
            </div>

            {{-- ══ الخطوة 2 — الكويز (يُبنى في شاشة مخصّصة) ══ --}}
            <div data-wizard-step="2" hidden class="flex flex-col gap-6">
                <x-ui.alert variant="accent" icon="clipboard">
                    {{ __('teacher.quiz_builder_subtitle') }}
                </x-ui.alert>

                <div class="tile p-5 sm:p-6">
                    <x-ui.empty-state icon="clipboard"
                                      :title="__('teacher.quiz_empty_title')"
                                      :body="__('teacher.quiz_empty_body')">
                        <x-ui.button size="md" :href="route('teacher.lectures.quiz', 1)" class="mt-1">
                            <x-icon name="plus" class="size-4" />
                            {{ __('teacher.quiz_add_question') }}
                        </x-ui.button>
                    </x-ui.empty-state>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-between">
                    <x-ui.button type="button" data-wizard-prev variant="secondary" size="md" class="w-full sm:w-auto">
                        {{ __('teacher.lecture_prev') }}
                    </x-ui.button>

                    <x-ui.button type="submit" size="md" class="w-full sm:w-auto sm:min-w-44">
                        {{ __('teacher.quiz_save_draft') }}
                    </x-ui.button>
                </div>
            </div>
        </form>
    </div>

</x-layouts.teacher>
