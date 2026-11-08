<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A student review on the public home page.
 *
 * 🔁 REVERSAL, recorded. This used to be a pure marketing SNAPSHOT with no
 * link to a real account, so that deleting a student could not tear a
 * section out of the home page. A `student_id` link was requested and built
 * — but the snapshot columns stay as the fallback, so the guarantee holds:
 *
 *   student linked   → name, branch and city read live from the account
 *   student removed  → the FK nulls, and the stored name/title still render
 *
 * The quote itself always lives here. It is what the student said, not
 * something derived from their record.
 */
class Testimonial extends Model
{
    use HasFactory, SoftDeletes;

    /** المدى المسموح للتقييم — تُفرض في التطبيق لا في القاعدة. */
    public const MIN_RATE = 1;

    public const MAX_RATE = 5;

    /**
     * Which portrait the card renders.
     *
     * 🔴 The authority for the column, not the database. It is stored as a
     * string precisely so this list can grow without a schema migration —
     * the controller validates against these values, and anything else in
     * the column is treated as `student`.
     */
    public const AVATAR_STUDENT = 'student';

    public const AVATAR_CUSTOM = 'custom';

    public const AVATAR_SOURCES = [self::AVATAR_STUDENT, self::AVATAR_CUSTOM];

    protected $fillable = [
        // 🔴 Added with the migration, in the same batch. A validated column
        // missing from this array is dropped by Eloquent in SILENCE.
        'student_id',
        'name',
        // 🔴 Added with the migration, in the same batch. A validated column
        // missing from this array is dropped by Eloquent in SILENCE.
        'avatar_source',
        'title',
        'rate',
        'description',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'rate' => 'integer',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | The reviewer
    |--------------------------------------------------------------------------
    */

    /**
     * ⚠️ Nullable by design. A review can outlive the account that wrote it,
     * and the FK is nullOnDelete precisely so it does.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The name shown on the card: live from the account, else the snapshot.
     */
    public function displayName(): string
    {
        return (string) ($this->student?->full_name ?: $this->name);
    }

    /**
     * The line under the name — branch and city, live from the account.
     *
     * ⚠️ Falls back to the stored `title` whenever the account is gone OR
     * has no branch yet. Without that, unlinking a student would leave a
     * name floating over an empty line.
     */
    public function displayMeta(): string
    {
        $student = $this->student;

        if ($student) {
            $parts = array_filter([
                $student->branch?->name,
                $student->governorate ? __('governorates.'.$student->governorate) : null,
            ]);

            if ($parts !== []) {
                return implode(' · ', $parts);
            }
        }

        return (string) $this->title;
    }

    /** True when this review is set to use its own uploaded portrait. */
    public function usesCustomAvatar(): bool
    {
        return $this->avatar_source === self::AVATAR_CUSTOM;
    }

    /**
     * The portrait the card should render, or null for the placeholder.
     *
     * 🔁 This used to be pure precedence — attached media beat the student
     * avatar, always. That made the choice implicit and one-way: an admin
     * who uploaded a custom image could not go back to the account photo
     * without deleting the file. `avatar_source` now decides, and each
     * branch still falls back rather than rendering a broken image:
     *
     *   custom  → the attached asset, else the student's, else placeholder
     *   student → the student's, else placeholder
     *
     * ⚠️ The custom branch falls back to the student on purpose. Choosing
     * "custom" and not uploading anything yet is a normal half-finished
     * state, and an empty frame is a worse answer than the account photo.
     */
    public function photoUrl(): ?string
    {
        if ($this->usesCustomAvatar()) {
            $attached = $this->relationLoaded('photo') ? $this->photo : $this->photo()->first();

            if ($attached) {
                return $attached->url();
            }
        }

        $avatar = $this->student?->avatar_path;

        return filled($avatar) ? asset(ltrim($avatar, '/')) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | الوسائط — متعدّدة الأشكال
    |--------------------------------------------------------------------------
    */

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /** صورة صاحب الرأي — أصل واحد. */
    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->ofMany('display_order', 'min');
    }

    /*
    |--------------------------------------------------------------------------
    | النطاقات
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | التقييم
    |--------------------------------------------------------------------------
    */

    /**
     * التقييم مقصوصاً على المدى المعروض.
     *
     * unsignedTinyInteger يقبل حتى 255؛ القاعدة لا تعرف أن سبع نجوم لا
     * وجود لها. القصّ هنا يمنع حلقة عرض تطبع 255 نجمة.
     */
    public function stars(): int
    {
        return max(self::MIN_RATE, min(self::MAX_RATE, (int) $this->rate));
    }
}
