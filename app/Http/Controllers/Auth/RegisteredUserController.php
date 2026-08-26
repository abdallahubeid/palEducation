<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * التسجيل الذاتي — للطلاب حصراً.
 * حسابات المعلمين والأدمن يُنشئها الأدمن من لوحته (الموجة 8).
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.register', [
            'branches' => Branch::where('is_active', true)
                ->orderBy('display_order')
                ->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],

            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'school' => ['nullable', 'string', 'max:160'],
            'governorate' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        // سجلّ المجال أولاً ثم حساب الدخول — لأن userable_id إجباري.
        // معاملة واحدة: لا يوجد نصف تسجيل.
        $user = DB::transaction(function () use ($validated) {
            $student = Student::create([
                'full_name' => $validated['name'],
                'branch_id' => $validated['branch_id'],
                'school_name' => $validated['school'] ?? null,
                'governorate' => $validated['governorate'] ?? null,
                'country_code' => $validated['country'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);

            $user = $student->user()->create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'locale' => app()->getLocale(),
            ]);

            // الدور يُسنَد داخل نفس المعاملة لا بعدها: حساب يُنشأ بلا دور
            // لا يشتكي — يعمل ثم يسقط عند أول فحص صلاحية. (الثابت 4)
            return $user->syncRoleToShape();
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
