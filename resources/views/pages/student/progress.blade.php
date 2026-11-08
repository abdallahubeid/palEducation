<x-layouts.student
    :title="__('student.progress_title')"
    :student-name="auth()->user()->displayName()"
    :student-avatar="auth()->user()->avatar_url">

    <div class="mx-auto flex max-w-5xl flex-col gap-6">
        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('student.progress_title') }}</h1>
            <p class="mt-1 text-ui text-steel">أرقام حقيقية من نشاطك على محتوى فرعك.</p>
        </div>

        @if (! $hasSubjects)
            <div class="tile">
                <x-ui.empty-state icon="book"
                                  title="لا مواد في فرعك بعد"
                                  body="حين تُنشر مواد فرعك، يبدأ تتبّع تقدّمك هنا." />
            </div>
        @elseif (! $hasActivity)
            <div class="tile">
                <x-ui.empty-state icon="chart"
                                  title="لم يبدأ نشاطك بعد"
                                  body="شاهد أول محاضرة أو نزّل أول ملف، وسيظهر تقدّمك هنا." />
            </div>
        @else
            {{-- ═══ الأرقام الكبرى ═══ --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-domain.stat-card icon="check"
                                    :value="$completedLectures . ' / ' . $totalLectures"
                                    label="محاضرات مكتملة" tone="accent" />
                <x-domain.stat-card icon="play"
                                    :value="(string) $inProgressLectures"
                                    label="محاضرات قيد المشاهدة" tone="tag" />
                <x-domain.stat-card icon="folder"
                                    :value="$accessedFiles . ' / ' . $totalFiles"
                                    label="ملفات اطُّلع عليها" tone="amber" />
                <x-domain.stat-card icon="chart"
                                    :value="$watchedHours . ' س'"
                                    label="ساعات المشاهدة" tone="warn" />
            </div>

            {{-- ═══ نسبة الإنجاز الأكاديمي ═══ --}}
            <div class="tile flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:gap-8">
                <div class="shrink-0">
                    <x-ui.score-ring :percent="$completionRate" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-h5 font-semibold text-ink">نسبة الإنجاز الأكاديمي</h2>
                    <p class="mt-1.5 text-ui text-steel">
                        أكملت <span class="num font-semibold text-ink">{{ $completedLectures }}</span>
                        من <span class="num font-semibold text-ink">{{ $totalLectures }}</span>
                        محاضرة منشورة في فرعك.
                    </p>
                </div>
            </div>

            {{-- ═══ التقدّم حسب المادة ═══ --}}
            <div class="tile flex flex-col gap-5 p-6">
                <h2 class="text-h5 font-semibold text-ink">{{ __('student.progress_subjects_title') }}</h2>

                <div class="flex flex-col gap-4">
                    @foreach ($subjects as $subject)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2 text-ui text-ink">
                                    <x-icon :name="$subject['icon']" class="size-4 shrink-0 text-stone" />
                                    <span class="truncate">{{ $subject['name'] }}</span>
                                </span>
                                <span class="num shrink-0 text-caption text-stone">
                                    {{ $subject['completed'] }} / {{ $subject['total'] }}
                                </span>
                            </div>
                            <x-ui.progress-bar :percent="$subject['percent']" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

</x-layouts.student>
