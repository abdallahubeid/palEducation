<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * شريحة الهيرو في الصفحة الرئيسية.
 *
 * 🔴 حاوية الشرائح تحمل تحويلات طيّ ومحور RTL — أي تغيير على ترتيب
 * الشرائح يُختبر في الاتجاهين. شبكة CSS تنعكس مع dir مجاناً؛ لا تُضِف
 * أصنافاً اتجاهية لمعالجة الترتيب.
 */
class Slider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'badge_text',
        'title',
        'sub_title',
        'btn1_text',
        'btn1_link',
        'btn2_text',
        'btn2_link',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الوسائط — متعدّدة الأشكال
    |--------------------------------------------------------------------------
    */

    /** كل أصول هذه الشريحة (تحتمل نسخة سطح مكتب وأخرى للجوال). */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /** الصورة الواحدة المعروضة — أوّل أصل بالترتيب. */
    public function image(): MorphOne
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
    | مساعدات العرض
    |--------------------------------------------------------------------------
    */

    /** هل تحمل الشريحة دعوة أساسية مكتملة؟ نصّ بلا رابط زرّ ميّت. */
    public function hasPrimaryAction(): bool
    {
        return filled($this->btn1_text) && filled($this->btn1_link);
    }

    public function hasSecondaryAction(): bool
    {
        return filled($this->btn2_text) && filled($this->btn2_link);
    }
}
