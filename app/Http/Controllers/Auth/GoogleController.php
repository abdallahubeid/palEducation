<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * دخول وتسجيل عبر جوجل.
 *
 * ثلاث حالات يعالجها الرجوع (callback):
 *   1. حساب معروف بـ google_id  → دخول مباشر.
 *   2. بريد موجود بلا google_id → ربط الحسابين (نفس الشخص سجّل سابقاً بكلمة مرور).
 *   3. بريد جديد                → لا يُنشأ شيء بعد؛ يُوجَّه لاختيار الفرع.
 *
 * 🔴 لماذا لا يُنشأ الطالب فوراً في الحالة 3؟ لأن students.branch_id
 * إجباري، وجوجل لا تعرف الفرع. الخيار كان إمّا ترخية القيد — وهو ما
 * يُبطل الضمانة الوحيدة التي تجعل «طالب بلا فرع» مستحيلاً — أو تأجيل
 * الإنشاء حتى يُعرف الفرع. الثاني هو الصحيح: بيانات جوجل تنتظر في
 * الجلسة، ويُنشأ الصفّان معاً في معاملة واحدة بعد اختيار الفرع.
 */
class GoogleController extends Controller
{
    private const SESSION_KEY = 'google_registration';

    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            flash()->error(__('auth.google_failed'));

            return redirect()->route('auth.login');
        }

        // (1) حساب معروف بجوجل.
        $user = User::where('google_id', $googleUser->getId())->first();

        // (2) نفس البريد سجّل سابقاً بكلمة مرور — نربط لا نُنشئ نسخة ثانية.
        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'google_avatar_url' => $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }
        }

        // (3) جديد تماماً — احفظ الملف في الجلسة واطلب الفرع قبل الإنشاء.
        if (! $user) {
            $request->session()->put(self::SESSION_KEY, [
                'google_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: '',
                'avatar' => $googleUser->getAvatar(),
            ]);

            return redirect()->route('auth.google.branch');
        }

        return $this->loginExisting($user, $request);
    }

    /** شاشة اختيار الفرع — الخطوة الأخيرة في تسجيل جوجل. */
    public function showBranchSelection(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('auth.register');
        }

        return view('pages.auth.select-branch', [
            'branches' => Branch::where('is_active', true)
                ->orderBy('display_order')
                ->get(),
        ]);
    }

    /** إنشاء الطالب وحسابه معاً بعد معرفة الفرع. */
    public function completeRegistration(Request $request): RedirectResponse
    {
        $profile = $request->session()->get(self::SESSION_KEY);

        if (! $profile) {
            return redirect()->route('auth.register');
        }

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $user = DB::transaction(function () use ($profile, $validated) {
            $student = Student::create([
                'full_name' => $profile['name'],
                'branch_id' => $validated['branch_id'],
            ]);

            $user = $student->user()->create([
                'email' => $profile['email'],
                'password' => null,
                'google_id' => $profile['google_id'],
                'google_avatar_url' => $profile['avatar'],
                'email_verified_at' => now(), // جوجل تحقّقت من البريد أصلاً
                'locale' => app()->getLocale(),
            ]);

            // الدور داخل المعاملة نفسها — كما في التسجيل بكلمة مرور.
            return $user->syncRoleToShape();
        });

        $request->session()->forget(self::SESSION_KEY);

        return $this->loginExisting($user, $request);
    }

    private function loginExisting(User $user, Request $request): RedirectResponse
    {
        if (! $user->is_active) {
            flash()->error(__('auth.blocked'));

            return redirect()->route('auth.login');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        flash()->success(__('auth.login_success', ['name' => $user->displayName()]));

        return redirect()->intended($user->homeRoute());
    }
}
