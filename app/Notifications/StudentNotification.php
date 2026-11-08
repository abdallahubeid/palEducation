<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * الظرف الذي تتشاركه كلّ إشعارات الطالب — مطابق لـAdminNotification
 * وTeacherNotification لأن نفس العارض يرسمها جميعاً (title/message/icon/url/type
 * عبر قناة database).
 */
abstract class StudentNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * لقطة لا مرجع: العنوان يبقى كما كان وقت الحدث.
     *
     * @return array{title: string, message: string, icon: string, url: string, type: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'icon' => $this->icon(),
            'url' => $this->url(),
            'type' => $this->type(),
        ];
    }

    abstract public function title(): string;

    abstract public function message(): string;

    abstract public function icon(): string;

    abstract public function url(): string;

    abstract public function type(): string;
}
