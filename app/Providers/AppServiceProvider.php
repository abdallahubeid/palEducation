<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         | خريطة الإعدادات — قراءة واحدة لكل طلب.
         |
         | ⚠️ scoped لا singleton: singleton يعيش ما دامت العملية حيّة،
         | فيبقى قديماً بين مهام الطابور. scoped تُفرّغه Laravel بين الطلبات
         | وبين المهام، وكل اختبار يحصل على حاوية جديدة.
         */
        $this->app->scoped(
            Setting::MAP_BINDING,
            fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );

        //
    }

    public function boot(): void
    {
        $this->configurePasswordRules();
        $this->configureVerificationEmail();
        $this->invalidateSessionsOnDatabaseRefresh();
    }

    /**
     * Drop every session and cached value whenever the database is rebuilt.
     *
     * 🔴 The hazard is NOT just "still logged in after a reseed". Reseeding
     * reassigns primary keys, so a surviving session cookie authenticates
     * its holder as whichever account now happens to own that id — a
     * developer who was an admin can come back as a student, or worse, the
     * reverse. That is a silent identity swap, not an inconvenience.
     *
     * ⚠️ With SESSION_DRIVER=database this mostly happens for free, because
     * `migrate:fresh` drops the sessions table with everything else. This
     * listener exists for the case that is NOT free: a developer switching
     * to the file or redis driver, where sessions outlive the database and
     * nothing warns them.
     *
     * DatabaseRefreshed is fired by `migrate:fresh` specifically — not by
     * `migrate`, and not by `db:seed`. Putting this in DatabaseSeeder
     * instead would log every user out on a routine production reseed.
     */
    private function invalidateSessionsOnDatabaseRefresh(): void
    {
        Event::listen(DatabaseRefreshed::class, function (): void {
            $this->flushSessions();

            // Rebuilt data behind a stale cache is its own class of ghost:
            // the permission cache in particular outlives the roles it
            // describes and grants access to rows that no longer exist.
            Artisan::call('cache:clear');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * Clears the session store for whichever driver is configured.
     *
     * ⚠️ Only the drivers this project can actually be running. A silent
     * no-op on an unknown driver is the honest outcome: guessing at how to
     * flush something we do not manage would be worse than leaving it.
     */
    private function flushSessions(): void
    {
        $driver = config('session.driver');

        if ($driver === 'database') {
            $table = config('session.table', 'sessions');

            // `migrate:fresh` has usually just dropped and recreated this,
            // so the truncate is belt-and-braces rather than the main event.
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }

            return;
        }

        if ($driver === 'file') {
            // 🔴 The case this whole listener exists for: files sit outside
            // the database and survive a rebuild untouched.
            foreach (File::glob(config('session.files').'/*') as $path) {
                // Laravel keeps a .gitignore in that directory.
                if (File::isFile($path) && ! str_ends_with($path, '.gitignore')) {
                    File::delete($path);
                }
            }
        }
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
