<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * الفرع — الفلتر الجذري للمنصة.
 */
class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'accent_token',
        'btn_text',
        'btn_link',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** نصّ بلا رابط يُنتج زرّاً ميّتاً — يُفحص الاثنان معاً. */
    public function hasAction(): bool
    {
        return filled($this->btn_text) && filled($this->btn_link);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * كل محاضرات الفرع عبر مواده.
     *
     * موجودة لأن السؤال «ماذا في هذا الفرع؟» يُطرح في شاشة الفرع العامة
     * وفي إحصاءات الأدمن؛ بدونها يُجمع الجواب بحلقة على المواد = N+1.
     */
    public function lectures(): HasManyThrough
    {
        return $this->hasManyThrough(Lecture::class, Subject::class);
    }
}
