<?php

namespace App\Models;

use App\Enums\TermStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * الفصل الدراسي — وحدة تقويمية على مستوى المنصة، لا تخصّ فرعاً بعينه.
 *
 * سجلّ تاريخي لا إعداد يُرمى: المحاضرات والطلبات والاشتراكات تشير إليه
 * بمفاتيح restrict، فلا يُحذف ما دام شيء يذكره. الإغلاق بـ archived.
 */
class AcademicTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'academic_year',
        'starts_on',
        'ends_on',
        'status',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => TermStatus::class,
            'is_current' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class);
    }

    // ⏳ تُفعَّل مع المرحلة 8:
    // orders()        → HasMany(Order::class)
    // subscriptions() → HasMany(Subscription::class)

    /*
    |--------------------------------------------------------------------------
    | النطاقات
    |--------------------------------------------------------------------------
    */

    /** الفصول المفتوحة للطلبات. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TermStatus::Active);
    }

    /*
    |--------------------------------------------------------------------------
    | الفصل الجاري
    |--------------------------------------------------------------------------
    */

    /** الفصل الذي تتبنّاه الاشتراكات الجديدة افتراضياً. */
    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    /**
     * يجعل هذا الفصل هو الجاري — ويُنزل العلم عن غيره في معاملة واحدة.
     *
     * 🔴 القاعدة «صفّ واحد فقط is_current» لا تفرضها قاعدة البيانات:
     * الفهرس الفريد الجزئي غير محمول بين المحرّكات. فرضها هنا يعني أن
     * يمرّ كل تغيير عبر هذا الباب — لا أن يُكتب العمود مباشرة في مسارات
     * متفرّقة، فتظهر لحظة يحمل فيها صفّان العلم أو لا يحمله أحد.
     */
    public function makeCurrent(): self
    {
        static::getConnection()->transaction(function () {
            static::where('id', '!=', $this->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            // فصل جارٍ ومغلق للطلبات في آنٍ معاً حالة متناقضة — تُمنع هنا.
            $this->forceFill([
                'is_current' => true,
                'status' => TermStatus::Active,
            ])->save();
        });

        return $this;
    }
}
