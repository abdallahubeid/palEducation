<?php

namespace App\Notifications;

use App\Enums\CampaignStatus;
use App\Models\NewsletterCampaign;

/**
 * NewsletterCampaignFinishedNotification.
 *
 * ⚠️ Fires on BOTH outcomes. A campaign that failed silently is the worse
 * case of the two — the title and colour change, the notification does not
 * disappear.
 */
class NewsletterCampaignFinishedNotification extends AdminNotification
{
    public function __construct(public NewsletterCampaign $campaign) {}

    public function title(): string
    {
        return $this->campaign->status === CampaignStatus::Failed
            ? 'فشل إرسال حملة'
            : 'اكتمل إرسال حملة';
    }

    public function message(): string
    {
        return '«'.$this->campaign->subject.'» — '.$this->campaign->recipients_count.' مستلم';
    }

    public function icon(): string
    {
        return 'mail';
    }

    public function url(): string
    {
        return route('admin.newsletter.campaigns.index');
    }

    public function type(): string
    {
        return $this->campaign->status === CampaignStatus::Failed ? 'error' : 'success';
    }
}
