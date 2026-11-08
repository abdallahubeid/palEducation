<?php

namespace App\Models;

use App\Enums\NewsletterStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

/**
 * An address on the newsletter list.
 *
 * 🔴 Deliberately NOT tied to `users`. Most people who subscribe from the
 * footer have no account, and someone who deletes their account has not
 * thereby asked to keep receiving mail — the list has to stand on its own.
 */
class NewsletterSubscriber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'status',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsletterStatus::class,
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === NewsletterStatus::Active;
    }

    /*
    |--------------------------------------------------------------------------
    | Subscribing
    |--------------------------------------------------------------------------
    */

    /**
     * Subscribes an address, or brings a lapsed one back.
     *
     * 🔴 Looks through soft deletes on purpose. `email` is unique and the
     * index does not ignore `deleted_at`, so a plain create() against a
     * trashed address throws a duplicate-key error — a 500 on the public
     * footer form for a returning visitor. Restoring the existing row is
     * both the correct behaviour and the one that cannot collide.
     */
    public static function subscribe(string $email): self
    {
        $subscriber = self::withTrashed()->firstOrNew(['email' => $email]);

        // ⚠️ Only a NEW or lapsed row is (re)dated. Re-submitting the form
        // while already active must not move subscribed_at forward and erase
        // how long someone has actually been on the list.
        if (! $subscriber->exists || ! $subscriber->isActive()) {
            $subscriber->status = NewsletterStatus::Active;
            $subscriber->subscribed_at = now();
            $subscriber->unsubscribed_at = null;
        }

        $subscriber->save();

        if ($subscriber->trashed()) {
            $subscriber->restore();
        }

        return $subscriber;
    }

    public function unsubscribe(): void
    {
        // Idempotent: an unsubscribe link gets clicked twice, and the second
        // click must not overwrite the date of the first.
        if (! $this->isActive()) {
            return;
        }

        $this->update([
            'status' => NewsletterStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ]);
    }

    public function resubscribe(): void
    {
        if ($this->isActive()) {
            return;
        }

        $this->update([
            'status' => NewsletterStatus::Active,
            'subscribed_at' => $this->subscribed_at ?? now(),
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * The one-per-recipient unsubscribe URL carried by every campaign.
     *
     * 🔴 SIGNED, never a bare id. An unsigned /unsubscribe/{id} lets anyone
     * walk the integers and unsubscribe the entire list. The signature also
     * makes the link unguessable for an address the sender does not own.
     *
     * ⚠️ No expiry. A newsletter sits in an inbox for months and an expired
     * unsubscribe link is precisely the thing that earns a spam complaint.
     */
    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', NewsletterStatus::Active);
    }

    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', NewsletterStatus::Unsubscribed);
    }

    /** Case-insensitive contains, for the admin quick search. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $q): Builder => $q->where('email', 'like', '%'.trim($term).'%'));
    }

    /** Newest first — the list reads backwards, like the inbox. */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('subscribed_at')->orderByDesc('id');
    }
}
