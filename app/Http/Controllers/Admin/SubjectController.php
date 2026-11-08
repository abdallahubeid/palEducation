<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Subject;
use App\Models\Teacher;
use App\Notifications\SubjectVisibilityChangedNotification;
use App\Support\TeacherNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Subjects — the join between a branch, its teachers, and its content.
 *
 * 🔴 A subject is the hinge of the whole domain: lectures and library files
 * hang off it, teachers are assigned through it, and `subjects.branch_id` is
 * the ONLY path by which a branch reaches content. Deleting one hides
 * everything beneath it from every student in that branch — which is why
 * destroy() refuses while content still exists.
 */
class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;
        $teacherId = $request->integer('teacher_id') ?: null;
        $search = $request->query('q');

        return view('admin.subjects.index', [
            'subjects' => Subject::query()
                /*
                 * ⚠️ Four eager loads, each replacing a per-row query: the
                 * branch badge, the teacher avatar stack (and its media), and
                 * the two content counts. Without them a page of fifteen
                 * subjects is roughly sixty queries.
                 */
                ->with(['branch', 'teachers.image', 'image'])
                ->withCount(['lectures', 'libraryFiles'])
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->when($teacherId, fn ($query) => $query->whereHas(
                    'teachers', fn ($teachers) => $teachers->whereKey($teacherId)
                ))
                ->when(filled($search), fn ($query) => $query->where('name', 'like', '%'.trim($search).'%'))
                ->ordered()
                ->paginate(config('app.paginate_page'))
                ->withQueryString(),

            'branches' => Branch::query()->ordered()->pluck('name', 'id'),
            'teachers' => Teacher::query()->orderBy('full_name')->pluck('full_name', 'id'),

            'filters' => ['branch_id' => $branchId, 'teacher_id' => $teacherId, 'q' => $search],
        ]);
    }

    public function show(Subject $subject): View
    {
        return view('admin.subjects.show', [
            'subject' => $subject->load(['branch', 'teachers.image', 'image'])
                ->loadCount(['lectures', 'libraryFiles']),
        ]);
    }

    public function create(): View
    {
        return view('admin.subjects.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $subject = DB::transaction(function () use ($data, $request): Subject {
            $subject = Subject::create([
                ...$data['attributes'],
                'slug' => $this->uniqueSlug($data['attributes']['name']),
            ]);

            $this->syncTeachers($subject, $data['teacher_ids'], $request);

            return $subject;
        });

        $this->syncCover($request, $subject);

        flash()->success('تمت إضافة المادة بنجاح.');

        return redirect()->route('admin.subjects.index');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject->load(['teachers', 'image']),
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $data = $this->validated($request, $subject);

        DB::transaction(function () use ($subject, $data, $request): void {
            $subject->update([
                ...$data['attributes'],
                /*
                 * ⚠️ The slug follows the name, but only when the name
                 * actually changed — regenerating it on every save would
                 * break links already shared for an edit that fixed a typo.
                 */
                'slug' => $subject->name === $data['attributes']['name']
                    ? $subject->slug
                    : $this->uniqueSlug($data['attributes']['name'], $subject->id),
            ]);

            $this->syncTeachers($subject, $data['teacher_ids'], $request);
        });

        $this->syncCover($request, $subject);

        flash()->success('تم تعديل المادة بنجاح.');

        return redirect()->route('admin.subjects.index');
    }

    public function toggle(Subject $subject): RedirectResponse
    {
        $subject->update(['is_published' => ! $subject->is_published]);

        // 🔴 كل معلّم مسند للمادة يُخطَر: ظهور مادته للطلبة أو اختفاؤها حدث
        // يخصّه، ولا يجب أن يكتشفه بشاشة نشاط خفتت فجأة.
        $subject->teachers->each(fn (Teacher $teacher) => TeacherNotifier::notify(
            $teacher,
            new SubjectVisibilityChangedNotification($subject->name, $subject->is_published),
        ));

        flash()->success($subject->is_published ? 'المادة منشورة الآن.' : 'أُخفيت المادة.');

        return redirect()->route('admin.subjects.index');
    }

    /**
     * Deletes a subject, but only an empty one.
     *
     * 🔴 The guard is the point. Soft-deleting a subject hides every lecture
     * and file beneath it from every student in the branch, and those rows
     * are NOT themselves deleted — they become unreachable content with no
     * screen that lists them. Refusing is recoverable; a silent orphaning is
     * not.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $lectures = $subject->lectures()->count();
        $files = $subject->libraryFiles()->count();

        if ($lectures > 0 || $files > 0) {
            flash()->error(
                'لا يمكن حذف المادة: تحتوي على '.$lectures.' محاضرة و'.$files.' ملف. '
                .'انقل محتواها أو احذفه أولاً، أو أخفِ المادة بدل حذفها.'
            );

            return redirect()->route('admin.subjects.index');
        }

        $subject->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'subjects', 'id' => $subject->id]),
            'label' => 'تراجع',
        ]);

        return redirect()->route('admin.subjects.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'branches' => Branch::query()->ordered()->pluck('name', 'id'),
            'teacherOptions' => Teacher::query()->orderBy('full_name')->get(['id', 'full_name']),
            'tones' => ['accent' => 'أخضر', 'tag' => 'أزرق', 'amber' => 'كهرماني', 'warn' => 'برتقالي'],
        ];
    }

    /** @return array{attributes: array<string, mixed>, teacher_ids: array<int, int>} */
    private function validated(Request $request, ?Subject $subject = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'accent_token' => ['nullable', Rule::in(['accent', 'tag', 'amber', 'warn'])],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cover' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'teacher_ids' => ['nullable', 'array'],
            /*
             * 🔴 Each id is verified to be a real, undeleted teacher. The
             * form posts a checkbox list, so the request is a list of
             * integers anyone can edit — attaching an arbitrary id would put
             * a stranger's name on a public subject card.
             */
            'teacher_ids.*' => [Rule::exists('teachers', 'id')->whereNull('deleted_at')],
        ]);

        return [
            'attributes' => [
                'name' => $validated['name'],
                'branch_id' => $validated['branch_id'],
                'description' => $validated['description'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'accent_token' => $validated['accent_token'] ?? 'accent',
                'display_order' => $validated['display_order'] ?? 0,
                // ⚠️ An absent checkbox means UNCHECKED, not "leave as is".
                'is_published' => $request->boolean('is_published'),
            ],
            'teacher_ids' => array_map('intval', $validated['teacher_ids'] ?? []),
        ];
    }

    /**
     * Syncs the pivot, preserving who was primary.
     *
     * ⚠️ sync() with attributes would rewrite `assigned_at` and `assigned_by`
     * on every save, so a teacher assigned last term would look newly
     * assigned after an unrelated edit. Only genuinely NEW rows get stamped.
     */
    private function syncTeachers(Subject $subject, array $teacherIds, Request $request): void
    {
        $existing = $subject->teachers()->pluck('teachers.id')->all();

        $payload = [];

        foreach ($teacherIds as $index => $id) {
            $payload[$id] = in_array($id, $existing, true)
                // 🔴 Untouched for an existing row: only is_primary may move.
                ? ['is_primary' => $index === 0]
                : [
                    'is_primary' => $index === 0,
                    'assigned_at' => now(),
                    'assigned_by' => $request->user()?->id,
                ];
        }

        $subject->teachers()->sync($payload);
    }

    /**
     * A unique slug, derived from an Arabic name.
     *
     * 🔴 Str::slug() strips non-latin characters, so an Arabic name reduces
     * to an EMPTY string and every subject would collide on ''. The fallback
     * keeps the Arabic, replacing only whitespace.
     *
     * ⚠️ withTrashed: `slug` is unique and the index does not ignore
     * deleted_at, so a trashed subject still occupies its slug.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = trim(preg_replace('/\s+/u', '-', trim($name)), '-');
        }

        $base = $base === '' ? 'subject' : $base;
        $slug = $base;
        $suffix = 2;

        while (Subject::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Attaches or replaces the cover through the polymorphic media relation.
     *
     * ⚠️ Outside the transaction on purpose: a file write cannot be rolled
     * back, so keeping it inside would leave an orphaned file on disk
     * whenever the transaction failed after the upload.
     */
    private function syncCover(Request $request, Subject $subject): void
    {
        $file = $request->file('cover');

        if ($file === null) {
            return;
        }

        $previous = $subject->image?->path;

        $path = $file->store('subjects', 'public');

        $subject->media()->updateOrCreate([], [
            'path' => 'storage/'.$path,
            'type' => 'image',
            'alt_text' => $subject->name,
            'display_order' => 1,
        ]);

        // 🔴 Only files WE stored: seeded artwork under public/images is a
        // repository file, and deleting one removes it from every
        // environment's working tree for ever.
        if ($previous && str_starts_with($previous, 'storage/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $previous));
        }
    }
}
