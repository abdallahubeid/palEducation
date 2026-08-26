<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * المادة — كيان دائم يملكه فرع واحد.
 *
 * لا ترتبط بفصل دراسي: الفصل يربط بمحتواها (المحاضرات) لا بها، وإلا
 * لاحتاج كل فصل نسخة جديدة من كل مادة.
 */
class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'slug',
        'description',
        'icon',
        'accent_token',
        'display_order',
        'price_override_agora',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'price_override_agora' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * الـslug فريد داخل الفرع لا عالمياً، فلا يصلح مفتاح مسار وحده.
     * الربط يمرّ عبر الفرع: /branches/{branch:slug}/subjects/{subject:slug}
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** المعلمون المسنَدون — واحد اليوم (م-10)، والبنية تحتمل الكثرة. */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'subject_teacher')
            ->withPivot(['is_primary', 'assigned_at', 'assigned_by'])
            ->withCasts(['is_primary' => 'boolean', 'assigned_at' => 'datetime']);
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
    | النطاقات
    |--------------------------------------------------------------------------
    */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * ⚠️ فلتر يدوي مؤقّت. حين يوجد BranchScope عاماً يصير هذا زائداً
     * للطالب — لكنه يبقى مفيداً للأدمن الذي لا يقيّده النطاق العام.
     * لا تعتمد عليه وحده كضمانة أمنية: الفلترة في الاستعلام والتحقّق في
     * الـPolicy والإخفاء في الواجهة — الثلاثة معاً.
     */
    public function scopeForBranch(Builder $query, Branch|int $branch): Builder
    {
        return $query->where('branch_id', $branch instanceof Branch ? $branch->id : $branch);
    }

    /** ترتيب العرض المعتمد في كل شاشة تعرض المواد. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /** المعلّم صاحب الوجه العام للمادة. */
    public function primaryTeacher(): ?Teacher
    {
        return $this->teachers->firstWhere('pivot.is_primary', true)
            ?? $this->teachers->first();
    }
}
