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
        'unit_name',
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
    /*
    |--------------------------------------------------------------------------
    | يوتيوب
    |--------------------------------------------------------------------------
    */

    /**
     * يستخرج معرّف فيديو يوتيوب من رابط، أو null إن لم يكن رابط يوتيوب.
     *
     * 🔴 قائمة سماح للمضيف لا بحث عن نصّ «youtube» في الرابط. فحص
     * str_contains كان سيقبل `evil.test/youtube.com/x` — وهو رابط لطرف
     * ثالث يُضمَّن في iframe داخل صفحات طلبتنا.
     *
     * ⚠️ المعرّف نفسه يُتحقَّق من شكله (11 محرفاً من مجموعة محدّدة): ما
     * يُخزَّن هنا يُركَّب لاحقاً في عنوان iframe، فقيمة غير مقيّدة تعني
     * حقن معاملات في ذلك العنوان.
     */
    public static function youtubeId(string $url): ?string
    {
        $url = trim($url);

        // يقبل الرابط بلا بروتوكول: المعلّم ينسخ من شريط العنوان غالباً.
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if (! isset($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^www\./i', '', $parts['host']));
        $path = trim($parts['path'] ?? '', '/');

        $candidate = match (true) {
            // youtu.be/VIDEO_ID
            $host === 'youtu.be' => $path,

            // youtube.com/watch?v=VIDEO_ID
            in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com'], true)
                && $path === 'watch' => self::queryParam($parts['query'] ?? '', 'v'),

            // youtube.com/embed/VIDEO_ID و youtube.com/shorts/VIDEO_ID
            in_array($host, ['youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)
                && preg_match('#^(embed|shorts|v)/(.+)$#', $path, $m) === 1 => $m[2],

            default => null,
        };

        if ($candidate === null) {
            return null;
        }

        // قد يبقى جزء مسار بعد المعرّف (youtu.be/ID/extra) — يُقتطع.
        $candidate = explode('/', $candidate)[0];

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) === 1 ? $candidate : null;
    }

    /** قراءة معامل واحد من سلسلة استعلام بلا الاعتماد على ترتيبها. */
    private static function queryParam(string $query, string $key): ?string
    {
        parse_str($query, $params);

        return is_string($params[$key] ?? null) ? $params[$key] : null;
    }

    /**
     * رابط التضمين النظيف، أو null لغير اليوتيوب.
     *
     * ⚠️ يُبنى من المعرّف في كل مرة ولا يُخزَّن: الرابط المخزَّن يتجمّد على
     * صيغة نطاق قد تتغيّر، والمعرّف لا يتغيّر.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        return $this->video_source === VideoSource::YouTube && filled($this->video_reference)
            ? 'https://www.youtube.com/embed/'.$this->video_reference
            : null;
    }

    /** رابط المشاهدة العادي — لفتح الفيديو خارج المنصّة. */
    public function getWatchUrlAttribute(): ?string
    {
        return $this->video_source === VideoSource::YouTube && filled($this->video_reference)
            ? 'https://www.youtube.com/watch?v='.$this->video_reference
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | نطاق المعلّم
    |--------------------------------------------------------------------------
    */

    /**
     * محاضرات المواد المسندة لهذا المعلّم وحدها.
     *
     * 🔴 الفلترة على **المادة** لا على lectures.teacher_id: المعلّم يدير
     * محتوى مادته كلّها، بما فيه ما رفعه زميل سبقه على نفس المادة. والعكس
     * أخطر — teacher_id وحده كان سيُخفي عنه محاضرات مادته الحالية.
     */
    public function scopeForTeacher(Builder $query, Teacher $teacher): Builder
    {
        return $query->whereIn('subject_id', $teacher->subjects()->select('subjects.id'));
    }

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
