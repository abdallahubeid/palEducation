<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Student reviews on the public home page.
 *
 * 🔴 A review is linked to a student account but does NOT belong to it. The
 * quote is what the student said; the name, branch and city are read live
 * from their record while it exists, and fall back to the stored snapshot
 * once it does not. That is why `student_id` is nullable and nullOnDelete:
 * deleting an account must not silently delete their words.
 */
class TestimonialController extends Controller
{
    /**
     * The three states an admin filters by.
     *
     * 🔴 Single source: the tab bar, the count badges and the query all read
     * this. `all` is deliberately the default — a moderation screen that
     * hides pending work by default is a moderation screen nobody uses.
     */
    private const STATUSES = ['all', 'active', 'pending'];

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : 'all';

        return view('admin.testimonials.index', [
            /*
             * 🔴 NO is_active filter on the base query, and that is the whole
             * point of this screen: student submissions land inactive, so
             * filtering to active here would hide exactly the rows an admin
             * came to act on — the queue would look permanently empty.
             *
             * Eager loaded because the row shows the reviewer's branch, two
             * hops away (testimonial → student → branch). Reaching that from
             * the template would be two queries per row.
             */
            'testimonials' => Testimonial::query()
                ->with('student.branch')
                ->when($status === 'active', fn ($query) => $query->where('is_active', true))
                ->when($status === 'pending', fn ($query) => $query->where('is_active', false))
                ->ordered()
                ->paginate(config('app.paginate_page'))
                // Keeps the filter across pages; without it page 2 silently
                // reverts to "all".
                ->withQueryString(),

            'activeStatus' => $status,

            // Counted unfiltered, so the tab badges show what is waiting even
            // while the admin is looking at another tab.
            'counts' => [
                'all' => Testimonial::query()->count(),
                'active' => Testimonial::query()->where('is_active', true)->count(),
                'pending' => Testimonial::query()->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.testimonials.create', [
            'testimonial' => new Testimonial([
                'display_order' => (int) Testimonial::query()->max('display_order') + 1,
                'rate' => Testimonial::MAX_RATE,
                'is_active' => true,
            ]),
            'students' => $this->studentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $testimonial = Testimonial::query()->create($this->validated($request));

        $this->syncCustomPhoto($request, $testimonial);

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.testimonials.index');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('admin.testimonials.edit', [
            'testimonial' => $testimonial,
            'students' => $this->studentOptions(),
        ]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request));

