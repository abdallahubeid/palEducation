<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Feature cards — the "what sets us apart" grid on the home page.
 *
 * 🔴 A dedicated module, not a panel inside settings. A feature is a domain
 * record with an order, a soft delete and four permissions; only the section
 * HEADER (badge and title) stays in the settings table. This is the same
 * split the branches module follows.
 */
class FeatureController extends Controller
{
    public function index(): View
    {
        return view('admin.features.index', [
            'features' => Feature::query()->ordered()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.features.create', [
            'feature' => new Feature([
                'display_order' => (int) Feature::query()->max('display_order') + 1,
                'is_active' => true,
                'icon' => 'clipboard',
                'accent_token' => 'accent',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Feature::query()->create($this->validated($request));

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.features.index');
    }

    public function edit(Feature $feature): View
    {
        return view('admin.features.edit', ['feature' => $feature]);
    }

    public function update(Request $request, Feature $feature): RedirectResponse
    {
        $feature->update($this->validated($request));

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.features.index');
    }

    public function toggle(Feature $feature): RedirectResponse
    {
        $feature->update(['is_active' => ! $feature->is_active]);

        flash()->info('تم تغيير الحالة بنجاح.');

        return back();
    }

    public function destroy(Feature $feature): RedirectResponse
    {
        $feature->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        // Undo is a real PATCH form with CSRF rendered by the shell, never a
        // link inside the notice: a restore mutates server state.
        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'features', 'id' => $feature->id]),
            'label' => 'تراجع',
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            /*
             * 🔴 Closed lists, not free text. Both columns hold NAMES the
             * template resolves at render time — an icon from the x-icon set
             * and a tone from the card's palette map. An unknown value draws
             * a card with no icon or no colour, silently.
             */
            'icon' => ['required', 'string', 'in:'.implode(',', self::ICONS)],
            'accent_token' => ['required', 'string', 'in:'.implode(',', self::TONES)],
            'btn_text' => ['nullable', 'required_with:btn_link', 'string', 'max:80'],
            'btn_link' => ['nullable', 'required_with:btn_text', 'string', 'max:500', self::LINK_RULE],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'btn_text.required_with' => 'النصّ والرابط يُملآن معاً — رابط بلا نصّ يُنتج زرّاً غير مرئي.',
            'btn_link.required_with' => 'النصّ والرابط يُملآن معاً — نصّ بلا رابط يُنتج زرّاً لا يعمل.',
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'],
            'accent_token' => $validated['accent_token'],
            'btn_text' => $validated['btn_text'] ?? null,
            'btn_link' => $validated['btn_link'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * 🔴 The link lands in an href on a public page, so the constraint is
     * the SCHEME: javascript: or data: there executes in every visitor's
     * browser. Not a `url` rule — /register and #pricing are valid targets
     * that `url` rejects.
     */
    private const LINK_RULE = 'regex:/^(#|\/|https?:\/\/)/';

    /**
     * Icons that exist in the x-icon set and suit a feature card.
     *
     * @var array<int, string>
     */
    public const ICONS = ['clipboard', 'folder', 'users', 'compass', 'shield', 'sparkle', 'chart', 'play', 'book'];

    /**
     * Tones the card knows how to paint.
     *
     * ⚠️ Must stay in step with the tone map in the home template: a value
     * absent there renders the chip with no colour at all.
     *
     * @var array<int, string>
     */
    public const TONES = ['accent', 'tag', 'amber', 'warn'];
}
