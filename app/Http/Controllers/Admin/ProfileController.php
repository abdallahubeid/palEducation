<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', [
            // ⚠️ image محمّلة أيضاً: avatar_url يقرأ العلاقة، وبدونها
            // يستعلم المُلحِق مرة لكل قراءة في القالب.
            'user' => auth()->user()->load(['userable', 'image']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'locale' => ['required', 'in:ar,en'],
        ]);

        // الاسم يعيش على سجلّ المجال (admins) لا على users — لا عمود name هناك.
        $user->userable->update(['full_name' => $validated['full_name']]);

        $user->update([
            'email' => $validated['email'],
            'locale' => $validated['locale'],
        ]);

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.profile.edit');
    }

    /**
     * يستقبل الصورة المقصوصة من محرّر القصّ.
     *
     * 🔴 رفع ملف عادي، لا Base64. المحرّر يضع الـBlob المقصوص داخل حقل
     * <input type="file"> حقيقي عبر DataTransfer، فيصل الخادم ملفاً يخضع
     * لقاعدة mimes الاعتيادية. سلسلة Base64 كانت ستُفحص كنصّ — أي أن نوع
     * الملف يصير ادّعاء العميل لا فحص الخادم.
     *
     * ⚠️ والفحص هنا لا يثق بالعميل أصلاً: القصّ راحة للمستخدم، والحدود
     * (النوع والحجم) تُفرض على الخادم لأن الطلب قد يأتي بلا مرور بالمحرّر.
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

        return redirect()->route('admin.profile.edit');
    }

    /** يحذف الصورة المرفوعة ويعيد الحساب إلى البديل. */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $request->user()->clearAvatar();

        flash()->success('تم حذف صورة الحساب.');

        return redirect()->route('admin.profile.edit');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.profile.edit');
    }
}
