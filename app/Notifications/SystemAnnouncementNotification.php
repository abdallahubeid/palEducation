<?php

namespace App\Notifications;

/**
 * A platform-wide announcement to every teacher — maintenance, a new feature,
 * a policy change.
 *
 * 🔴 Broadcast through TeacherNotifier::broadcast(). There is no per-teacher
 * targeting here on purpose: anything that concerns one teacher and not the
 * rest belongs to a specific event, not to this.
 */
class SystemAnnouncementNotification extends TeacherNotification
{
    public function __construct(
        public string $heading,
        public string $body,
        public ?string $link = null,
    ) {}

    public function title(): string
    {
        return $this->heading;
    }

    public function message(): string
    {
        return $this->body;
    }

    public function icon(): string
    {
        return 'sparkle';
    }

    public function url(): string
    {
        return $this->link ?? route('teacher.dashboard');
    }

    public function type(): string
    {
        return 'info';
    }
}
