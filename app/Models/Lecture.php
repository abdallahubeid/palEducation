<?php

namespace App\Models;

use App\Enums\LectureStatus;
use App\Enums\VideoSource;
use App\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * المحاضرة — الوحدة المركزية للمحتوى.
 *
 * تنتمي إلى مادة (أين؟) وفصل دراسي (متى؟) ومعلّم (لمن؟) — الثلاثة إجبارية.
 */
class Lecture extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'academic_term_id',
        'teacher_id',
        'title',
        'slug',
        'description',
        'display_order',
        'video_source',
        'video_reference',
        'video_duration_seconds',
        'video_status',
        'thumbnail_path',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'video_duration_seconds' => 'integer',
            'video_source' => VideoSource::class,
            'video_status' => VideoStatus::class,
            'status' => LectureStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /** فريد داخل المادة لا عالمياً — يُربط دائماً متداخلاً تحت مادته. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /** المعلّم المالك — عليه يقوم نطاق المعلّم، لا على «مَن أنشأ». */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function libraryFiles(): HasMany
    {
        return $this->hasMany(LibraryFile::class);
    }

    // ⏳ تُفعَّل مع مراحل لاحقة:
    // quiz()     → HasOne(Quiz::class)             — إجبارية عند النشر (م-3)
    // progress() → HasMany(LectureProgress::class) — الحلقة المغلقة (م-5)

    /*
    |--------------------------------------------------------------------------
    | النطاقات
    |--------------------------------------------------------------------------
    */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', LectureStatus::Published);
    }

    public function scopeForTerm(Builder $query, AcademicTerm|int $term): Builder
    {
        return $query->where('academic_term_id', $term instanceof AcademicTerm ? $term->id : $term);
    }

    /** الترتيب المعتمد داخل المادة. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | جاهزية النشر
    |--------------------------------------------------------------------------
    */

    /**
     * هل تصلح هذه المحاضرة للنشر؟
     *
     * 🔴 المدة شرط لا زينة: م-5 يجعل الاكتمال تحقّقاً خادمياً عند 90%،
     * و90% من مدة مجهولة لا تُحسب — فمحاضرة بلا مدة لا تفتح كويزها أبداً.
     * والمدة يجب أن تكون **مأخوذة من الخادم**؛ قيمة يرسلها العميل هي
     * بالضبط ما يريد المهاجم التحكّم به.
     *
     * ⏳ ناقص هنا عمداً: شرط م-3 (كويز بسؤال واحد على الأقل) — يُضاف مع
     * جدول quizzes في المرحلة 5. لا تعتبر هذه الدالة كاملة قبله.
     */
    public function isReadyToPublish(): bool
    {
        return $this->video_status === VideoStatus::Ready
            && $this->video_reference !== null
            && $this->video_duration_seconds > 0;
    }

    /** عدد الثواني اللازمة لاعتبار المشاهدة مكتملة (عتبة م-2: 90%). */
    public function completionThresholdSeconds(?int $percent = null): ?int
    {
        if (! $this->video_duration_seconds) {
            return null;
        }

        return (int) ceil($this->video_duration_seconds * ($percent ?? 90) / 100);
    }
}
