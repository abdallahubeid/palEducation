<?php

namespace App\Models;

use App\Enums\ContactSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A message sent through the public contact form.
 *
 * 🔴 The sender is stored as plain columns, never a link to `users`. Most
 * people who write in have no account, and the ones who do may delete it —
 * correspondence has to outlive both.
 *
 * Read and replied are separate states on purpose: a message can be read and
 * left unanswered, which is precisely what an inbox needs to surface.
 */
class ContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject_type',
        'message',
        'is_read',
        'replied_at',
        'reply_message',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'subject_type' => ContactSubject::class,
            'is_read' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    /**
     * Who replied.
     *
     * ⚠️ A `users` reference, not `admins`: this records the ACTOR, and the
     * column is nullOnDelete so removing a staff account cannot delete the
     * reply they sent — the recipient already has it in their inbox.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isReplied(): bool
    {
        return $this->replied_at !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeReplied(Builder $query): Builder
    {
        return $query->whereNotNull('replied_at');
    }

    public function scopeAwaitingReply(Builder $query): Builder
    {
        return $query->whereNull('replied_at');
    }

    /** Newest first — an inbox reads backwards. */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * Marks the message read, without touching anything else.
     *
     * ⚠️ Only writes when the flag actually changes. Opening a message
     * already read would otherwise bump `updated_at` on every view and make
     * the column useless for telling when a message was last acted on.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true]);
        }
    }
}
