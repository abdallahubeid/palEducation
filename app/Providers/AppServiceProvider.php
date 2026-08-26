<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configurePasswordRules();
        $this->configureVerificationEmail();
    }

    /**
     * سياسة كلمة المرور — مكان واحد يحكم كل شاشات المصادقة.
     * تطابق نصّ auth.register_password_hint المعروض للطالب.
     */
    private function configurePasswordRules(): void
    {
        Password::defaults(fn () => Password::min(8)->numbers());
    }

    /**
     * رسالة التوثيق بالعربية.
     *
     * 🔴 الافتراضية إنجليزية ("Verify your email address") — رسالة
     * إنجليزية تصل طالب توجيهي فلسطيني تُقرأ رسالة تصيّد لا ترحيباً.
     * البريد أوّل ما يراه المستخدم بعد التسجيل، فلغته ليست تفصيلاً.
     */
    private function configureVerificationEmail(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject(__('mail.verify_subject'))
                ->greeting(__('mail.verify_greeting', ['name' => $notifiable->displayName()]))
                ->line(__('mail.verify_line1'))
                ->action(__('mail.verify_action'), $url)
                ->line(__('mail.verify_line2'))
                ->salutation(__('mail.salutation'));
        });
    }
}
