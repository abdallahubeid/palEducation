<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * خطوة في قسم «كيف تبدأ».
 *
 * 🔴 نصّي بالكامل — **بلا علاقة وسائط عمداً**. لا media() ولا morphMany
 * هنا، وغيابها هو الضمانة: مكوّن العرض لا يجد ما يعرضه فلا تتسرّب صورة
 * إلى قسم صُمّم رقماً وأيقونة ونصّاً.
 */
class Step extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'sub_title',
        'icon',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** الترتيب هنا دلالي: «سجّل ← اختر فرعك ← ادفع ← ابدأ». */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
