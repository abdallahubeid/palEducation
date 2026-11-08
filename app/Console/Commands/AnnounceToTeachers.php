<?php

namespace App\Console\Commands;

use App\Notifications\SystemAnnouncementNotification;
use App\Support\TeacherNotifier;
use Illuminate\Console\Command;

/**
 * Sends a platform-wide announcement to every active teacher.
 *
 * 🔴 A command, not a scheduled task. System announcements are rare and
 * deliberate — maintenance windows, a new feature, a policy change — so they
 * are pushed by a human running this, not by a timer. An admin UI for it is a
 * later addition; the delivery path is the same either way.
 *
 *   php artisan teachers:announce "صيانة الليلة" "المنصّة غير متاحة 2–3 صباحاً."
 */
class AnnounceToTeachers extends Command
{
    protected $signature = 'teachers:announce
        {heading : عنوان قصير للإعلان}
        {body : نصّ الإعلان}
        {--url= : رابط النقر (افتراضياً لوحة المعلّم)}';

    protected $description = 'يرسل إعلاناً عاماً لكل المعلّمين الفاعلين';

    public function handle(): int
    {
        $recipients = TeacherNotifier::recipients();

        if ($recipients->isEmpty()) {
            $this->warn('لا معلّمين فاعلين — لم يُرسَل شيء.');

            return self::SUCCESS;
        }

        TeacherNotifier::broadcast(new SystemAnnouncementNotification(
            heading: $this->argument('heading'),
            body: $this->argument('body'),
            link: $this->option('url') ?: null,
        ));

        $this->info("أُرسل الإعلان إلى {$recipients->count()} معلّم.");

        return self::SUCCESS;
    }
}
