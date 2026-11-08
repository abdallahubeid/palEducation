<?php

namespace App\Notifications;

use App\Models\User;

/**
 * NewAdminRegisteredNotification.
 *
 * 🔴 Staff accounts only. A student registration is routine and would bury
 * everything else in the bell within a day of launch.
 */
class NewAdminRegisteredNotification extends AdminNotification
{
    public function __construct(public User $account, public string $roleLabel) {}

    public function title(): string
    {
        return 'حساب '.$this->roleLabel.' جديد';
    }

    public function message(): string
    {
        return $this->account->displayName().' — '.$this->account->email;
    }

    public function icon(): string
    {
        return 'user';
    }

    public function url(): string
    {
        return route('admin.users.index');
    }

    public function type(): string
    {
        return 'account';
    }
}
