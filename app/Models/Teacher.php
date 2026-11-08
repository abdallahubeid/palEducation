<?php

namespace App\Models;

use App\Models\Pivots\SubjectTeacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * سجلّ المجال للمعلم — تُقرأ صفوفه من زوّار غير مسجّلين (صفحة الفرع).
 */
class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        // 🔴 Added with the migration, in the same batch. A column that
        // exists, is validated, is passed to create() and is missing from
        // this array is dropped by Eloquent in SILENCE — no exception, no
        // warning, no log. That trap has already cost this project a
        // fortnight of lost phone numbers.
        'branch_id',
        'title',
        'phone',
        'bio',
        'avatar_path',
        'social_links',
        'is_publicly_listed',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_publicly_listed' => 'boolean',
        ];
    }

    /** حساب الدخول المرتبط بهذا المعلم. */
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    /**
     * المواد المسنَدة — أساس قاعدة نطاق المعلّم: لا يرى مواد زملائه.
     *
     * 🔴 هذه العلاقة **مصدر بيانات لا ضمانة أمنية**. الفلترة في الاستعلام
     * والتحقّق في الـPolicy والإخفاء في الواجهة — الثلاثة معاً.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher')
            ->using(SubjectTeacher::class)
            ->withPivot(['is_primary', 'assigned_at', 'assigned_by']);
    }

    /** المواد التي هذا المعلّم وجهها العام. */
    public function primarySubjects(): BelongsToMany
    {
        return $this->subjects()->wherePivot('is_primary', true);
    }

    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class);
    }

    public function libraryFiles(): HasMany
    {
        return $this->hasMany(LibraryFile::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Public profile — the landing page card
    |--------------------------------------------------------------------------
    */

    /**
     * Portrait for the public teachers section.
     *
     * 🔴 Allowed on the media table because a teacher portrait is exactly
     * what that table is for: a PUBLIC marketing asset, the same category as
     * sliders and testimonials. It is not the paid-content boundary — that
     * one still holds absolutely: `lectures` and `library_files` declare no
     * morph here and must never gain one.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->ordered();
    }

    /** The single portrait — the first attached asset by display order. */
    public function image(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->oldest('display_order');
    }

    /**
     * Portrait URL, or null to let the card fall back to initials.
     *
     * ⚠️ Precedence is explicit and documented because there are two places
     * a portrait can live: an attached Media row (uploaded) wins over the
     * legacy `avatar_path` column (seeded repository asset). Without a
     * stated order the two would drift and nobody could say which is shown.
     */
    public function avatarUrl(): ?string
    {
        $attached = $this->relationLoaded('image') ? $this->image : $this->image()->first();

        if ($attached) {
            return $attached->url();
        }

        return filled($this->avatar_path) ? asset(ltrim($this->avatar_path, '/')) : null;
    }

    /**
     * The branch this teacher belongs to, through their primary subject.
     *
     * 🔴 DERIVED, never stored. There is no `teachers.branch_id` and there
     * must not be: a teacher reaches a branch only through the subjects
     * assigned to them, and a second column would drift the moment an
     * assignment changed. `subjects.branch_id` stays the single path.
     *
     * ⚠️ Reads loaded relations rather than querying, so the caller controls
     * the query count — eager load `primarySubjects.branch` or this is N+1.
     */
    /**
     * The branch shown on this teacher's public card.
     *
     * 🔴 Explicit `branch_id` wins, and the subject derivation is the
     * fallback. Both paths are kept on purpose: the column exists so an
     * admin can state a teacher's branch directly, but a teacher nobody has
     * set one for still shows the branch their subject belongs to instead
     * of an empty chip.
     *
     * ⚠️ The two CAN disagree — an explicit branch with subjects in another
     * one. That is the drift a stored copy always risks, so the card is the
     * only thing that reads this. Content access still runs through
     * `subjects.branch_id` alone.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function primaryBranch(): ?Branch
    {
        return $this->branch ?? $this->primarySubject()?->branch;
    }

    /**
     * The subject this teacher is the public face of.
     *
     * ⚠️ Filters the ALREADY LOADED `subjects` collection on its pivot
     * rather than reading `primarySubjects`. Both relations point at the
     * same table, so eager loading each one separately fetched the subjects
     * and their branches twice — two extra queries per page, which the
     * debug bar reported as an N+1.
     */
    public function primarySubject(): ?Subject
    {
        return $this->subjects->firstWhere('pivot.is_primary', true)
            ?? $this->subjects->first();
    }

    /**
     * The line under the name on the card.
     *
     * `title` is the editorial label ("الرياضيات"); the subject name is the
     * fallback when nobody has written one.
     */
    public function displayTitle(): string
    {
        return filled($this->title)
            ? $this->title
            : (string) ($this->primarySubject()?->name ?? '');
    }

    /** Initials for the card when there is no portrait at all. */
    public function initials(): string
    {
        // Strip the honorific ("أ.") so the initials come from the name.
        $name = trim(preg_replace('/^\s*\S{1,2}\.\s*/u', '', (string) $this->full_name));

        return collect(preg_split('/\s+/u', $name))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Only teachers who agreed to appear publicly. */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->where('is_publicly_listed', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('id');
    }
}
