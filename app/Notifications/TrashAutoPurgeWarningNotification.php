<?php

namespace App\Notifications;

/**
 * TrashAutoPurgeWarningNotification.
 *
 * ⚠️ A warning, never an action. Nothing is deleted by this notification;
 * it exists so a permanent purge is always a human decision.
 */
class TrashAutoPurgeWarningNotification extends AdminNotification
{
    public function __construct(public int $count, public int $days) {}

    public function title(): string
    {
        return 'عناصر قديمة في سلّة المحذوفات';
    }

    public function message(): string
    {
        return $this->count.' عنصر مضى على حذفه أكثر من '.$this->days.' يوماً.';
    }

    public function icon(): string
    {
        return 'trash';
    }

    public function url(): string
    {
        return route('admin.trash.index');
    }

    public function type(): string
    {
        return 'warning';
    }
}
