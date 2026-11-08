<?php

namespace App\Notifications;

use App\Models\Testimonial;

/**
 * TestimonialPendingNotification.
 *
 * 🔴 The moderation queue's only push. A testimonial arrives with
 * is_active = false and would otherwise sit unseen until somebody thought
 * to open the screen.
 */
class TestimonialPendingNotification extends AdminNotification
{
    public function __construct(public Testimonial $testimonial) {}

    public function title(): string
    {
        return 'رأي بانتظار الموافقة';
    }

    public function message(): string
    {
        return $this->testimonial->name.' أرسل رأياً جديداً.';
    }

    public function icon(): string
    {
        return 'sparkle';
    }

    public function url(): string
    {
        return route('admin.testimonials.index');
    }

    public function type(): string
    {
        return 'testimonial';
    }
}
