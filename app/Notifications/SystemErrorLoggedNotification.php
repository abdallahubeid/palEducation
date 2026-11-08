<?php

namespace App\Notifications;

/**
 * SystemErrorLoggedNotification.
 *
 * 🔴 Deduplicated by the caller, not here. An error that repeats a thousand
 * times a minute would otherwise fill the table faster than anyone could
 * read it — see AdminNotifier::reportError().
 */
class SystemErrorLoggedNotification extends AdminNotification
{
    public function __construct(public string $summary, public string $context) {}

    public function title(): string
    {
        return 'خطأ في النظام';
    }

    public function message(): string
    {
        return $this->summary;
    }

    public function icon(): string
    {
        return 'alert';
    }

    public function url(): string
    {
        return route('admin.dashboard');
    }

    public function type(): string
    {
        return 'error';
    }
}
