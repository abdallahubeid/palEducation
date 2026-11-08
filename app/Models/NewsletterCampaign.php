<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One newsletter send, kept as a record of what went out.
 *
 * 🔴 An archive, not a draft. Nothing here is editable after dispatch: the
 * message is already in other people's inboxes, and a screen that let it be
 * rewritten afterwards would show a history that never happened. Sending
 * again is a NEW row, which is what "نسخ كحملة جديدة" produces.
 */
class NewsletterCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admin_id',
        'subject',
        'body',
        'recipients_count',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'recipients_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * The sender's display name.
     *
     * ⚠️ Falls back rather than blanking. A campaign whose sender's account
     * was deleted still has to name a row in the table — an empty cell reads
     * as a bug in the screen rather than as a departed colleague.
     */
    public function senderName(): string
    {
        return $this->admin?->displayName()
            ?: ($this->admin?->email ?? 'حساب محذوف');
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    /**
     * Closes the campaign once the whole batch has run.
     *
     * 🔴 Idempotent, and it will be called more than once: the batch's
     * finally() callback fires on completion, and a job's failed() hook may
     * have marked the campaign already. Writing sent_at unconditionally would
     * move the timestamp every time.
     */
    public function markFinished(bool $hasFailures): void
    {
        if ($this->sent_at !== null) {
            return;
        }

        $this->update([
            'status' => $hasFailures ? CampaignStatus::Failed : CampaignStatus::Completed,
            'sent_at' => now(),
        ]);
    }

    /**
     * Marks the campaign failed the moment a job gives up.
     *
     * ⚠️ Leaves sent_at alone. The rest of the batch keeps running — failures
     * are allowed precisely so one bad address cannot stop the send — so the
     * campaign is not finished yet, only doomed to be recorded as failed.
     */
    public function markFailed(): void
    {
        if ($this->status === CampaignStatus::Failed) {
            return;
        }

        $this->update(['status' => CampaignStatus::Failed]);
    }

    public function isProcessing(): bool
    {
        return $this->status === CampaignStatus::Processing;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Newest first — a history reads backwards. */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
