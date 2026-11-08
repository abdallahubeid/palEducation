<x-layouts.teacher :title="__('teacher.students_title')" :teacher-name="auth()->user()->displayName()">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('teacher.students_title') }}</h1>
            <p class="mt-1 text-ui text-steel">{{ __('teacher.students_subtitle') }}</p>
        </div>

        @if ($hasSubjects)
            <span class="rounded-full bg-surface px-3 py-1.5 text-caption text-steel">
                {{ __('teacher.students_count', ['count' => $totalStudents]) }}
            </span>
        @endif
    </div>

    @if (! $hasSubjects)
        <div class="tile mt-6">
            <x-ui.empty-state icon="book"
                              :title="__('teacher.students_no_subjects_title')"
                              :body="__('teacher.students_no_subjects_body')" />
        </div>
    @else
        {{-- بحث + فلتر فرع في نموذج GET واحد. --}}
        <form method="GET" action="{{ route('teacher.students') }}"
              class="mt-6 flex flex-wrap items-center gap-3">

            <div class="min-w-0 flex-1 sm:max-w-xs">
                <label for="students-search" class="sr-only">{{ __('teacher.students_search') }}</label>
                <input id="students-search" type="search" name="search" value="{{ $filters['search'] }}"
                       placeholder="{{ __('teacher.students_search') }}"
                       class="h-10 w-full rounded-full border border-hairline bg-canvas px-4 text-ui text-ink
                              transition placeholder:text-muted focus-visible:border-accent focus-visible:outline-none">
            </div>

            @if ($branches->count() > 1)
                <select name="branch_id"
                        class="h-10 rounded-full border border-hairline bg-canvas px-4 text-caption text-ink
                               focus-visible:border-accent focus-visible:outline-none">
                    <option value="">{{ __('teacher.students_all_branches') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <x-ui.button type="submit" variant="secondary" size="sm">تصفية</x-ui.button>

            @if (array_filter($filters, fn ($v) => filled($v)))
                <x-ui.button variant="ghost" size="sm" :href="route('teacher.students')">مسح</x-ui.button>
            @endif
        </form>

        @if ($students->isEmpty())
            <div class="tile mt-6">
                <x-ui.empty-state icon="users"
                                  :title="array_filter($filters, fn ($v) => filled($v))
                                      ? __('teacher.students_no_results_title')
                                      : __('teacher.students_empty_title')"
                                  :body="array_filter($filters, fn ($v) => filled($v))
                                      ? __('teacher.students_no_results_body')
                                      : __('teacher.students_empty_body')" />
            </div>
        @else
            <div class="tile mt-6 overflow-x-auto p-0">
                <table class="min-w-full divide-y divide-hairline text-ui">
                    <thead class="bg-surface-soft">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-start text-caption font-semibold text-stone">{{ __('teacher.students_col_name') }}</th>
                            <th scope="col" class="w-36 px-4 py-3 text-start text-caption font-semibold text-stone">{{ __('teacher.students_col_branch') }}</th>
                            <th scope="col" class="hidden px-4 py-3 text-start text-caption font-semibold text-stone md:table-cell">{{ __('teacher.students_col_school') }}</th>
                            <th scope="col" class="hidden w-32 px-4 py-3 text-start text-caption font-semibold text-stone lg:table-cell">{{ __('teacher.students_col_governorate') }}</th>
                            <th scope="col" class="hidden w-32 px-4 py-3 text-start text-caption font-semibold text-stone sm:table-cell">{{ __('teacher.students_col_joined') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-hairline">
                        @foreach ($students as $student)
                            <tr class="transition hover:bg-surface/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$student->full_name" size="sm" />
                                        <p class="truncate font-semibold text-ink">{{ $student->full_name }}</p>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-surface px-2 py-1 text-micro text-stone">
                                        {{ $student->branch?->name ?? '—' }}
                                    </span>
                                </td>

                                <td class="hidden px-4 py-3 text-caption text-stone md:table-cell">
                                    {{ $student->school_name ?? '—' }}
                                </td>

                                <td class="hidden px-4 py-3 text-caption text-stone lg:table-cell">
                                    {{ $student->governorate ?? '—' }}
                                </td>

                                <td class="hidden px-4 py-3 sm:table-cell">
                                    <span class="num text-caption text-stone" dir="ltr">
                                        {{ $student->created_at->format('Y-m-d') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="mt-4">{{ $students->links() }}</div>
            @endif
        @endif
    @endif

</x-layouts.teacher>
