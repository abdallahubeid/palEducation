<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A news article.
 *
 * 🔴 Two separate gates decide whether the public sees it: `is_published`
 * (the editor's switch) and `published_at` (the clock). Both must pass. An
 * article scheduled for next week is published in the admin's sense and
 * invisible in the visitor's, and collapsing the two would either publish it
 * early or make scheduling impossible.
 */
class News extends Model
{
    use HasFactory, SoftDeletes;

    /** ⚠️ Laravel would guess `news` is plural of `new` and look for `news`. It is right by accident — stated so it survives a rename. */
    protected $table = 'news';

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'summary',
        'content',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The by-line.
     *
     * ⚠️ Falls back to the platform's own name rather than blanking. An
     * article whose author's account was deleted still needs a by-line —
     * an empty one reads as a broken page, not as a departed colleague.
     */
    public function authorName(): string
    {
        return $this->author?->displayName()
            ?: __('common.brand');
    }

    /**
     * Every asset attached to this article.
     *
     * 🔴 Within the media table's stated scope: PUBLIC MARKETING assets. The
     * standing prohibition is on PAID content — no Lecture and no LibraryFile
     * may declare a morph here, because those live on the private disk behind
     * a permission check.
     *
     * ⚠️ Declaring media() also opts this model into TrashManager::purgeMedia,
     * which is what stops a permanently deleted article leaving orphaned rows
     * and files behind — mediable_id has no foreign key, so nothing cascades.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /** The single cover — first asset by display order. */
    public function image(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->ofMany('display_order', 'min');
    }

    /**
     * Cover image URL, or null so the card falls back to its placeholder.
     *
     * ⚠️ Reads the LOADED relation when there is one, so a list that eager
     * loads `image` costs one query for the whole page rather than one per
     * article. Without that check this accessor is an N+1 hiding in a
     * property access, which is the hardest kind to notice.
     */
    public function getCoverUrlAttribute(): ?string
    {
        $cover = $this->relationLoaded('image') ? $this->image : $this->image()->first();

        return $cover?->url();
    }

    /**
     * Whether a visitor may see this article.
     *
     * 🔴 The same two conditions as scopePublished, kept beside it. A route
     * that checked only `is_published` would serve a scheduled article to
     * anyone holding its slug.
     */
    public function isVisible(): bool
    {
        return $this->is_published
            && $this->published_at !== null
            && $this->published_at->lessThanOrEqualTo(now());
    }

    /**
     * Rough reading time in minutes.
     *
     * ⚠️ DERIVED, never stored. The card shows it, but a column would have
     * to be recalculated on every edit and would silently go stale the first
     * time someone forgot. 180 words a minute is the usual figure for Arabic
     * prose; the floor of 1 stops a short notice reading "0 دقيقة".
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->content)) / 180));
    }

    /**
     * A slug that is unique, derived from the title when none is given.
     *
     * 🔴 Arabic titles matter here: Str::slug() strips non-latin characters,
     * so "إعلان مواعيد الامتحانات" becomes an EMPTY string and every article
     * would collide on ''. The fallback keeps the Arabic, replacing only
     * whitespace, which produces a readable percent-encoded URL.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = trim(preg_replace('/\s+/u', '-', trim($title)), '-');
        }

        $base = $base === '' ? 'news' : $base;
        $slug = $base;
        $suffix = 2;

        while (self::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            // ⚠️ withTrashed above: `slug` is unique and the index does not
            // ignore deleted_at, so a trashed article still occupies its slug.
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** What a visitor may see: switched on AND due. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** Newest first — a news list reads backwards. */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }
}
