@php
    $lectureTitle = 'التكامل غير المحدد';

    $timeOptions = [
        ''   => __('teacher.quiz_time_none'),
        '10' => __('teacher.quiz_time_10'),
        '15' => __('teacher.quiz_time_15'),
        '30' => __('teacher.quiz_time_30'),
    ];
@endphp

<x-layouts.teacher :title="__('teacher.quiz_builder_title')">

    {{--
        🔴 أعقد نموذج في المشروع. بلا Livewire وبلا Alpine، فالبناء بـ
        <template> يُستنسخ ويُدرج — والحالة تعيش في الـDOM نفسه لا في كائن
        JS موازٍ. هذا مقصود: عند تثبيت Livewire يُنقل نفس الـmarkup بلا
        إعادة تصميم، ولا توجد حالة ثانية تحتاج مزامنة.
    --}}
    <div class="mx-auto max-w-4xl" data-quiz-builder>

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <x-ui.breadcrumb :items="[
                    ['label' => __('teacher.nav_lectures'), 'href' => route('teacher.lectures')],
                    ['label' => $lectureTitle],
                ]" />
                <h1 class="mt-2 text-h2 font-bold text-ink">{{ __('teacher.quiz_builder_title') }}</h1>
                <p class="measure mt-1 text-ui text-steel">{{ __('teacher.quiz_builder_subtitle') }}</p>
            </div>
        </div>

        {{-- إعدادات الكويز --}}
        <section class="tile mt-6 flex flex-col gap-4 p-5 sm:p-6">
            <h2 class="text-h5 font-semibold text-ink">{{ __('teacher.quiz_settings') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-ui.select name="time_limit"
                                 :label="__('teacher.quiz_time_limit')"
                                 :options="$timeOptions"
                                 value="" />
                    <p class="mt-1.5 text-caption text-stone">{{ __('teacher.quiz_time_hint') }}</p>
                </div>

                <x-ui.input name="pass_mark"
                            type="number"
                            :label="__('teacher.quiz_pass_mark')"
                            value="50"
                            dir="ltr"
                            class="sm:max-w-40" />
            </div>
        </section>

        {{-- رأس قائمة الأسئلة --}}
        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-h4 font-semibold text-ink">
                {{ __('teacher.quiz_questions') }}
                <span class="num ms-1 text-ui font-normal text-stone">(<span data-question-count>0</span>)</span>
            </h2>

            <p class="text-caption text-stone">
                {{ __('teacher.quiz_total_points') }}:
                <span class="num font-semibold text-ink" data-total-points>0</span>
            </p>
        </div>

        {{-- حاوية الأسئلة --}}
        <div data-questions class="mt-4 flex flex-col gap-4"></div>

        {{-- حالة فارغة --}}
        <div data-questions-empty class="tile mt-4">
            <x-ui.empty-state icon="clipboard"
                              :title="__('teacher.quiz_empty_title')"
                              :body="__('teacher.quiz_empty_body')" />
        </div>

        <button type="button" data-add-question
                class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed
                       border-hairline-strong bg-surface-soft px-6 py-5 text-ui font-semibold text-steel
                       transition hover:border-accent hover:bg-accent-soft/40 hover:text-accent-deep">
            <x-icon name="plus" class="size-5" />
            {{ __('teacher.quiz_add_question') }}
        </button>

        {{-- إجراءات --}}
        <div class="mt-8 flex flex-col gap-2 sm:flex-row sm:justify-end">
            <x-ui.button variant="ghost" size="md" class="w-full sm:w-auto">
                {{ __('teacher.quiz_preview') }}
            </x-ui.button>

            <x-ui.button variant="secondary" size="md" class="w-full sm:w-auto">
                {{ __('teacher.quiz_save_draft') }}
            </x-ui.button>

            {{-- معطّل حتى يوجد سؤال — م-3: لا نشر بلا كويز --}}
            <x-ui.button data-publish size="md" class="w-full sm:w-auto sm:min-w-44" disabled>
                {{ __('teacher.quiz_publish') }}
            </x-ui.button>
        </div>

        <p data-publish-hint class="mt-2 text-end text-caption text-warn-deep">
            {{ __('teacher.quiz_publish_blocked') }}
        </p>
    </div>

    {{-- ══════ قالب السؤال — يُستنسخ بـJS ══════ --}}
    <template data-question-template>
        <article class="tile flex flex-col gap-4 p-5 sm:p-6" data-question>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-h5 font-semibold text-ink">
                    {{ __('teacher.quiz_question_n', ['n' => '']) }}<span class="num" data-question-number></span>
                </h3>

                <div class="flex items-center gap-1">
                    <button type="button" data-move-up
                            class="grid size-11 place-items-center rounded-md text-steel transition hover:bg-surface hover:text-ink lg:size-9"
                            aria-label="{{ __('teacher.quiz_move_up') }}" title="{{ __('teacher.quiz_move_up') }}">
                        <x-icon name="chevron-down" class="size-4 rotate-180" />
                    </button>

                    <button type="button" data-move-down
                            class="grid size-11 place-items-center rounded-md text-steel transition hover:bg-surface hover:text-ink lg:size-9"
                            aria-label="{{ __('teacher.quiz_move_down') }}" title="{{ __('teacher.quiz_move_down') }}">
                        <x-icon name="chevron-down" class="size-4" />
                    </button>

                    <button type="button" data-remove-question
                            class="grid size-11 place-items-center rounded-md text-steel transition hover:bg-error/10 hover:text-error-deep lg:size-9"
                            aria-label="{{ __('teacher.quiz_remove_question') }}" title="{{ __('teacher.quiz_remove_question') }}">
                        <x-icon name="trash" class="size-4" />
                    </button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_10rem]">
                <x-ui.select name="qtype" data-question-type
                             :label="__('teacher.quiz_question_type')"
                             :options="[
                                 'mcq' => __('teacher.quiz_type_mcq'),
                                 'truefalse' => __('teacher.quiz_type_truefalse'),
                                 'short' => __('teacher.quiz_type_short'),
                             ]"
                             value="mcq" />

                <x-ui.input name="points" type="number" data-question-points
                            :label="__('teacher.quiz_points')" value="1" dir="ltr" />
            </div>

            <x-ui.textarea name="question_text"
                           :label="__('teacher.quiz_question_text')"
                           :placeholder="__('teacher.quiz_question_placeholder')"
                           :rows="2" />

            {{-- ── اختيار من متعدّد ── --}}
            <div data-qtype-block="mcq" class="flex flex-col gap-3">
                <p class="text-ui text-steel">
                    {{ __('teacher.quiz_options') }}
                    <span class="text-caption text-stone">— {{ __('teacher.quiz_mark_correct') }}</span>
                </p>

                <div data-options class="flex flex-col gap-2"></div>

                <button type="button" data-add-option
                        class="inline-flex h-11 w-fit items-center gap-2 rounded-full border border-hairline-strong
                               bg-canvas px-4 text-caption font-semibold text-ink transition hover:bg-surface lg:h-9">
                    <x-icon name="plus" class="size-4" />
                    {{ __('teacher.quiz_add_option') }}
                </button>
            </div>

            {{-- ── صح أو خطأ ── --}}
            <div data-qtype-block="truefalse" hidden class="flex flex-col gap-2">
                <p class="text-ui text-steel">{{ __('teacher.quiz_mark_correct') }}</p>
                <div data-truefalse class="flex flex-col gap-2"></div>
            </div>

            {{-- ── إجابة قصيرة ── --}}
            <div data-qtype-block="short" hidden>
                <x-ui.input name="short_answer"
                            :label="__('teacher.quiz_short_answer_label')"
                            :placeholder="__('teacher.quiz_short_answer_placeholder')"
                            :hint="__('teacher.quiz_short_answer_hint')" />
            </div>

            <x-ui.textarea name="explanation"
                           :label="__('teacher.quiz_explanation')"
                           :placeholder="__('teacher.quiz_explanation_placeholder')"
                           :rows="2" />
        </article>
    </template>

    {{-- قالب خيار MCQ --}}
    <template data-option-template>
        <div class="flex items-center gap-2" data-option>
            <label class="group flex shrink-0 cursor-pointer items-center" data-correct-label>
                <input type="radio" data-correct-radio class="peer sr-only">
                <span class="grid size-6 place-items-center rounded-full border border-hairline-strong bg-canvas transition
                             group-hover:border-accent
                             peer-checked:border-accent peer-checked:bg-accent peer-checked:[&>svg]:opacity-100
                             peer-focus-visible:shadow-[0_0_0_3px_rgb(82_95_225_/_0.32)]">
                    <x-icon name="check" class="size-3.5 text-on-primary opacity-0 transition-opacity" />
                </span>
            </label>

            <input type="text" data-option-input
                   placeholder="{{ __('teacher.quiz_option_placeholder') }}"
                   class="h-12 min-w-0 flex-1 rounded-md border border-hairline bg-canvas px-4 text-ui text-ink
                          transition placeholder:text-muted hover:border-hairline-strong
                          focus-visible:border-accent focus-visible:outline-none lg:h-11">

            <button type="button" data-remove-option
                    class="grid size-11 shrink-0 place-items-center rounded-md text-steel transition
                           hover:bg-error/10 hover:text-error-deep lg:size-9"
                    aria-label="{{ __('teacher.quiz_remove_option') }}">
                <x-icon name="close" class="size-4" />
            </button>
        </div>
    </template>

    {{-- قالب صح/خطأ --}}
    <template data-truefalse-template>
        <label class="group flex cursor-pointer items-center gap-3 rounded-lg border border-hairline bg-canvas px-4 py-3
                      transition hover:border-hairline-strong">
            <input type="radio" data-tf-radio class="peer sr-only">
            <span class="grid size-6 shrink-0 place-items-center rounded-full border border-hairline-strong bg-canvas transition
                         group-hover:border-accent
                         peer-checked:border-accent peer-checked:bg-accent peer-checked:[&>svg]:opacity-100
                         peer-focus-visible:shadow-[0_0_0_3px_rgb(82_95_225_/_0.32)]">
                <x-icon name="check" class="size-3.5 text-on-primary opacity-0 transition-opacity" />
            </span>
            <span data-tf-label class="text-ui text-ink"></span>
        </label>
    </template>

    <x-ui.confirm-dialog
        id="question-delete"
        :title="__('teacher.quiz_delete_question_title')"
        :body="__('teacher.quiz_delete_question_body')"
        :confirm-label="__('teacher.quiz_remove_question')"
        variant="danger" />

</x-layouts.teacher>
