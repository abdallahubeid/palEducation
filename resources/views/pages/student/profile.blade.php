@php
    // بيانات عرض — تُستبدل بمصادقة حقيقية لاحقاً. الحفظ هنا واجهة فقط،
    // لا يوجد اتصال خادم بعد (لا Livewire، لا نموذج مجال).
    $student = [
        'name'   => 'محمد أبو عودة',
        'email'  => 'mohammad.abuouda@example.com',
        'phone'  => '0599123456',
        'branch' => 'الفرع العلمي',
    ];

    $notificationPrefs = [
        ['name' => 'new_lecture',   'label' => __('student.profile_notif_new_lecture'),   'checked' => true],
        ['name' => 'quiz_result',   'label' => __('student.profile_notif_quiz_result'),   'checked' => true],
        ['name' => 'subscription',  'label' => __('student.profile_notif_subscription'),  'checked' => true],
        ['name' => 'news',          'label' => __('student.profile_notif_news'),          'checked' => false],
    ];
@endphp

<x-layouts.student
    :title="__('student.profile_title')"
    :student-name="$student['name']"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <h1 class="text-h2 font-bold text-ink">{{ __('student.profile_title') }}</h1>

        <div class="tile flex flex-col gap-5 p-6">
            <div class="flex items-center gap-4">
                <x-ui.avatar :name="$student['name']" size="lg" />
                <div>
                    <p class="text-ui font-semibold text-ink">{{ $student['name'] }}</p>
                    <p class="text-caption text-stone">{{ $student['branch'] }}</p>
                </div>
            </div>

            <h2 class="border-t border-hairline-soft pt-5 text-h5 font-semibold text-ink">{{ __('student.profile_account_section') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('student.profile_name_label')" name="name" :value="$student['name']" />
                <x-ui.input :label="__('student.profile_email_label')" name="email" type="email" :value="$student['email']" />
                <x-ui.input :label="__('student.profile_phone_label')" name="phone" type="tel" :value="$student['phone']" />

                <div class="flex flex-col gap-1.5">
                    <label class="text-ui text-steel">{{ __('student.profile_branch_label') }}</label>
                    <div class="flex h-12 items-center justify-between rounded-md border border-hairline bg-surface px-4 text-ui text-steel lg:h-11">
                        <span>{{ $student['branch'] }}</span>
                        <a href="#" class="text-caption font-semibold text-accent-deep hover:underline">
                            {{ __('student.profile_branch_change_request') }}
                        </a>
                    </div>
                </div>
            </div>

            <x-ui.button variant="primary" size="md" data-profile-save class="w-fit">
                {{ __('student.profile_save_changes') }}
            </x-ui.button>
        </div>

        <div class="tile flex flex-col gap-5 p-6">
            <h2 class="text-h5 font-semibold text-ink">{{ __('student.profile_password_section') }}</h2>

            <div class="grid gap-4">
                <x-ui.input :label="__('student.profile_current_password_label')" name="current_password" type="password" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('student.profile_new_password_label')" name="new_password" type="password" />
                    <x-ui.input :label="__('student.profile_confirm_password_label')" name="confirm_password" type="password" />
                </div>
            </div>

            <x-ui.button variant="secondary" size="md" class="w-fit">
                {{ __('student.profile_update_password') }}
            </x-ui.button>
        </div>

        <div class="tile flex flex-col gap-1 p-6">
            <h2 class="mb-2 text-h5 font-semibold text-ink">{{ __('student.profile_notifications_section') }}</h2>

            @foreach ($notificationPrefs as $pref)
                <div class="border-b border-hairline-soft last:border-0">
                    <x-ui.toggle :name="$pref['name']" :label="$pref['label']" :checked="$pref['checked']" />
                </div>
            @endforeach
        </div>

        <div class="tile flex flex-col gap-4 p-6">
            <h2 class="text-h5 font-semibold text-ink">{{ __('student.profile_goals_section') }}</h2>
            <x-ui.input
                :label="__('student.profile_goals_minutes_label')"
                name="daily_goal_minutes"
                type="number"
                value="45"
                class="max-w-40" />
        </div>

        <p data-profile-saved-toast hidden class="text-center text-ui font-medium text-accent-deep">
            {{ __('student.profile_saved_confirmation') }}
        </p>
    </div>

</x-layouts.student>
