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
 * 🔴 للأصول التسويقية العامة حصراً (Slider · Testimonial). المحتوى المدفوع
 * لا يمرّ من هنا: lectures و library_files على القرص الخاص خلف controller
 * يفحص الصلاحية.
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
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'display_order' => 'integer',
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
    public function url(): string
    {
        return asset(ltrim($this->path, '/'));
    }
}
