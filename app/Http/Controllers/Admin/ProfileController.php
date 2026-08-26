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
            'user' => auth()->user()->load('userable'),
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
