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

    /**
     * ⚠️ حالة مستقلّة لا External: video_reference عند External رابط كامل،
     * وعند YouTube **معرّف الفيديو وحده** — فيُبنى رابط التضمين نظيفاً ولا
     * يُخزَّن رابط قد يحمل قوائم تشغيل أو طوابع زمنية أو معاملات تتبّع.
     *
     * 🔴 يوتيوب **استضافة عامة**. من يملك الرابط يشاهد، مهما كان الفيديو
     * «غير مدرَج». هذا يخالف قاعدة المشروع بأن المحتوى المدفوع لا يُخدَم
     * علناً — قرار مقصود لإطلاق سريع، وقابل للترحيل لاحقاً لأن كل محاضرة
     * تحمل مصدرها في هذا العمود.
     */
    case YouTube = 'youtube';

    /** التسمية المعروضة للمعلّم. */
    public function label(): string
    {
        return match ($this) {
            self::LocalPrivate => 'استضافة ذاتية',
            self::Bunny => 'Bunny',
            self::Vimeo => 'Vimeo',
            self::External => 'رابط خارجي',
            self::YouTube => 'يوتيوب',
        };
    }

    /** هل يُخدَم هذا المصدر من قرصنا الخاص عبر controller يفحص الصلاحية؟ */
    public function isSelfHosted(): bool
    {
        return $this === self::LocalPrivate;
    }
}
