<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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

    /*
    |--------------------------------------------------------------------------
    | صورة الحساب
    |--------------------------------------------------------------------------
    */

    /**
     * أصول هذا الحساب — صورة واحدة عملياً.
     *
     * 🔴 داخل نطاق جدول media المعلَن: أصول عامة على القرص العام. المنع
     * القائم يخصّ **المحتوى المدفوع** — لا Lecture ولا LibraryFile يُعرّف
     * morph هنا أبداً.
     *
     * ⚠️ الرابط عامّ لمن يعرفه. هذا سلوك الصور الرمزية في كل تطبيق تقريباً،
     * لكنه يعني ألّا تُرفع هنا صورة يُفترض أنها خاصّة.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /** الصورة الواحدة — أوّل أصل بالترتيب. */
    public function image(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->ofMany('display_order', 'min');
    }

    /**
     * رابط الصورة، أو null فيرجع المكوّن إلى الحرف الأول.
     *
     * 🔴 الأسبقية معلنة صراحةً وبهذا الترتيب:
     *   ١) الصورة المرفوعة من شاشة الملف الشخصي (صفّ media) — فعل المستخدم
     *      المباشر، فيجب أن يغلب كل ما عداه.
     *   ٢) avatar_path على سجلّ المجال — أصل مبذور من المستودع.
     *   ٣) google_avatar_url — يأتي من مزوّد الدخول ولا يملك المستخدم تغييره.
     *
     * بلا ترتيب معلن، ثلاثة مصادر للصورة نفسها تنحرف ولا أحد يعرف أيّها يظهر.
     *
     * ⚠️ يقرأ العلاقة المحمّلة إن وُجدت: بدون ذلك يصير هذا المُلحِق استعلاماً
     * لكل صفّ في أي قائمة — N+1 مختبئ داخل قراءة خاصيّة.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $uploaded = $this->relationLoaded('image') ? $this->image : $this->image()->first();

        if ($uploaded) {
            return $uploaded->url();
        }

        if (filled($this->userable?->avatar_path)) {
            return asset(ltrim($this->userable->avatar_path, '/'));
        }

        return $this->google_avatar_url;
    }

    /**
     * الصورة: المرفوعة أولاً، ثم سجلّ المجال، ثم جوجل.
     *
     * ⚠️ مُبقاة كاسم قديم يفوّض إلى المُلحِق — كانت تقرأ avatar_path وحده،
     * فاستدعاء متبقٍّ لها كان سيتجاهل الصورة المرفوعة بصمت.
     */
    public function avatar(): ?string
    {
        return $this->avatar_url;
    }

    /** هل يملك صورة مرفوعة يمكن حذفها؟ (صورة جوجل ليست ملكه ليحذفها) */
    public function hasUploadedAvatar(): bool
    {
        return $this->image()->exists();
    }

    /**
     * يرفع صورة جديدة ويحذف ملف السابقة.
     *
     * 🔴 updateOrCreate على العلاقة لا create: للحساب صورة واحدة، وcreate
     * في كل حفظ يكدّس صفّاً جديداً حتى تصير «الأولى بالترتيب» أيّها أرجعه
     * الاستعلام صدفةً.
     */
    public function syncAvatar(UploadedFile $file): Media
    {
        $previous = $this->image?->path;

        // بادئة storage/ ليحلّها asset() عبر الرابط الرمزي — نفس اصطلاح
        // الشرائح والأخبار.
        $path = $file->store('avatars', 'public');

        $media = $this->media()->updateOrCreate([], [
            'path' => 'storage/'.$path,
            'type' => 'image',
            'alt_text' => $this->displayName(),
            'display_order' => 1,
        ]);

        self::deleteAvatarFile($previous);

        // العلاقة المحمّلة صارت قديمة بعد الكتابة، وأي قراءة لـavatar_url
        // بعدها في نفس الطلب كانت سترجع المسار السابق.
        $this->unsetRelation('image');

        return $media;
    }

    /** يحذف الصورة المرفوعة ويعيد الحساب إلى البديل. */
    public function clearAvatar(): void
    {
        $media = $this->image;

        if (! $media) {
            return;
        }

        self::deleteAvatarFile($media->path);

        // forceDelete لا delete: صفّ media محذوف ناعماً يبقى يشغل العلاقة
        // فيمنع الرجوع إلى البديل، والصورة ليست عنصراً في سلّة المحذوفات.
        $media->forceDelete();

        $this->unsetRelation('image');
    }

    /**
     * ⚠️ الحذف مشروط بالبادئة: الأصول المبذورة تعيش تحت public/images
     * كملفات مستودع، وحذف أحدها يمسحه من شجرة العمل في كل بيئة ولا يعود.
     */
    private static function deleteAvatarFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $path));
        }
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
