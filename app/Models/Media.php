<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * أصل وسائط مرتبط بأي كيان يُعلن علاقة morph إليه.
 *
 * 🔁 انعكاس مسجَّل. كان هذا الجدول للأصول التسويقية العامة حصراً، والحارس
 * بنيوياً: لا LibraryFile يُعرّف morph إليه. صار الجدول الآن يحمل **كل**
 * بيانات الملفات — العامة والخاصة — وحلّ محلّ الحارس البنيوي عمود `disk`:
 *
 *   public  → أصل تسويقي، يُخدَم مباشرةً وasset() يبني رابطه
 *   private → محتوى مدفوع، url() يُرجع null والتسليم عبر controller
 *
 * ⚠️ ما زال ممنوعاً: أي مسار يبني رابطاً عاماً لصفّ خاص. lectures لم تُضَف
 * هنا بعد لأن الفيديو مصدره مزوّد خارجي لا ملف عندنا.
 */
class Media extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * جمع «media» هو نفسه مفرده، فتخمين Laravel ينتج «medias».
     * يُعلَن صراحةً بدل الاعتماد على الاشتقاق.
     */
    protected $table = 'media';

    protected $fillable = [
        'path',
        'type',
        'alt_text',
        'display_order',
        'mediable_id',
        'mediable_type',
        'disk',
        'original_filename',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'display_order' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقة متعدّدة الأشكال
    |--------------------------------------------------------------------------
    */

    /** الكيان المالك — Slider أو Testimonial. */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | النطاقات
    |--------------------------------------------------------------------------
    */

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', MediaType::Image);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | مساعدات
    |--------------------------------------------------------------------------
    */

    /**
     * الرابط العام للأصل.
     *
     * 🔴 asset() لا Storage::url(): هذه أصول عامة تعيش تحت public/ ويقصد
     * أن تُخدَم مباشرة. القاعدة المضادّة — «الملفات لا تُخدَّم من public» —
     * تخصّ المحتوى **المدفوع**، وهو لا يمرّ من هذا الجدول أصلاً.
     */
    public function url(): ?string
    {
        /*
         * 🔴 الحارس الذي حلّ محلّ الحارس البنيوي.
         *
         * كان يستحيل أن يصل ملف مكتبة إلى هذا الجدول أصلاً، فكان asset()
         * آمناً بالتعريف. بعد ضمّ ملفات المكتبة صار ممكناً — فلو بقيت هذه
         * الدالة كما هي لأنتجت رابطاً عاماً صالحاً لمحتوى مدفوع بمجرّد
         * استدعاء $media->url() في أي قالب.
         *
         * null لا استثناء: القوالب تعرض بديلاً حين لا رابط، ورمي استثناء
         * هنا كان سيُسقط صفحة كاملة بسبب صورة.
         */
        if ($this->isPrivate()) {
            return null;
        }

        return asset(ltrim($this->path, '/'));
    }

    /** هل يعيش هذا الأصل خلف فحص صلاحية؟ */
    public function isPrivate(): bool
    {
        return $this->disk === 'private';
    }

    /**
     * الحجم بصيغة مقروءة. الأرقام تبقى غربية 0-9 والوحدة LTR.
     *
     * 🔁 نُقلت كما هي من LibraryFile مع العمود نفسه — لا صيغة ثانية. تنسيقان
     * لنفس الرقم ينحرفان، ويظهر الملف بحجمين مختلفين في شاشتين.
     */
    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size_bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return ($unit === 0 ? (int) $size : round($size, 1)).' '.$units[$unit];
    }
}
