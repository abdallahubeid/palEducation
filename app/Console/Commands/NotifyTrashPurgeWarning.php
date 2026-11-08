<?php

namespace App\Console\Commands;

use App\Notifications\TrashAutoPurgeWarningNotification;
use App\Support\AdminNotifier;
use App\Support\TrashableResources;
use Illuminate\Console\Command;

/**
 * Warns admins about items sitting in the trash past the retention window.
 *
 * 🔴 It WARNS and deletes nothing. The 30-day auto-purge is still an open
 * decision in this project, and a scheduled command that quietly destroyed
 * content while that decision is unmade would be the worst possible way to
 * settle it. Permanent deletion stays a human action on the trash screen.
 */
class NotifyTrashPurgeWarning extends Command
{
    protected $signature = 'notifications:trash-warning {--days=30 : عمر العنصر بالأيام}';

    protected $description = 'ينبّه الأدمن إلى العناصر القديمة في سلّة المحذوفات';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        /*
         * ⚠️ Reads the registry rather than a hard-coded list, so a trashable
         * type added later is covered without touching this command — the
         * same reason the dashboard counts the trash that way.
         */
        $count = collect(TrashableResources::all())->sum(
            fn (array $resource): int => $resource['model']::onlyTrashed()
                ->where('deleted_at', '<=', $cutoff)
                ->count()
        );

        if ($count === 0) {
            $this->info('لا عناصر قديمة في السلّة.');

            return self::SUCCESS;
        }

        AdminNotifier::send(new TrashAutoPurgeWarningNotification($count, $days));

        $this->info("أُرسل تنبيه بـ{$count} عنصر.");

        return self::SUCCESS;
    }
}
