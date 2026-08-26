<?php

namespace App\Support;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Model;

/**
 * سجلّ الكيانات التي تظهر في سلّة المحذوفات المركزية.
 *
 * 🔴 مصدر واحد: الفلاتر في الواجهة، والتحقّق من رمز العنصر، وحلقة الجمع
 * كلها تقرأ من هنا. إضافة كيان جديد للسلّة = صفّ واحد في هذه المصفوفة،
 * لا تعديل في الكنترولر ولا في العرض.
 *
 * ⚠️ الكيان يدخل هنا فقط إن كان يستعمل SoftDeletes فعلاً؛ وإلّا رمى
 * onlyTrashed استثناءً وقت الطلب لا وقت البناء.
 */
final class TrashableResources
{
    /**
     * @return array<string, array{label: string, model: class-string<Model>, title: callable, subtitle: callable|null, permission: string}>
     */
    public static function all(): array
    {
        return [
            'sliders' => [
                'label' => 'الهيرو',
                'model' => Slider::class,
                'title' => fn (Slider $slider): string => $slider->title,
                'subtitle' => fn (Slider $slider): ?string => $slider->badge_text,
                'permission' => 'delete-sliders',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function has(?string $type): bool
    {
        return $type !== null && $type !== '' && array_key_exists($type, self::all());
    }

    /** يُرجع النوع إن كان معروفاً، وnull لأي شيء آخر — لا يرمي. */
    public static function sanitize(?string $type): ?string
    {
        return self::has($type) ? $type : null;
    }

    /** @return class-string<Model> */
    public static function modelFor(string $type): string
    {
        return self::all()[$type]['model'];
    }

    public static function labelFor(string $type): string
    {
        return self::all()[$type]['label'] ?? $type;
    }

    public static function permissionFor(string $type): string
    {
        return self::all()[$type]['permission'];
    }
}
