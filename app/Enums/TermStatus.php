<?php

namespace App\Enums;

/**
 * دورة حياة الفصل الدراسي — سطح «فتح/إغلاق الفصل» في الشاشة 44.
 *
 * 🔴 لا يُخلط بينها وبين academic_terms.is_current:
 *   status     → هل الفصل مفتوح للطلبات الجديدة؟ (دورة حياة)
 *   is_current → أي فصل تتبنّاه الاشتراكات الجديدة افتراضياً؟ (اختيار واحد)
 *
 * فصلان قد يكونان Active معاً (الطالب ما زال يفتح محتوى فصل سابق دفع
 * ثمنه) بينما is_current يخصّ صفّاً واحداً فقط.
 */
enum TermStatus: string
{
    /** أُنشئ ولم يبدأ بعد — لا يقبل طلبات. */
    case Upcoming = 'upcoming';

    /** مفتوح: يقبل الطلبات ويُخدَم محتواه. */
    case Active = 'active';

    /** مغلق: لا طلبات جديدة. المحتوى يبقى (سجلّ تاريخي لا إعداد يُرمى). */
    case Archived = 'archived';

    /** هل يقبل هذا الفصل طلبات واشتراكات جديدة؟ */
    public function acceptsOrders(): bool
    {
        return $this === self::Active;
    }
}
