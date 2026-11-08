<?php

namespace App\Enums;

/**
 * Whether a subscriber still wants the newsletter.
 *
 * 🔴 Unsubscribing is a STATUS, never a delete. The row has to survive so a
 * later campaign cannot mail someone who already opted out — a deleted row
 * would simply be re-added by the next footer signup and start mailing them
 * again. Deleting is a separate, admin-only act with its own trash entry.
 */
enum NewsletterStatus: string
{
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Unsubscribed => 'ملغى الاشتراك',
        };
    }

    /** The admin badge tone for this status. */
    public function badge(): string
    {
        return match ($this) {
            self::Active => 'active',
            self::Unsubscribed => 'draft',
        };
    }

    /** @return array<string, string> value ⇒ label, for filters. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
