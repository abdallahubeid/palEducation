@php
    $breadcrumb = [
        ['label' => __('student.breadcrumb_subjects'), 'href' => route('student.subjects.index')],
        ['label' => $subject->name],
    ];

    // أول محاضرة غير مكتملة — وجهة زرّ «تابع»؛ وإن اكتملت كلها فالأولى.
    $resumeLecture = $lecturesByUnit->flatten()->firstWhere('is_completed', false)
        ?? $lecturesByUnit->flatten()->first();

    $fmtDuration = function (?int $seconds): string {
        if (! $seconds) {
            return '—';
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;

        return sprintf('%d:%02d', $m, $s);
    };
@endphp

<x-layouts.student
    :title="$subject->name"
    :student-name="auth()->user()->displayName()"
    :student-avatar="auth()->user()->avatar_url">

    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <x-ui.breadcrumb :items="$breadcrumb" />

        <x-domain.subject-hero
            :name="$subject->name"
            :teacher="$teacher"
            :icon="$subject->icon ?: 'book'"
            :percent="$percent"
            :href="$resumeLecture ? route('student.lectures.show', $resumeLecture->slug) : '#'" />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">

            <div class="flex min-w-0 flex-col gap-5">
                <x-ui.tabs :items="['lectures' => __('student.tab_lectures'), 'files' => __('student.tab_files')]" active="lectures">

                    {{-- ═══ المحاضرات — مجمّعة بالوحدة ═══ --}}
                    <div data-tab-panel="lectures" class="mt-5 flex flex-col gap-3">
                        @forelse ($lecturesByUnit as $unitName => $unitLectures)
                            @php $unitDone = $unitLectures->where('is_completed', true)->count(); @endphp
                            <x-domain.module-accordion
                                :number="$loop->iteration"
                                :title="$unitName"
                                :lecture-count="$unitLectures->count()"
                                total-duration=""
                                :completed-count="$unitDone"
                                :open="$loop->first">
                                <ul class="divide-y divide-hairline-soft">
                                    @foreach ($unitLectures as $lecture)
                                        <li>
                                            <a href="{{ route('student.lectures.show', $lecture->slug) }}"
                                               class="flex items-center gap-3 px-4 py-3 transition hover:bg-surface/60">
                                                <span @class([
                                                    'grid size-6 shrink-0 place-items-center rounded-full text-micro',
                                                    'bg-accent/15 text-accent-deep' => $lecture->is_completed,
                                                    'bg-surface text-stone' => ! $lecture->is_completed,
                                                ])>
                                                    <x-icon :name="$lecture->is_completed ? 'check' : 'play'" class="size-3.5" />
                                                </span>
                                                <span class="min-w-0 flex-1 truncate text-ui text-ink">{{ $lecture->title }}</span>
                                                <span class="num shrink-0 text-caption text-stone" dir="ltr">
                                                    {{ $fmtDuration($lecture->video_duration_seconds) }}
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </x-domain.module-accordion>
                        @empty
                            <div class="tile">
                                <x-ui.empty-state icon="play"
                                                  title="لا محاضرات منشورة بعد"
                                                  body="محاضرات هذه المادة تظهر هنا فور نشرها من المعلّم." />
                            </div>
                        @endforelse
                    </div>

                    {{-- ═══ الملفات ═══ --}}
                    <div data-tab-panel="files" hidden class="mt-5">
                        <div class="tile p-3 sm:p-6">
                            @if ($files->isNotEmpty())
                                <div class="divide-y divide-hairline-soft">
                                    @foreach ($files as $file)
                                        <x-domain.file-row
                                            :name="$file->title"
                                            :size="$file->media?->humanSize() ?? '—'"
                                            :date="optional($file->published_at)->format('Y-m-d') ?? '—'"
                                            :href="route('student.library', ['subject' => $subject->id])" />
                                    @endforeach
                                </div>
                                <div class="mt-5">
                                    <x-ui.button variant="secondary" size="sm"
                                                 :href="route('student.library', ['subject' => $subject->id])">
                                        فتح في المكتبة
                                    </x-ui.button>
                                </div>
                            @else
                                <x-ui.empty-state icon="folder"
                                                  :title="__('student.files_empty_title')"
                                                  :body="__('student.files_empty_body')" />
                            @endif
                        </div>
                    </div>
                </x-ui.tabs>
            </div>

            {{-- ═══ الشريط الجانبي ═══ --}}
            <div class="lg:sticky lg:top-24">
                <div class="tile flex flex-col gap-5 p-6">
                    <h2 class="text-h5 font-semibold text-ink">{{ __('student.about_subject_title') }}</h2>

                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$teacher" size="md" />
                        <p class="truncate text-ui font-medium text-ink">{{ $teacher }}</p>
                    </div>

                    <dl class="flex flex-col gap-2.5 border-t border-hairline-soft pt-4 text-ui">
                        <div class="flex items-center justify-between">
                            <dt class="text-steel">{{ __('student.about_subject_lectures_count') }}</dt>
                            <dd class="num font-semibold text-ink">{{ $total }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-steel">المحاضرات المكتملة</dt>
                            <dd class="num font-semibold text-ink">{{ $completed }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-steel">{{ __('student.about_subject_files_count') }}</dt>
                            <dd class="num font-semibold text-ink">{{ $files->count() }}</dd>
                        </div>
                    </dl>

                    @if ($resumeLecture)
                        <x-ui.button variant="secondary" size="md" class="w-full"
                                     :href="route('student.lectures.show', $resumeLecture->slug)">
                            {{ $completed > 0 ? 'تابع' : __('student.start_subject_cta') }}
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-layouts.student>
