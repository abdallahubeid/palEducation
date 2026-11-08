@php
    $toneFor = [
        'success' => ['icon' => 'check-circle', 'chip' => 'bg-accent/14 text-accent-deep'],
        'warning' => ['icon' => 'alert', 'chip' => 'bg-warn/14 text-warn-deep'],
        'info' => ['icon' => 'sparkle', 'chip' => 'bg-tag/12 text-tag'],
        'error' => ['icon' => 'alert', 'chip' => 'bg-error/12 text-error-deep'],
    ];
@endphp

<x-layouts.student
    :title="__('student.nav_dashboard')"
    :student-name="$studentName"
    :student-avatar="auth()->user()->avatar_url">

    <div class="mx-auto flex max-w-6xl flex-col gap-6">

        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('student.welcome', ['name' => $studentName]) }}</h1>
            <p class="mt-1 text-ui text-steel">
                فرعك: <span class="font-semibold text-ink">{{ $branchName ?? '—' }}</span>
            </p>
        </div>

        {{-- ═══ الأرقام ═══ --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-domain.stat-card icon="book" :value="(string) $subjectsCount"
                                :label="__('student.stat_branch_subjects')" tone="tag" />
            <x-domain.stat-card icon="check-circle"
                                :value="$lecturesCompleted . ' / ' . $lecturesTotal"
                                :label="__('student.stat_completed_lectures')" tone="accent" />
            <x-domain.stat-card icon="chart" :value="$completionRate . '%'"
                                label="نسبة الإنجاز" tone="amber" />
            <x-domain.stat-card icon="folder" :value="(string) $downloadsCount"
                                label="تنزيلات المكتبة" tone="warn" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- ═══ آخر ملفات المكتبة ═══ --}}
            <div class="tile flex flex-col gap-4 p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-h5 font-semibold text-ink">آخر ملفات فرعك</h2>
                    <a href="{{ route('student.library') }}"
                       class="text-caption font-semibold text-accent-deep transition hover:underline">
                        {{ __('student.subjects_view_all') }}
                    </a>
                </div>

                @forelse ($recentFiles as $file)
                    <a href="{{ route('student.library', ['subject' => $file->subject_id]) }}"
                       class="flex items-center gap-3 rounded-lg py-1.5 transition hover:bg-surface/60">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                            <x-icon name="folder" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-ui text-ink">{{ $file->title }}</span>
                            <span class="block truncate text-micro text-stone">{{ $file->subject?->name }}</span>
                        </span>
                        <span class="num shrink-0 text-micro text-muted" dir="ltr">
                            {{ optional($file->published_at)->format('Y-m-d') }}
                        </span>
                    </a>
                @empty
                    <p class="py-6 text-center text-caption text-stone">لا ملفات في مكتبة فرعك بعد.</p>
                @endforelse
            </div>

            {{-- ═══ إعلانات النظام ═══ --}}
            <div class="tile flex flex-col gap-4 p-6">
                <h2 class="text-h5 font-semibold text-ink">إعلانات المنصّة</h2>

                @forelse ($announcements as $note)
                    @php
                        $data = $note->data;
                        $tone = $toneFor[$data['type'] ?? 'info'] ?? $toneFor['info'];
                    @endphp
                    <a href="{{ $data['url'] ?? route('student.dashboard') }}"
                       @class([
                           'flex items-start gap-3 rounded-lg p-2 transition hover:bg-surface/60',
                           'bg-accent/5' => $note->read_at === null,
                       ])>
                        <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg {{ $tone['chip'] }}">
                            <x-icon :name="$tone['icon']" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-ui font-semibold text-ink">{{ $data['title'] ?? '' }}</span>
                            <span class="mt-0.5 block text-micro leading-relaxed text-stone">{{ $data['message'] ?? '' }}</span>
                            <span class="mt-1 block text-micro text-muted">{{ $note->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                @empty
                    <p class="py-6 text-center text-caption text-stone">لا إعلانات حالياً — أنت على اطّلاع بكل شيء.</p>
                @endforelse
            </div>
        </div>

        <div class="flex justify-center">
            <x-ui.button variant="secondary" :href="route('student.progress')">
                <x-icon name="chart" class="size-4" />
                عرض تقدّمي الكامل
            </x-ui.button>
        </div>
    </div>

</x-layouts.student>
