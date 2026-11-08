<?php

namespace App\Notifications;

use App\Models\ContactMessage;

/**
 * NewContactMessageNotification.
 *
 * ⚠️ Carries the model, not just its id: the payload is written once at
 * dispatch and must survive the message later being read, replied to or
 * deleted.
 */
class NewContactMessageNotification extends AdminNotification
{
    public function __construct(public ContactMessage $message) {}

    public function title(): string
    {
        return 'رسالة تواصل جديدة';
    }

    public function message(): string
    {
        return $this->message->name.' — '.$this->message->subject_type->label();
    }

    public function icon(): string
    {
        return 'mail';
    }

    public function url(): string
    {
        return route('admin.messages.show', $this->message);
    }

    public function type(): string
    {
        return 'message';
    }
}
