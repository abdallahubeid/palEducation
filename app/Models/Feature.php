<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * بطاقة ميزة في الصفحة الرئيسية.
 *
 * 🔴 نصّي بالكامل — **بلا علاقة وسائط عمداً**. البطاقة تُعرَض بأيقونة من
 * مجموعة x-icon لا بصورة مرفوعة.
 */
class Feature extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'btn_text',
        'btn_link',
        'icon',
        'accent_token',
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /** نصّ بلا رابط يُنتج زرّاً ميّتاً — يُفحص الاثنان معاً. */
    public function hasAction(): bool
    {
        return filled($this->btn_text) && filled($this->btn_link);
    }
}
