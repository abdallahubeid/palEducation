{{--
    🔴 A plain-Blade email, not markdown mail.

    Laravel's markdown mailer ships an LTR template with English-first
    typography. This one sets dir="rtl" on <html> and inlines its own styles,
    because email clients strip <style> blocks and have no CSS cascade worth
    relying on — every rule has to live on the element.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>وصلتنا رسالتك</title>
</head>
<body style="margin:0;padding:24px;background-color:#f5f8f7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;">
        <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:32px;">

                <h1 style="margin:0;font-size:20px;line-height:1.5;color:#1c1c1e;">
                    وصلتنا رسالتك، شكراً لك
                </h1>

                {{-- line-height 1.9: Arabic descenders collide below ~1.75,
                     and email clients will not inherit it from anywhere. --}}
                <p style="margin:16px 0 0;font-size:15px;line-height:1.9;color:#4a5261;">
                    مرحباً {{ $contactMessage->name }}، استلمنا رسالتك وسنردّ عليك عادةً
                    خلال يوم عمل واحد على هذا البريد.
                </p>

                <div style="margin-top:24px;padding:16px;background-color:#f5f8f7;border-radius:12px;">
                    <p style="margin:0;font-size:13px;color:#666e7c;">موضوع الرسالة</p>
                    <p style="margin:6px 0 0;font-size:15px;font-weight:600;color:#1c1c1e;">
                        {{ $contactMessage->subject_type->label() }}
                    </p>

                    <p style="margin:16px 0 0;font-size:13px;color:#666e7c;">نصّ رسالتك</p>
                    {{-- nl2br so the sender's own paragraph breaks survive;
                         the value is escaped first by {{ }}. --}}
                    <p style="margin:6px 0 0;font-size:15px;line-height:1.9;color:#4a5261;">
                        {!! nl2br(e($contactMessage->message)) !!}
                    </p>
                </div>

                <p style="margin:24px 0 0;font-size:13px;line-height:1.8;color:#666e7c;">
                    لا حاجة للردّ على هذه الرسالة — إنها تأكيد استلام آلي.
                </p>

                <p style="margin:24px 0 0;font-size:14px;color:#4a5261;">
                    فريق {{ config('app.name') }}
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
