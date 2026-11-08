<?php

namespace App\Notifications;

/**
 * إعلان عامّ لكلّ الطلبة — صيانة، ميزة جديدة، تغيير سياسة.
 *
 * 🔴 يُبَثّ عبر StudentNotifier::broadcast(). لا استهداف لطالب بعينه هنا عمداً:
 * ما يخصّ طالباً دون غيره حدثٌ محدَّد لا هذا.
 */
class StudentAnnouncementNotification extends StudentNotification
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
        return $this->link ?? route('student.dashboard');
    }

    public function type(): string
    {
        return 'info';
    }
}
