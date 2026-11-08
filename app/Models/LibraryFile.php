<?php

namespace App\Models;

use App\Enums\LibraryCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ملف مكتبة — مرفق على مستوى المادة، وقد يُشدّ إلى محاضرة بعينها.
 *
 * 🔴 يعيش على القرص الخاص. لا Storage::url() ولا أي مسار تحت public/:
 * التسليم برابط موقّع قصير العمر عبر controller يقيّم الصلاحية أولاً.
 */
class LibraryFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'lecture_id',
        'teacher_id',
        'title',
        'category',
        'unit_name',
        'page_count',
        'is_published',
        'published_at',
        'uploaded_by',
    ];

    /*
     * 🔁 storage_path لم يعد هنا — انتقل إلى media مع بقية مقاييس الملف،
     * وحجبه صار مسؤولية Media نفسه.
     *
     * ⚠️ download_count يبقى عدّاداً يُدار بـincrement() لا بإسناد جملي من
     * الطلب — ولهذا هو غائب عن $fillable أعلاه.
     */

    protected function casts(): array
    {
        return [
            'category' => LibraryCategory::class,
            'page_count' => 'integer',
            'download_count' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    /**
     * الملف نفسه.
     *
     * 🔴 morphOne لا morphMany: لملف المكتبة أصل واحد بالتعريف. morphMany
     * كان سيسمح بصفّين لنفس السجلّ ويجعل «الملف» أيّهما أرجعه الاستعلام.
     *
     * ⚠️ الصفّ يحمل disk = 'private'، فـMedia::url() يُرجع null له عمداً —
     * التسليم عبر controller يفحص الصلاحية، لا برابط مباشر.
     */
    public function media(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    /** الوحدة المعروضة: الموروثة من المحاضرة إن وُجدت، وإلا الخاصة بالملف. */
    public function unitLabel(): ?string
    {
        /*
         * 🔴 تُحسب ولا تُخزَّن. نسخ وحدة المحاضرة إلى الملف يعني قيمتين
         * تنحرفان أول ما يعدّل المعلّم وحدة المحاضرة — والملف يبقى معلّقاً
         * على وحدة لم تعد موجودة.
         */
        if ($this->relationLoaded('lecture') && $this->lecture && filled($this->lecture->unit_name)) {
            return $this->lecture->unit_name;
        }

        return filled($this->unit_name) ? $this->unit_name : null;
    }

    /**
     * هل يمكن عرضه داخل المتصفّح؟
     *
     * ⚠️ PDF فقط. زرّ «معاينة» على ملف Word أو ZIP زرّ لا يعمل — وغيابه
     * أصدق من تعطيله.
     */
    public function isPreviewable(): bool
    {
        return $this->media?->mime_type === 'application/pdf';
    }

    /*
    |--------------------------------------------------------------------------
    | النطاقات
    |--------------------------------------------------------------------------
    */

    /**
     * ملفات المواد المسندة لهذا المعلّم.
     *
     * 🔴 الفلترة على **المادة** لا على uploaded_by: المعلّم يدير ملفات مادته
     * كلها بما رفعه زميل سبقه، تماماً كالمحاضرات.
     */
    public function scopeForTeacher(Builder $query, Teacher $teacher): Builder
    {
        return $query->whereIn('subject_id', $teacher->subjects()->select('subjects.id'));
    }

    /** ما يراه الطالب: منشور وحلّ تاريخه. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** اختيارية: ملف على مستوى المادة لا يخصّ محاضرة بعينها. */
    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    /** المعلّم المالك — مرجع مجال، عليه يقوم نطاق المعلّم. */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /** مَن نفّذ الرفع — مرجع فاعل، قد يكون أدمن ينوب عن المعلّم. */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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

    /** ملفات المادة العامة — أي غير المشدودة إلى محاضرة بعينها. */
    public function scopeSubjectLevel(Builder $query): Builder
    {
        return $query->whereNull('lecture_id');
    }

    /*
    |--------------------------------------------------------------------------
    | مساعدات
    |--------------------------------------------------------------------------
    */

    /** عدّاد التنزيل — ذرّي، وبلا لمس updated_at (التنزيل ليس تعديلاً). */
    public function recordDownload(): void
    {
        $this->incrementQuietly('download_count');
    }

    /**
     * الحجم بصيغة مقروءة.
     *
     * 🔁 الرقم انتقل إلى media، فالدالة تُفوَّض بدل أن تُحذف: اسمها عقد قائم
     * تستعمله شاشات أخرى، وحذفه كان سيكسرها. تُرجع '0 B' حين لا ملف — وهي
     * الحالة الوحيدة التي لا حجم فيها أصلاً.
     */
    public function humanSize(): string
    {
        return $this->media?->humanSize() ?? '0 B';
    }
}
