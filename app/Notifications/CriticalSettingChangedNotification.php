<?php

namespace App\Notifications;

/**
 * CriticalSettingChangedNotification.
 *
 * ⚠️ Records WHO changed WHICH tab. Settings drive the public site and had
 * no audit trail at all before this.
 */
class CriticalSettingChangedNotification extends AdminNotification
{
    public function __construct(public string $tabLabel, public string $actorName) {}

    public function title(): string
    {
        return 'تغيير في الإعدادات';
    }

    public function message(): string
    {
        return $this->actorName.' عدّل تبويب «'.$this->tabLabel.'».';
    }

    public function icon(): string
    {
        return 'settings';
    }

    public function url(): string
    {
        return route('admin.settings.index');
    }

    public function type(): string
    {
        return 'settings';
    }
}
