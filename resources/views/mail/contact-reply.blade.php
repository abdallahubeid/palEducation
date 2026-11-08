{{-- Plain-Blade RTL email — see contact-confirmation for why. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ردّ على رسالتك</title>
</head>
<body style="margin:0;padding:24px;background-color:#f5f8f7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;">
        <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:32px;">

                <h1 style="margin:0;font-size:20px;line-height:1.5;color:#1c1c1e;">
                    ردّاً على رسالتك
                </h1>

                <p style="margin:16px 0 0;font-size:15px;line-height:1.9;color:#4a5261;">
                    مرحباً {{ $contactMessage->name }},
                </p>

                {{-- The reply, given the visual weight — it is why this email
                     exists. Accent border on the inline START edge so it
                     mirrors correctly in RTL. --}}
                <div style="margin-top:16px;padding:16px 20px;background-color:#f0f1fd;border-radius:12px;border-inline-start:3px solid #525fe1;">
                    <p style="margin:0;font-size:15px;line-height:1.9;color:#1c1c1e;">
                        {!! nl2br(e($contactMessage->reply_message)) !!}
                    </p>
                </div>

                {{-- Their original message, quoted underneath for context:
                     a reply arriving days later is unreadable without it. --}}
                <div style="margin-top:24px;padding:16px;background-color:#f5f8f7;border-radius:12px;">
                    <p style="margin:0;font-size:13px;color:#666e7c;">رسالتك الأصلية</p>
                    <p style="margin:6px 0 0;font-size:14px;line-height:1.9;color:#666e7c;">
                        {!! nl2br(e($contactMessage->message)) !!}
                    </p>
                </div>

                <p style="margin:24px 0 0;font-size:14px;color:#4a5261;">
                    فريق {{ config('app.name') }}
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
