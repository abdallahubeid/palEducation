<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\News;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\PricingPlan;
use App\Models\Slider;
use App\Models\Step;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * سجلّ الكيانات التي تظهر في سلّة المحذوفات المركزية.
 *
 * 🔴 مصدر واحد: الفلاتر في الواجهة، والتحقّق من رمز العنصر، وحلقة الجمع
 * كلها تقرأ من هنا. إضافة كيان جديد للسلّة = صفّ واحد في هذه المصفوفة،
 * لا تعديل في الكنترولر ولا في العرض.
 *
 * ⚠️ الكيان يدخل هنا فقط إن كان يستعمل SoftDeletes فعلاً؛ وإلّا رمى
 * onlyTrashed استثناءً وقت الطلب لا وقت البناء.
 *
 * 🔴 AND only if a real permission already guards deleting it. A trash tab
 * for an entity nothing can delete through the UI would advertise an
 * action that does not exist — the same reason PermissionCatalog refuses
 * permissions no route checks.
 *
 * Optional per-type hooks:
 *   restore     — runs instead of $record->restore()
 *   forceDelete — runs instead of $record->forceDelete()
 * Both exist because some rows are only half the record (a user account is
 * paired with a domain row) and restoring one without the other produces a
 * broken account rather than a restored one.
 */
