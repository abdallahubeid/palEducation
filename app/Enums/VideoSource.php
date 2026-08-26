<?php

namespace App\Enums;

/**
 * مصدر استضافة الفيديو.
 *
 * ⏳ م-5 حُسم في **جزئه الأول فقط**: اكتمال المشاهدة تحقّق خادمي موثوق.
 * أما **مزوّد الاستضافة** فلم يُحسم بعد. هذا الحقل يجعل القرار قابلاً
 * للتأجيل بلا كلفة: كل محاضرة تحمل مصدرها، فيمكن ترحيل المكتبة تدريجياً
 * بدل أن يفرض القرار هجرة واحدة كبرى على كل الصفوف.
 *
 * 🔴 video_reference يعني شيئاً مختلفاً لكل قيمة هنا:
 *   LocalPrivate → مسار على القرص الخاص (لا public أبداً)
 *   Bunny/Vimeo  → معرّف الأصل عند المزوّد
 *   External     → رابط كامل
 */
enum VideoSource: string
{
    case LocalPrivate = 'local_private';
    case Bunny = 'bunny';
    case Vimeo = 'vimeo';
    case External = 'external';

    /** هل يُخدَم هذا المصدر من قرصنا الخاص عبر controller يفحص الصلاحية؟ */
    public function isSelfHosted(): bool
    {
        return $this === self::LocalPrivate;
    }
}
