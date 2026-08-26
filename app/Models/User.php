<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * جدول الدخول. لا يحمل اسماً ولا فرعاً.
 *
 * الدور لا يُخزَّن هنا كعمود: يعيش في جداول Spatie ويُسنَد إلى هذا
 * النموذج حصراً — لأن الحارس يصادق User، فيجب أن يُحسَم سؤال الصلاحية
 * على النموذج نفسه الذي يُرجعه الحارس.
 *
 * @property-read Student|Teacher|Admin $userable
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /*
     | 🔴 email_verified_at مُدرَج عمداً: تسجيل جوجل يُنشئ الحساب موثَّقاً
     | (جوجل تحقّقت من البريد أصلاً). بدونه كان create() يُسقطه بصمت —
     | بلا خطأ ولا تحذير — فيُطالَب مستخدم جوجل بتوثيق بريد موثَّق سلفاً.
     | آمن هنا لأن أياً من مسارات المصادقة لا يمرّر مدخلات الطلب جملةً
     | إلى User::create؛ كل حقل يُكتب صراحةً.
     */
    protected $fillable = [
        'email',
        'email_verified_at',
        'password',
        'google_id',
        'google_avatar_url',
        'userable_type',
        'userable_id',
        'is_active',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    /** سجلّ المجال: Student أو Teacher أو Admin. */
    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | تمييز النوع — مميّز «الشكل»
    |--------------------------------------------------------------------------
    | userable_type يجيب: أي جدول يحمل اسم هذا الشخص وفرعه؟
    | دور Spatie يجيب سؤالاً آخر: ماذا يملك أن يفعل؟
    | لا يُخلط بينهما ولا يُستعمل أحدهما مكان الآخر — لكنهما يجب أن يتفقا،
    | ولهذا roleName() تشتقّ الدور من الشكل بدل تركه لاجتهاد كل مسار.
    */

    public function isStudent(): bool
    {
        return $this->userable_type === Student::class;
    }

    public function isTeacher(): bool
    {
        return $this->userable_type === Teacher::class;
    }

    public function isAdmin(): bool
    {
        return $this->userable_type === Admin::class;
    }

    /**
     * اسم الدور الذي يوجبه شكل هذا الحساب — المصدر الوحيد لهذا الربط.
     *
     * الثابت 4: حساب userable_type = Student يحمل الدور student وحده.
     * لا مفتاح أجنبي يفرض هذا (جدول Spatie متعدّد الأشكال)، فالتطبيق
     * هو الحارس — وحارس واحد أضمن من ثلاثة متفرّقة في المسارات.
     */
    public function roleName(): string
    {
        return match (true) {
            $this->isTeacher() => 'teacher',
            $this->isAdmin() => 'admin',
            default => 'student',
        };
    }

    /**
     * يزامن دور الحساب مع شكله. تُستدعى فور إنشاء أي حساب، داخل نفس
     * المعاملة — إنشاء حساب بلا دور يُنتج حساباً بلا صلاحيات لا يشتكي.
     */
    public function syncRoleToShape(): self
    {
        $this->syncRoles([$this->roleName()]);

        return $this;
    }

    /** الاسم المعروض — يعيش في سجلّ المجال لا هنا. */
    public function displayName(): string
    {
        return $this->userable?->full_name ?? '';
    }

    /**
     * 🔴 `name` ليس عموداً في users — الاسم يعيش على سجلّ المجال
     * (students/teachers/admins). هذا الوسيط يجعل `$user->name` يعمل كما
     * يُتوقَّع في القوالب بدل أن يُرجع null بصمت.
     */
    public function getNameAttribute(): string
    {
        return $this->displayName();
    }

    /** الصورة: المرفوعة أولاً، ثم صورة جوجل. */
    public function avatar(): ?string
    {
        return $this->userable?->avatar_path ?? $this->google_avatar_url;
    }

    /**
     * لوحة التحكّم الافتراضية بحسب نوع الحساب.
     *
     * الأدمن يهبط على لوحة التحكّم. كل مسارات الدخول (كلمة مرور · جوجل ·
     * توثيق البريد) تمرّ من هنا، فسطر واحد يغطّيها جميعاً.
     */
    public function homeRoute(): string
    {
        return match (true) {
            $this->isAdmin() => route('admin.dashboard'),
            $this->isTeacher() => route('teacher.dashboard'),
            default => route('student.dashboard'),
        };
    }
}
