<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * توثيق البريد الإلكتروني.
 * أثناء التطوير تُلتقط الرسائل بـ MailDev على http://localhost:1080
 */
class EmailVerificationController extends Controller
{
    /** شاشة «تحقّق من بريدك». */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended($request->user()->homeRoute())
            : view('pages.auth.verify-email');
    }

    /** الرابط الموقّع القادم من الرسالة. */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->homeRoute());
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        flash()->success(__('auth.verify_done'));

        return redirect()->intended($request->user()->homeRoute());
    }

    /** إعادة إرسال الرسالة. */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->homeRoute());
        }

        $request->user()->sendEmailVerificationNotification();

        flash()->info(__('auth.verify_resent'));

        return back();
    }
}
