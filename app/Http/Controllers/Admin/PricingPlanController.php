<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pricing tier cards.
 *
 * 🔴 A dedicated module, like branches and features. Only the section HEADER
 * lives in settings; a plan is a domain record with a price, a bullet list,
 * an order, a soft delete and four permissions.
 *
 * 🔴 This is MARKETING copy, never the billing model. What a student
 * actually bought belongs to `subscriptions` in phase 8. Editing a price
 * here must not rewrite what somebody already paid, and withdrawing a plan
 * from the page must not erase it from past receipts.
 */
class PricingPlanController extends Controller
{
    public function index(): View
    {
        return view('admin.pricing.index', [
            'plans' => PricingPlan::query()->ordered()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.pricing.create', [
            'plan' => new PricingPlan([
                'display_order' => (int) PricingPlan::query()->max('display_order') + 1,
                'is_active' => true,
                'price' => 0,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PricingPlan::query()->create($this->validated($request));

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.pricing.index');
    }

    public function edit(PricingPlan $pricing): View
    {
        return view('admin.pricing.edit', ['plan' => $pricing]);
    }

    public function update(Request $request, PricingPlan $pricing): RedirectResponse
    {
        $pricing->update($this->validated($request));

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.pricing.index');
    }

    public function toggle(PricingPlan $pricing): RedirectResponse
    {
        $pricing->update(['is_active' => ! $pricing->is_active]);

        flash()->info('تم تغيير الحالة بنجاح.');

        return back();
    }

    public function destroy(PricingPlan $pricing): RedirectResponse
    {
        $pricing->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        // Undo is a real PATCH form with CSRF in the shell, never a link
        // inside the notice: a restore mutates server state.
        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'pricings', 'id' => $pricing->id]),
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
            'name' => ['required', 'string', 'max:120'],
            'badge' => ['nullable', 'string', 'max:200'],
            /*
             * ⚠️ integer, not numeric: `numeric` accepts "120.50" and the
             * column is an unsigned int, so the fraction would be truncated
             * on write with no error. Whole shekels only — see the migration.
             */
            'price' => ['required', 'integer', 'min:0', 'max:999999'],
            'period' => ['nullable', 'string', 'max:60'],
            'features' => ['nullable', 'string', 'max:2000'],
            'highlight_badge' => ['nullable', 'string', 'max:80'],
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
            'name' => $validated['name'],
            'badge' => $validated['badge'] ?? null,
            'price' => $validated['price'],
            'period' => $validated['period'] ?? null,
            'features' => self::parseFeatures($validated['features'] ?? null),
            'highlight_badge' => $validated['highlight_badge'] ?? null,
            'btn_text' => $validated['btn_text'] ?? null,
            'btn_link' => $validated['btn_link'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * One bullet per line → a JSON array.
     *
     * 🔴 A textarea rather than repeating inputs: the list has no identity,
     * order-independence or behaviour of its own, and repeating rows would
     * need add/remove JavaScript to edit three sentences. Pasting a list
     * from a document just works.
     *
     * ⚠️ Blank lines and stray whitespace are stripped. A trailing newline
     * is the normal way to leave a textarea, and it would otherwise store an
     * empty bullet that renders as a lone tick with no text.
     *
     * @return array<int, string>
     */
    public static function parseFeatures(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /** The inverse, for the edit form. */
    public static function featuresToText(?array $features): string
    {
        return implode(PHP_EOL, $features ?? []);
    }

    /**
     * 🔴 The link lands in an href on a public page, so the constraint is
     * the SCHEME: javascript: or data: there executes in every visitor's
     * browser. Not a `url` rule — /register and #compare are valid targets
     * that `url` rejects.
     */
    private const LINK_RULE = 'regex:/^(#|\/|https?:\/\/)/';
}
