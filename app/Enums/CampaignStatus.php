<?php

namespace App\Enums;

/**
 * How far a campaign got.
 *
 * ⚠️ `processing` is the DEFAULT and the honest starting state. The send is a
 * queue of jobs, so at the moment the admin presses the button nothing has
 * actually been delivered — a record created as "completed" would be a claim
 * the system cannot yet make.
 */
enum CampaignStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'قيد الإرسال',
            self::Completed => 'مكتملة',
            self::Failed => 'فشلت',
        };
    }

    /** The admin badge tone for this status. */
    public function badge(): string
    {
        return match ($this) {
            self::Completed => 'active',
            self::Processing => 'draft',
            self::Failed => 'danger',
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