        $this->syncCustomPhoto($request, $testimonial);

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.testimonials.index');
    }

    /**
     * Approve a pending review, or take a published one back down.
     *
     * 🔴 This is the approval action for the whole moderation queue, so it
     * says which direction it went. "تم تغيير الحالة" leaves an admin
     * clicking through a queue unsure whether the last row was published or
     * pulled — the one thing they need to be certain of.
     */
    public function toggle(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $published = ! $testimonial->is_active;

        $testimonial->update(['is_active' => $published]);

        $published
            ? flash()->success('تمت الموافقة، والرأي ظاهر الآن في الصفحة الرئيسية.')
            : flash()->info('أُخفي الرأي من الصفحة الرئيسية.');

        /*
         * ⚠️ Returns to the TAB the admin was on, from a hidden field rather
         * than the referer. back() would usually work here, but it depends on
         * a header the browser may withhold — and approving from the pending
         * queue only to land on "all" loses your place in the list.
         */
        $status = $request->input('status');

        return in_array($status, self::STATUSES, true) && $status !== 'all'
            ? redirect()->route('admin.testimonials.index', ['status' => $status])
            : back();
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        // Undo is a real PATCH form with CSRF in the shell, never a link in
        // the notice: a restore mutates server state.
        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'testimonials', 'id' => $testimonial->id]),
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
            /*
             * ⚠️ Optional. A review can predate the accounts table, or come
             * from someone who has since left — and the snapshot name is
             * what the card falls back to. Requiring the link would make
             * those unrepresentable.
             */
            'student_id' => ['nullable', 'exists:students,id'],
            // Required only when no account backs the review, because then
            // it is the only name the card has.
            'name' => ['required_without:student_id', 'nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:160'],
            /*
             * 🔴 Bounded here, not just in the column. unsignedTinyInteger
             * accepts up to 255, and the database has no idea that seven
             * stars do not exist — a loop would happily print 255 of them.
             */
            'rate' => ['required', 'integer', Rule::in(range(Testimonial::MIN_RATE, Testimonial::MAX_RATE))],
            'description' => ['required', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],

            // Validated against the model constant, not a database enum:
            // the column is a plain string so this list can grow without a
            // schema migration.
            'avatar_source' => ['required', Rule::in(Testimonial::AVATAR_SOURCES)],

            /*
             * ⚠️ GD is not installed in this environment, so the `image`
             * rule — which reads image dimensions — cannot be relied on.
             * mimetypes checks the uploaded file's detected type instead,
             * which is the actual guarantee wanted here.
             */
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096'],
        ], [
            'name.required_without' => 'اكتب اسماً للرأي، أو اربطه بحساب طالب.',
            'photo.mimetypes' => 'الصورة يجب أن تكون JPG أو PNG أو WebP.',
        ]);

        /*
         * 🔴 The snapshot is captured AT WRITE TIME, not only when someone
         * types it. Two reasons, one of them a bug this fixed:
         *
         *   1. `testimonials.name` is NOT NULL, and ConvertEmptyStringsToNull
         *      turns a blank field into null before it ever reaches here —
         *      so linking a student and leaving the name empty produced a
         *      500 on save, not a validation message.
         *   2. The snapshot only earns its keep if it is populated while the
         *      account still exists. Filling it lazily would mean the very
         *      reviews most likely to outlive their student are the ones
         *      with nothing to fall back to.
         */
        $student = filled($validated['student_id'] ?? null)
            ? Student::with('branch')->find($validated['student_id'])
            : null;

        return [
            'student_id' => $validated['student_id'] ?? null,
            'name' => $validated['name'] ?: ($student?->full_name ?? ''),
            'title' => $validated['title'] ?: $this->snapshotTitle($student),
            'rate' => $validated['rate'],
            'description' => $validated['description'],
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'avatar_source' => $validated['avatar_source'],
        ];
    }

    /**
     * Stores an uploaded portrait against the review, morphically.
     *
     * 🔴 Allowed on the `media` table: a review portrait is a PUBLIC
     * marketing asset, the same category as sliders. The paid-content
     * boundary is untouched — lectures and library files still declare no
     * morph and must never gain one.
     *
     * ⚠️ Absence of a file is NOT "remove the picture". An admin editing the
     * quote leaves the file input empty every time, and treating that as a
     * delete would wipe the portrait on an unrelated save. Only a new upload
     * changes anything, and switching the source back to `student` leaves
     * the file in place so the choice stays reversible.
     */
    private function syncCustomPhoto(Request $request, Testimonial $testimonial): void
    {
        $file = $request->file('photo');

        if ($file === null) {
            return;
        }

        // One portrait per review: replace rather than accumulate, or the
        // ofMany('display_order','min') relation would keep showing the first
        // upload forever while later ones piled up invisibly.
        $testimonial->media()->delete();

        // 'custom' disk root is public/ — the stored path is root-relative,
        // which is what Media::url()'s asset() call expects.
        $testimonial->media()->create([
            'path' => $file->store('uploads/testimonials', 'custom'),
            'type' => MediaType::Image,
            'alt_text' => $testimonial->displayName(),
            'display_order' => 1,
        ]);
    }

    /**
     * The "branch · city" line, frozen from the account as it stands now.
     *
     * ⚠️ Returns an empty string rather than null: `title` is nullable, but
     * an empty string and a null read identically on the card and only one
     * of them needs a null check at every call site.
     */
    private function snapshotTitle(?Student $student): string
    {
        if (! $student) {
            return '';
        }

        return implode(' · ', array_filter([
            $student->branch?->name,
            $student->governorate ? __('governorates.'.$student->governorate) : null,
        ]));
    }

    /**
     * Students to pick from, labelled so two people with the same name are
     * still distinguishable.
     *
     * @return array<int, string>
     */
    private function studentOptions(): array
    {
        return Student::query()
            ->with('branch')
            ->orderBy('full_name')
            ->get()
            ->mapWithKeys(fn (Student $student): array => [
                $student->id => trim($student->full_name.' — '.($student->branch?->name ?? 'بلا فرع')),
            ])
            ->all();
    }
}
