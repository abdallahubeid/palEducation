<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'original_filename',
        'storage_path',
        'mime_type',
        'size_bytes',
        'is_published',
        'uploaded_by',
    ];

    /**
     * 🔴 storage_path محجوب عن أي تسلسل (JSON، استجابة، Livewire payload).
     * تسريب المسار الداخلي يكشف بنية القرص الخاص ويمنح المهاجم نصف الطريق.
     * download_count عدّاد يُدار بـincrement() لا بإسناد جملي من الطلب.
     */
    protected $hidden = [
        'storage_path',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'download_count' => 'integer',
            'is_published' => 'boolean',
        ];
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

    /** الحجم بصيغة مقروءة. الأرقام تبقى غربية 0-9 والوحدة LTR. */
    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size_bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, $unit === 0 ? 0 : 1).' '.$units[$unit];
    }
}
