<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The teacher's own profile.
 *
 * 🔴 Every action works on $request->user() and nothing else. No route
 * carries an account id, so there is nothing to tamper with to reach a
 * colleague's profile — the same structural guard the admin profile uses.
 *
 * ⚠️ The name lives on the DOMAIN row (teachers.full_name), not on users —
 * there is no `name` column there. Updating a profile therefore writes to two
 * tables, and forgetting the domain row is how a rename silently does nothing.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('teacher.profile.edit', [
            // image loaded too: avatar_url reads the relation, and without it
            // the accessor queries on every read in the template.
            'user' => $request->user()->load(['userable', 'image']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            /*
             * ⚠️ Contact data, not identity. Nothing authenticates against it,
             * so it is nullable and loosely formatted on purpose — Palestinian
             * numbers get written half a dozen ways and rejecting them is
             * worse than storing what the teacher typed.
             */
            'phone' => ['nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'locale' => ['required', 'in:ar,en'],
        ]);

        $user->userable->update([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        $user->update([
            'email' => $validated['email'],
            'locale' => $validated['locale'],
        ]);

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('teacher.profile.edit');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // 🔴 current_password is what stops a borrowed, unlocked session
            // from becoming a permanent account takeover.
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        flash()->success('تم تحديث كلمة المرور بنجاح.');

        return redirect()->route('teacher.profile.edit');
    }

    /**
     * Receives the cropped avatar.
     *
     * 🔴 An ordinary file upload, not Base64. The editor puts the cropped Blob
     * into a real file input via DataTransfer, so the server validates it with
     * the normal mimes rule — a Base64 string would make the file type the
     * client's claim rather than the server's check.
     *
     * ⚠️ The limits are enforced here regardless: a request can arrive without
     * ever passing through the cropper.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], [
            'avatar.mimes' => 'الصيغ المقبولة: JPG أو PNG أو WebP.',
            'avatar.max' => 'حجم الصورة يتجاوز 4 ميغابايت.',
        ]);

        // The same helper the admin profile uses — one avatar pipeline for
        // every account shape, so the two cannot drift.
        $request->user()->syncAvatar($request->file('avatar'));

        flash()->success('تم تحديث صورة الحساب.');

        return redirect()->route('teacher.profile.edit');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $request->user()->clearAvatar();

        flash()->success('تم حذف صورة الحساب.');

        return redirect()->route('teacher.profile.edit');
    }
}
