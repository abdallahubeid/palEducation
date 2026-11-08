@php
    /*
     * 🔴 استرجاع فقط. لا زرّ حذف نهائي في هذه الصفحة إطلاقاً — صلاحية إدارة.
     */
    $tabs = [
        'all' => __('teacher.trash_tab_all'),
        'lectures' => __('teacher.trash_tab_lectures'),
        'files' => __('teacher.trash_tab_files'),
    ];
@endphp

<x-layouts.teacher :title="__('teacher.trash_title')" :teacher-name="auth()->user()->displayName()">

    <div>
        <h1 class="text-h2 font-bold text-ink">{{ __('teacher.trash_title') }}</h1>
        <p class="mt-1 text-ui text-steel">{{ __('teacher.trash_subtitle') }}</p>
    </div>

    {{-- م-9: المعلّم يسترجع فقط. --}}
    <x-ui.alert variant="accent" icon="shield" class="mt-6">
        {{ __('teacher.trash_note') }}
    </x-ui.alert>

    {{-- تبويبات بعدّادات — روابط GET، لا فلتر JS. --}}
    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('teacher.trash', array_filter(['type' => $key === 'all' ? null : $key])) }}"
               @class([
                   'inline-flex min-h-9 items-center gap-2 rounded-full px-4 text-caption font-semibold transition',
                   'bg-accent text-on-primary' => $filter === $key,
                   'bg-surface text-steel hover:text-ink' => $filter !== $key,
               ])>
                {{ $label }}
                <span class="num rounded-full bg-black/10 px-1.5 text-micro">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    @if ($items->isEmpty())
        <div class="tile mt-6">
            <x-ui.empty-state icon="trash"
                              :title="__('teacher.trash_empty_title')"
                              :body="__('teacher.trash_empty_body')" />
        </div>
    @else
        <div class="tile mt-6 overflow-x-auto p-0">
            <table class="min-w-full divide-y divide-hairline text-ui">
                <thead class="bg-surface-soft">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start text-caption font-semibold text-stone">{{ __('teacher.trash_col_item') }}</th>
                        <th scope="col" class="w-32 px-4 py-3 text-start text-caption font-semibold text-stone">{{ __('teacher.trash_col_type') }}</th>
                        <th scope="col" class="w-40 px-4 py-3 text-start text-caption font-semibold text-stone">{{ __('teacher.trash_col_deleted_at') }}</th>
                        <th scope="col" class="px-4 py-3 text-end text-caption font-semibold text-stone">{{ __('teacher.trash_col_actions') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-hairline">
                    @foreach ($items as $item)
                        <tr class="transition hover:bg-surface/60">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                                        <x-icon :name="$item['icon']" class="size-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-ink">{{ $item['title'] }}</p>
                                        @if ($item['subject'])
                                            <p class="mt-0.5 truncate text-micro text-stone">{{ $item['subject'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span class="rounded-md bg-surface px-2 py-1 text-micro text-stone">{{ $item['type_label'] }}</span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-caption text-stone">{{ $item['deleted_at']?->diffForHumans() }}</span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end">
                                    {{-- نموذج PATCH بـCSRF لا رابط: الاسترجاع يغيّر حالة الخادم،
                                         وrestore عبر GET يُنفَّذ بجالب مسبق أو زاحف. --}}
                                    <form method="POST"
                                          action="{{ route('teacher.trash.restore', ['type' => $item['token'], 'id' => $item['id']]) }}"
                                          data-confirm-title="{{ __('teacher.trash_restore_confirm_title', ['title' => $item['title']]) }}"
                                          data-confirm="{{ __('teacher.trash_restore_confirm_body') }}"
                                          data-confirm-action="{{ __('teacher.trash_restore_confirm_action') }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="type" value="{{ $filter === 'all' ? '' : $filter }}">
                                        <x-ui.button type="submit" variant="secondary" size="sm">
                                            <x-icon name="undo" class="size-4 rtl:-scale-x-100" />
                                            {{ __('teacher.trash_action_restore') }}
                                        </x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.teacher>
