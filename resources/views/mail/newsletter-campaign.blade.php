{{-- Plain-Blade RTL email — see contact-confirmation for why the markup is
     table-based inline CSS rather than components. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:24px;background-color:#f5f8f7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;">
        <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:32px;">

                <p style="margin:0;font-size:13px;color:#666e7c;">{{ __('common.brand') }}</p>

                <h1 style="margin:8px 0 0;font-size:20px;line-height:1.5;color:#1c1c1e;">
                    {{ $subjectLine }}
                </h1>

                {{-- 🔴 nl2br(e()) — escaped FIRST, then line breaks added.
                     The body is admin-authored, but an admin pasting from a
                     web page brings markup with them, and this is mail: a
                     stray tag breaks the layout in every client at once. --}}
                <div style="margin-top:20px;font-size:15px;line-height:1.9;color:#4a5261;">
                    {!! nl2br(e($body)) !!}
                </div>

            </td>
        </tr>

        <tr>
            <td style="padding:20px 32px;text-align:center;">
                {{-- 🔴 Every campaign carries this. A newsletter with no way
                     out is not a newsletter, it is what gets a domain
                     blocklisted — and the link is per recipient. --}}
                <p style="margin:0;font-size:12px;line-height:1.8;color:#666e7c;">
                    وصلتك هذه الرسالة لأنك مشترك في النشرة البريدية على
                    {{ config('app.name') }}.
                    <br>
                    <a href="{{ $unsubscribeUrl }}" style="color:#525fe1;text-decoration:underline;">إلغاء الاشتراك</a>
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
