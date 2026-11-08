<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Branches — a first-class entity with its own screens.
 *
 * 🔴 Not managed from the settings page. A branch is the platform's root
 * filter: it owns subjects, which own lectures and library files, and every
 * student belongs to exactly one. That is a domain record with a slug, an
 * order, a soft delete and four permissions — not a key/value pair. Only the
 * SECTION HEADER above the cards stays in settings.
 */
class BranchController extends Controller
{
    public function index(): View
    {
        return view('admin.branches.index', [
            // withCardCounts() derives the subject and teacher figures the
            // public card shows, so the admin sees the same numbers visitors
            // do rather than a column somebody typed once.
            'branches' => Branch::query()
                ->withCardCounts()
                ->ordered()
                ->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.branches.create', [
            'branch' => new Branch([
                'display_order' => (int) Branch::query()->max('display_order') + 1,
                'is_active' => true,
                'icon' => 'beaker',
                'accent_token' => 'accent',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Branch::query()->create($validated);

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.branches.index');
    }

    public function edit(Branch $branch): View
    {
        return view('admin.branches.edit', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validated($request, $branch));

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.branches.index');
    }

    public function toggle(Branch $branch): RedirectResponse
    {
        $branch->update(['is_active' => ! $branch->is_active]);

        flash()->info('تم تغيير الحالة بنجاح.');

        return back();
    }

    /**
     * ⚠️ Soft delete, and it cascades in meaning if not in SQL: every
     * subject, lecture and student under this branch keeps pointing at a
     * row that no longer appears. A branch still holding students is
     * refused rather than silently orphaning them.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->students()->exists()) {
            flash()->error('لا يمكن حذف فرع يضمّ طلبة. انقلهم أولاً.');

            return back();
        }

        if ($branch->subjects()->exists()) {
            flash()->error('لا يمكن حذف فرع يضمّ مواد. احذف موادّه أولاً.');

            return back();
        }

        // deleted_by is an ACTOR reference (users), unlike the domain
        // references elsewhere on this table — invariant 21. Assigned
        // directly rather than mass-assigned: it is not fillable, and must
        // not be settable from a request.
        $branch->deleted_by = auth()->id();
        $branch->save();

        $branch->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Branch $branch = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable', 'string', 'max:100', 'alpha_dash',
                // Ignoring the current row on update; the slug is the route
                // key, so a duplicate would make one branch unreachable.
                'unique:branches,slug'.($branch ? ','.$branch->id : ''),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            /*
             * 🔴 in: rules, not free text. Both columns hold NAMES resolved
             * at render time — an icon from the x-icon set, a tone from the
             * card's palette map. An unknown value renders a card with no
             * icon or no colour, silently, with no error anywhere.
             */
            'icon' => ['required', 'string', 'in:'.implode(',', self::ICONS)],
            'accent_token' => ['required', 'string', 'in:'.implode(',', self::TONES)],
            'btn_text' => ['nullable', 'required_with:btn_link', 'string', 'max:80'],
            'btn_link' => ['nullable', 'required_with:btn_text', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'btn_text.required_with' => 'النصّ والرابط يُملآن معاً — رابط بلا نصّ يُنتج زرّاً غير مرئي.',
            'btn_link.required_with' => 'النصّ والرابط يُملآن معاً — نصّ بلا رابط يُنتج زرّاً لا يعمل.',
        ]);

        $slug = filled($validated['slug'] ?? null)
            ? $validated['slug']
            : $this->deriveSlug($validated['name'], $branch);

        return [
            'name' => $validated['name'],
            'slug' => $slug,
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
     * Builds a slug for a branch whose slug field was left blank.
     *
     * Str::slug() transliterates Arabic (الفرع التجاري → alfraa-altgary),
     * so it usually produces something usable; the numeric fallback is for
     * the names it reduces to an empty string.
     *
     * ⚠️ Uniqueness is enforced here as well as in the rules. The `unique`
     * rule validated the SUBMITTED value — which was empty — so a derived
     * slug can still collide with an existing row and would surface as a
     * database error rather than a field message.
     */
    private function deriveSlug(string $name, ?Branch $branch): string
    {
        $base = Str::slug($name) ?: 'branch';
        $slug = $base;
        $suffix = 2;

        while (
            Branch::withTrashed()
                ->where('slug', $slug)
                ->when($branch, fn ($query) => $query->whereKeyNot($branch->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Icons that exist in the x-icon set and read as a study track.
     *
     * @var array<int, string>
     */
    public const ICONS = ['beaker', 'book', 'briefcase', 'wrench', 'compass', 'chart', 'shield'];

    /**
     * Tones the branch card knows how to paint.
     *
     * ⚠️ Must stay in step with the $tones map in domain/branch-card:
     * a value absent there falls back to accent, so every branch would end
     * up the same colour without anyone noticing.
     *
     * @var array<int, string>
     */
    public const TONES = ['accent', 'tag', 'amber', 'warn'];
}
