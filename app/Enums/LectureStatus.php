<?php

namespace App\Enums;

/**
 * حالة نشر المحاضرة.
 *
 * منفصلة عن published_at عمداً: محاضرة أُخفيت تحتفظ بتاريخ نشرها الأصلي،
 * فإعادة إظهارها لا تُقدّمها في الترتيب الزمني كأنها جديدة.
 */
enum LectureStatus: string
{
    /** مسودّة — يراها صاحبها فقط. تُنشأ بلا فيديو وبلا كويز. */
    case Draft = 'draft';

    /** منشورة — مرئية للطلبة المؤهّلين. */
    case Published = 'published';

    /** مخفيّة بعد نشر — سُحبت مؤقتاً ولم تُحذف. */
    case Hidden = 'hidden';

    /** هل تظهر للطالب؟ (قبل تطبيق الفرع والاشتراك — هذه بوّابة أولى لا أخيرة.) */
    public function isVisibleToStudents(): bool
    {
        return $this === self::Published;
    }
}
