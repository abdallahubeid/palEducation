<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * إعداد موقع — مفتاح/قيمة.
 *
 * 🔴 لا تُخزَّن هنا أي قيمة لا يجوز أن يراها زائر غير مسجّل. هذه النسخة
 * بلا عمود is_public (§3.32 تُعرّف الجدول الأغنى للمرحلة 10)، فلا شيء في
 * القاعدة يميّز العام من الخاص. إعدادات التقييم — عتبة 90% وسقف المحاولات
 * وسياسة الاحتساب — تنتظر ذلك العمود.
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * مفاتيح روابط التواصل في الشريط العلوي.
     *
     * ثابتة في مكان واحد: الكنترولر يتحقّق منها، والبذرة تُنشئها، والعرض
     * يقرؤها. ثلاث نسخ من نفس القائمة تتباعد؛ نسخة واحدة لا تفعل.
     */
    public const NAVBAR_SOCIAL_KEYS = [
        'navbar.social_link1',
        'navbar.social_link2',
        'navbar.social_link3',
    ];

    /**
     * تبويبات صفحة الإعدادات.
     *
     * 🔴 مصدر واحد: شريط التبويبات، واللوح المعروض، والمسار الذي يحفظه —
     * ثلاثتها تُبنى من هنا. إضافة تبويب = صفّ واحد + جزئية باسمه، بلا لمس
     * الشريط ولا الـJS.
     *
     * `partial` اسم جزئية تحت resources/views/admin/settings/tabs/.
     */
    public const GROUPS = [
        'navbar' => [
            'label' => 'الشريط العلوي',
            'icon' => 'link',
            'partial' => 'navbar',
            'route' => 'admin.settings.navbar.update',
        ],
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    /*
    |--------------------------------------------------------------------------
    | القراءة والكتابة
    |--------------------------------------------------------------------------
    */

    /** قيمة مفتاح، أو البديل إن لم يوجد. */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * كتابة مفتاح — إنشاءً أو تحديثاً.
     *
     * updateOrCreate لا create: المفتاح فريد، ومسار كتابة يفترض الغياب
     * يفشل في المرة الثانية.
     */
    public static function put(string $key, ?string $value): self
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * كل الإعدادات كخريطة مفتاح ⇒ قيمة.
     *
     * استعلام واحد بدل نداء get() لكل مفتاح — التذييل وحده يقرأ خمسة.
     */
    public static function map(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }
}
