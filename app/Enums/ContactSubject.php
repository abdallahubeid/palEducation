<?php

namespace App\Enums;

/**
 * What a contact message is about.
 *
 * ⚠️ These four cases are the options the PUBLIC FORM already offers
 * (general · support · sales · signup), not the inquiry/technical/suggestion
 * set sketched in the brief. Storing values the form cannot produce would
 * make the admin filter list half-empty on day one; the form's own options
 * are the ones a visitor can actually pick.
 *
 * 🔴 The stored value is the case name, and the label is looked up for
 * display. Renaming a case is a data migration, not a copy edit.
 */
enum ContactSubject: string
{
    case General = 'general';
    case Support = 'support';
    case Sales = 'sales';
    case Signup = 'signup';

    /** The Arabic label, reusing the same keys the public form renders. */
    public function label(): string
    {
        return match ($this) {
            self::General => __('public.contact_topic_general'),
            self::Support => __('public.contact_topic_support'),
            self::Sales => __('public.contact_topic_sales'),
            self::Signup => __('public.contact_topic_signup'),
        };
    }

    /** @return array<string, string> value ⇒ label, for selects and filters. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
