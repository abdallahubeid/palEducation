<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * رأي طالب — لقطة تسويقية لا مرجع إلى حساب حقيقي.
 */
class Testimonial extends Model
{
    use HasFactory, SoftDeletes;

    /** المدى المسموح للتقييم — تُفرض في التطبيق لا في القاعدة. */
    public const MIN_RATE = 1;

    public const MAX_RATE = 5;

    protected $fillable = [
        'name',
        'title',
        'rate',
        'description',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'integer',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الوسائط — متعدّدة الأشكال
    |--------------------------------------------------------------------------
    */

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /** صورة صاحب الرأي — أصل واحد. */
    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->ofMany('display_order', 'min');
    }

    /*
    |--------------------------------------------------------------------------
    | النطاقات
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | التقييم
    |--------------------------------------------------------------------------
    */

    /**
     * التقييم مقصوصاً على المدى المعروض.
     *
     * unsignedTinyInteger يقبل حتى 255؛ القاعدة لا تعرف أن سبع نجوم لا
     * وجود لها. القصّ هنا يمنع حلقة عرض تطبع 255 نجمة.
     */
    public function stars(): int
    {
        return max(self::MIN_RATE, min(self::MAX_RATE, (int) $this->rate));
    }
}
