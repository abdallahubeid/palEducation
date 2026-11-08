<?php

namespace App\Console\Commands;

use App\Notifications\StudentAnnouncementNotification;
use App\Support\StudentNotifier;
use Illuminate\Console\Command;

/**
 * يرسل إعلاناً عامّاً لكلّ الطلبة الفاعلين.
 *
 * 🔴 أمر لا مهمّة مجدولة: الإعلانات نادرة ومقصودة، يدفعها إنسان.
 *
 *   php artisan students:announce "امتحان تجريبي" "امتحان الرياضيات التجريبي يوم الخميس."
 */
class AnnounceToStudents extends Command
{
    protected $signature = 'students:announce
        {heading : عنوان قصير للإعلان}
        {body : نصّ الإعلان}
        {--url= : رابط النقر (افتراضياً لوحة الطالب)}';

    protected $description = 'يرسل إعلاناً عامّاً لكلّ الطلبة الفاعلين';

    public function handle(): int
    {
        $recipients = StudentNotifier::recipients();

        if ($recipients->isEmpty()) {
            $this->warn('لا طلبة فاعلين — لم يُرسَل شيء.');

            return self::SUCCESS;
        }

        StudentNotifier::broadcast(new StudentAnnouncementNotification(
            heading: $this->argument('heading'),
            body: $this->argument('body'),
            link: $this->option('url') ?: null,
        ));

        $this->info("أُرسل الإعلان إلى {$recipients->count()} طالب.");

        return self::SUCCESS;
    }
}
