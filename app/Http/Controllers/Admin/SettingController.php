<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * الإعدادات — صفحة واحدة بتبويبات.
 *
 * 🔴 لا شاشة نظرة عامة ولا شاشة لكل قسم: صفحة واحدة تعرض كل الألواح،
 * والتبويب يبدّل بينها على العميل. كل لوح يحفظ عبر مساره الخاص ويعود
 * إلى نفس التبويب.
 */
class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'groups' => Setting::GROUPS,
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

        return $this->backToTab('navbar');
    }

    /**
     * يعيد إلى الصفحة **وإلى التبويب نفسه**.
     *
     * ⚠️ المرساة لا تصل الخادم أبداً، فالتبويب يُمرَّر كاستعلام `tab`
     * ويلتقطه العرض. بدونه يعود المستخدم دائماً للتبويب الأول بعد كل حفظ.
     */
    private function backToTab(string $tab): RedirectResponse
    {
        return redirect()->route('admin.settings.index', ['tab' => $tab]);
    }
}
