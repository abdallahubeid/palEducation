<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /*
         | 🔴 التحقّق من الاعتماد ثم من حالة الحساب **قبل** إنشاء أي جلسة.
         | المحاولة أولاً ثم الخروج عند اكتشاف الإيقاف كانت تُنشئ جلسة ثم
         | تُبطلها، فيضيع خطأ التحقّق المُومَض معها ويُرمى المستخدم إلى
         | الصفحة الرئيسية بلا أي رسالة تشرح ما جرى. Auth::validate تفحص
         | كلمة المرور بلا تسجيل دخول، فلا توجد جلسة تحتاج إبطالاً أصلاً.
         */
        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // رسالة منفصلة عن «بيانات خاطئة» عمداً: المستخدم يحتاج أن يعرف أن
        // كلمة مروره سليمة وأن المشكلة إدارية لا خطأ منه.
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => __('auth.blocked'),
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        flash()->success(__('auth.login_success', ['name' => $user->displayName()]));

        return redirect()->intended($user->homeRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
