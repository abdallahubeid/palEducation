<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * سجلّ المجال للطالب — الاسم والفرع يعيشان هنا لا في users.
 */
class Student extends Model
{
    use HasFactory, SoftDeletes;

    /*
     | 🔴 phone مُدرَج عمداً. الهوية بريد إلكتروني، وهذا هاتف تواصل لا
     | اعتماد — لكنّ شاشة التسجيل تجمعه و RegisteredUserController يمرّره،
     | وغيابه من هنا كان يجعل Eloquent يُسقطه بصمت: لا خطأ ولا تحذير،
     | فقط عمود يبقى NULL أبداً. نفس نمط الفخّ المسجّل في doctrine.md.
     */
    protected $fillable = [
        'full_name',
        'branch_id',
        'school_name',
        'governorate',
        'country_code',
        'phone',
        'guardian_phone',
        'avatar_path',
    ];

    /** حساب الدخول المرتبط بهذا الطالب. */
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    /**
     * فرع الطالب — الفلتر الجذري للمنصة.
     *
     * وجودها شرط لـ with('branch') في أي شاشة تعرض قائمة طلبة؛ بدونها
     * كل صفّ يستدعي استعلاماً منفصلاً (N+1).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** تقدّم مشاهدة هذا الطالب لكل محاضرة لمسها — م-5. */
    public function progress(): HasMany
    {
        return $this->hasMany(LectureProgress::class);
    }

    /** ملفات المكتبة التي وصل إليها هذا الطالب — لعدّاد الموارد. */
    public function libraryAccess(): HasMany
    {
        return $this->hasMany(StudentLibraryAccess::class);
    }

    // ⏳ العلاقات الآتية تُفعَّل مع موجات لاحقة:
    // subscriptions() → HasMany(Subscription::class)
    // attempts()      → HasMany(QuizAttempt::class)
}
