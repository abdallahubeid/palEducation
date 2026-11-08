<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A pricing tier card on the public pricing section.
 *
 * 🔴 Marketing copy, not the billing record. What a student actually bought
 * belongs to `subscriptions` (phase 8, unbuilt). Editing a price here must
 * never rewrite what someone already paid.
 *
 * 🔴 Text only — no media relation, deliberately. The card is a heading, a
 * number and a bullet list; there is nothing here to upload.
 */
class PricingPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'badge',
        'price',
        'period',
        'features',
        'highlight_badge',
        'btn_text',
        'btn_link',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            // Whole shekels. Never a float — see the migration.
            'price' => 'integer',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Cheapest first is meaningless here — the order is editorial. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /** The ribbon plan. At most one is expected, but nothing enforces it. */
    public function isHighlighted(): bool
    {
        return filled($this->highlight_badge);
    }

    /** Text with no link is a dead button; a link with no text is invisible. */
    public function hasAction(): bool
    {
        return filled($this->btn_text) && filled($this->btn_link);
    }

    /**
     * Bullet list, always an array.
     *
     * ⚠️ The JSON column is nullable, so an untouched row casts to null and
     * a @foreach over it throws. Normalising here keeps every caller from
     * repeating the same null check.
     *
     * @return array<int, string>
     */
    public function featureList(): array
    {
        return array_values(array_filter((array) ($this->features ?? [])));
    }
}
