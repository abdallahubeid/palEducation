<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'title',
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
            ->withPivot(['is_primary', 'assigned_at', 'assigned_by'])
            ->withCasts(['is_primary' => 'boolean', 'assigned_at' => 'datetime']);
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
}
