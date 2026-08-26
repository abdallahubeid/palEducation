<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * سجلّ المجال للأدمن — بسيط عمداً.
 * وجوده يُبقي المؤشّر متعدّد الأشكال في users إجبارياً بلا استثناء.
 */
class Admin extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'avatar_path',
    ];

    /** حساب الدخول المرتبط بهذا الأدمن. */
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }
}
