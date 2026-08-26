<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * نظرة عامة على الإعدادات — بوّابة الأقسام.
     *
     * موجودة لأن «الإعدادات» صارت أكثر من شاشة واحدة: الصفحة تعرض الأقسام
     * وحالة كل قسم، بدل أن يقفز الرابط الجانبي مباشرةً إلى قسم بعينه.
     */
    public function index(): View
    {
        $settings = Setting::map();

        return view('admin.settings.index', [
            'navbarFilled' => collect(Setting::NAVBAR_SOCIAL_KEYS)
                ->filter(fn (string $key) => filled($settings[$key] ?? null))
                ->count(),
            'navbarTotal' => count(Setting::NAVBAR_SOCIAL_KEYS),
        ]);
    }

    public function navbar(): View
    {
        return view('admin.settings.navbar', [
            'settings' => Setting::map(),
        ]);
    }

    public function updateNavbar(Request $request): RedirectResponse
    {
        // starts_with:https مقصود لا url وحدها: رابط غير مشفّر في شريط علوي
        // يظهر لكل زائر يُنتج تحذير متصفّح على صفحة سليمة.
        $validated = $request->validate([
            'social_link1' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
            'social_link2' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
            'social_link3' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
        ], [
            'starts_with' => __('admin.setting_https_only'),
        ]);

        foreach (Setting::NAVBAR_SOCIAL_KEYS as $index => $key) {
            Setting::put($key, $validated['social_link'.($index + 1)] ?? null);
        }

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.settings.navbar');
    }
}