final class TrashableResources
{
    /**
     * @return array<string, array{label: string, model: class-string<Model>, title: callable, subtitle: callable|null, permission: string, restore?: callable, forceDelete?: callable}>
     */
    public static function all(): array
    {
        return [
            'sliders' => [
                'label' => 'الهيرو',
                'model' => Slider::class,
                'title' => fn (Slider $slider): string => $slider->title,
                'subtitle' => fn (Slider $slider): ?string => $slider->badge_text,
                'permission' => 'delete-sliders',
            ],

            'branches' => [
                'label' => 'الفروع',
                'model' => Branch::class,
                'title' => fn (Branch $branch): string => $branch->name,
                'subtitle' => fn (Branch $branch): ?string => $branch->slug,
                'permission' => 'delete-branches',
            ],

            'users' => [
                'label' => 'المستخدمون',
                'model' => User::class,
                /*
                 * 🔴 NOT displayName(). That reads $user->userable, and the
                 * domain row was soft deleted alongside the account — so the
                 * default relation excludes it and the helper returns an
                 * empty string. Every trashed user would list with a blank
                 * title: nothing throws, the row is simply unidentifiable.
                 */
                'title' => fn (User $user): string => $user->userable()->withTrashed()->first()?->full_name
                    ?: $user->email,
                'subtitle' => fn (User $user): ?string => $user->email,
                'permission' => 'delete-users',

                /*
                 * 🔴 Identity is two rows: the account and its domain record
                 * (student / teacher / admin), soft deleted together inside
                 * one transaction. Restoring only the account produces a
                 * login that lands on nothing, and force deleting only the
                 * account orphans the domain row behind it.
                 */
                'restore' => function (User $user): bool {
                    return DB::transaction(function () use ($user): bool {
                        $user->userable()->withTrashed()->first()?->restore();

                        return (bool) $user->restore();
                    });
                },

                'forceDelete' => function (User $user): bool {
                    return DB::transaction(function () use ($user): bool {
                        $userable = $user->userable()->withTrashed()->first();

                        // Account first: it holds the foreign key, and
                        // dropping the domain row underneath it would break
                        // referential integrity mid-transaction.
                        $done = (bool) $user->forceDelete();
                        $userable?->forceDelete();

                        return $done;
                    });
                },
            ],

            'features' => [
                'label' => 'المميزات',
                'model' => Feature::class,
                'title' => fn (Feature $feature): string => $feature->title,
                'subtitle' => fn (Feature $feature): ?string => $feature->description,
                'permission' => 'delete-features',
            ],

            'pricings' => [
                'label' => 'الأسعار',
                'model' => PricingPlan::class,
                'title' => fn (PricingPlan $plan): string => $plan->name,
                'subtitle' => fn (PricingPlan $plan): ?string => $plan->badge,
                'permission' => 'delete-pricings',
            ],

            'messages' => [
                'label' => 'رسائل التواصل',
                'model' => ContactMessage::class,
                'title' => fn (ContactMessage $m): string => $m->name.' — '.$m->email,
                // The message, trimmed: a full paragraph would break the row.
                'subtitle' => fn (ContactMessage $m): ?string => Str::limit($m->message, 80),
                'permission' => 'delete-messages',
            ],

            'newsletter' => [
                'label' => 'مشتركو النشرة',
                'model' => NewsletterSubscriber::class,
                'title' => fn (NewsletterSubscriber $s): string => $s->email,
                // The status at the time of deletion, so restoring an
                // unsubscribed row is not mistaken for restoring a recipient.
                'subtitle' => fn (NewsletterSubscriber $s): ?string => $s->status->label(),
                'permission' => 'delete-newsletter',
            ],

            'campaigns' => [
                'label' => 'حملات النشرة',
                'model' => NewsletterCampaign::class,
                'title' => fn (NewsletterCampaign $c): string => $c->subject,
                // The size of the send, so restoring is not a guess about
                // which of several similarly titled campaigns this was.
                'subtitle' => fn (NewsletterCampaign $c): ?string => $c->recipients_count.' مستلم · '.$c->status->label(),
                'permission' => 'delete-campaigns',
            ],

            'subjects' => [
                'label' => 'المواد الدراسية',
                'model' => Subject::class,
                'title' => fn (Subject $s): string => $s->name,
                'subtitle' => fn (Subject $s): ?string => $s->branch?->name,
                'permission' => 'delete-subjects',
            ],

            'news' => [
                'label' => 'الأخبار',
                'model' => News::class,
                'title' => fn (News $item): string => $item->title,
                // The by-line and date, so restoring is not a guess between
                // two similarly titled notices.
                'subtitle' => fn (News $item): ?string => $item->authorName()
                    .' · '.($item->published_at?->format('Y-m-d') ?? '—'),
                'permission' => 'delete-news',
            ],

            'faqs' => [
                'label' => 'الأسئلة الشائعة',
                'model' => Faq::class,
                'title' => fn (Faq $faq): string => $faq->question,
                // The answer, trimmed: a full paragraph would break the row.
                'subtitle' => fn (Faq $faq): ?string => Str::limit($faq->answer, 80),
                'permission' => 'delete-faqs',
            ],

            'testimonials' => [
                'label' => 'آراء الطلبة',
                'model' => Testimonial::class,
                // The reviewer's name, live or snapshot — never a bare id.
                'title' => fn (Testimonial $t): string => $t->displayName() ?: 'رأي بلا اسم',
                'subtitle' => fn (Testimonial $t): ?string => $t->displayMeta(),
                'permission' => 'delete-testimonials',
            ],

            'teachers' => [
                'label' => 'المعلّمون',
                'model' => Teacher::class,
                'title' => fn (Teacher $teacher): string => $teacher->full_name,
                'subtitle' => fn (Teacher $teacher): ?string => $teacher->title,
                'permission' => 'delete-teachers',

                /*
                 * ⚠️ A teacher may or may not have a login account. Deleting
                 * one through the Users screen soft deletes BOTH rows, so an
                 * account-backed teacher is listed twice — once here and once
                 * under المستخدمون. Redundant, but each entry restores the
                 * whole pair, so neither can leave the other behind.
                 *
                 * A teacher seeded for the public roster has no account at
                 * all, and appears only here.
                 */
                'restore' => function (Teacher $teacher): bool {
                    return DB::transaction(function () use ($teacher): bool {
                        $teacher->user()->withTrashed()->first()?->restore();

                        return (bool) $teacher->restore();
                    });
                },

                'forceDelete' => function (Teacher $teacher): bool {
                    return DB::transaction(function () use ($teacher): bool {
                        // Account first: it holds the foreign key back to the
                        // teacher, so dropping the teacher underneath it
                        // would break referential integrity mid-transaction.
                        $teacher->user()->withTrashed()->first()?->forceDelete();

                        return (bool) $teacher->forceDelete();
                    });
                },
            ],

            'steps' => [
                'label' => 'خطوات آلية العمل',
                'model' => Step::class,
                'title' => fn (Step $step): string => $step->title,
                'subtitle' => fn (Step $step): ?string => $step->sub_title,
                // Steps are managed from the settings panel, so the
                // permission that creates and deletes them guards them here.
                'permission' => 'manage-settings',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function has(?string $type): bool
    {
        return $type !== null && $type !== '' && array_key_exists($type, self::all());
    }

    /** يُرجع النوع إن كان معروفاً، وnull لأي شيء آخر — لا يرمي. */
    public static function sanitize(?string $type): ?string
    {
        return self::has($type) ? $type : null;
    }

    /** @return class-string<Model> */
    public static function modelFor(string $type): string
    {
        return self::all()[$type]['model'];
    }

    public static function labelFor(string $type): string
    {
        return self::all()[$type]['label'] ?? $type;
    }

    public static function permissionFor(string $type): string
    {
        return self::all()[$type]['permission'];
    }

    /**
     * The types this user may act on.
     *
     * 🔴 One permission no longer guards the whole trash. Restoring a branch
     * required "delete sliders" before this, which is not a statement anyone
     * would defend out loud — it was an artefact of the trash being built
     * when sliders were the only trashable thing.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function allowedFor(?Authorizable $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_filter(
            self::all(),
            fn (array $config): bool => $user->can($config['permission']),
        );
    }

    /** @return array<int, string> */
    public static function allowedKeysFor(?Authorizable $user): array
    {
        return array_keys(self::allowedFor($user));
    }

    public static function allows(?Authorizable $user, ?string $type): bool
    {
        return self::has($type) && $user !== null && $user->can(self::permissionFor($type));
    }
}
