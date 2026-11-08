<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * ملف الطالب الشخصي.
 *
 * 🔴 كل إجراء على $request->user() حصراً — لا مُعرِّف حساب في أي مسار.
 *
 * ⚠️ الاسم على سجلّ المجال (students.full_name) لا على users. التحديث يكتب
 * في جدولين، ونسيان سجلّ المجال يجعل إعادة التسمية لا تفعل شيئاً بصمت.
 *
 * 🔴 الفرع **للقراءة فقط** هنا. الفرع الفلتر الجذري لكل المحتوى وموثَّق
 * كـ«شبه دائم»؛ تغييره يغيّر كلّ ما يراه الطالب، فمكانه الإدارة لا نقرة
 * ذاتية.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('pages.student.profile', [
            // image محمّلة: avatar_url يقرأ العلاقة، وبدونها يستعلم في كل قراءة.
            'user' => $request->user()->load(['userable.branch', 'image']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            /*
             * ⚠️ بيانات تواصل لا هوية. الدخول بالبريد وجوجل، ولا شيء يصادق
             * على هذه الأرقام — نُخزّن ما كتبه الطالب.
             */
            'phone' => ['nullable', 'string', 'max:32'],
            'guardian_phone' => ['nullable', 'string', 'max:32'],
            'school_name' => ['nullable', 'string', 'max:160'],
            'governorate' => ['nullable', 'string', 'max:80'],
            'locale' => ['required', 'in:ar,en'],
        ]);

        $user->userable->update([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'] ?? null,
            'guardian_phone' => $validated['guardian_phone'] ?? null,
            'school_name' => $validated['school_name'] ?? null,
            'governorate' => $validated['governorate'] ?? null,
        ]);

        $user->update([
            'email' => $validated['email'],
            'locale' => $validated['locale'],
        ]);

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('student.profile');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // 🔴 current_password هو ما يمنع جلسة مستعارة من أن تصير استيلاءً دائماً.
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        flash()->success('تم تحديث كلمة المرور بنجاح.');

        return redirect()->route('student.profile');
    }

    /**
     * يستقبل الصورة المقصوصة.
     *
     * 🔴 رفع ملف عادي لا Base64: المحرّر يضع الـBlob المقصوص في حقل ملف
     * حقيقي، فيتحقّق الخادم منه بقاعدة mimes العادية. نفس خطّ syncAvatar
     * الموحَّد للأدمن والمعلّم — لا نسخة تنحرف.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], [
            'avatar.mimes' => 'الصيغ المقبولة: JPG أو PNG أو WebP.',
            'avatar.max' => 'حجم الصورة يتجاوز 4 ميغابايت.',
        ]);

        $request->user()->syncAvatar($request->file('avatar'));

        flash()->success('تم تحديث صورة الحساب.');

        return redirect()->route('student.profile');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $request->user()->clearAvatar();

        flash()->success('تم حذف صورة الحساب.');

        return redirect()->route('student.profile');
    }
}
