<?php

namespace App\Enums;

/**
 * حالة معالجة الفيديو بعد الرفع.
 *
 * موجودة لأن الرفع غير متزامن: المعلم يرفع ويغادر، والمعالجة تكتمل بعده.
 * بلا هذا العمود تكون المحاضرة إمّا «بلا فيديو» أو «جاهزة» — ولا تمييز
 * بين فشل المعالجة وبين كونها ما زالت جارية.
 */
enum VideoStatus: string
{
    /** لم يُرفع شيء بعد. */
    case Pending = 'pending';

    /** رُفع والمعالجة جارية — لا يُقدَّم للطالب. */
    case Processing = 'processing';

    /** جاهز للتشغيل. */
    case Ready = 'ready';

    /** فشلت المعالجة — يحتاج تدخّل المعلم. */
    case Failed = 'failed';

    public function isPlayable(): bool
    {
        return $this === self::Ready;
    }
}
