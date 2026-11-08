<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\LectureStatus;
use App\Enums\VideoSource;
use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Lecture;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The teacher's lectures.
 *
 * 🔴 Every query and every write is scoped to the subjects assigned to this
 * teacher. The scope lives on the model (`Lecture::forTeacher`) so it cannot
 * be half-applied, and each write re-checks the target subject — a posted
 * subject_id is a number anyone can edit.
 *
 * ⚠️ Scoped by SUBJECT, not by `lectures.teacher_id`. A teacher manages their
 * subject's content including what a predecessor uploaded to it; filtering by
 * author would hide their own subject's material from them.
 */
class LectureController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user()->userable;

        $subjects = $teacher->subjects()->with('branch')->orderBy('name')->get();

        $subjectId = $request->integer('subject_id') ?: null;

        // ⚠️ A subject_id for something this teacher does not own is dropped
        // rather than obeyed, so a hand-edited query string cannot be used to
        // probe which subject ids exist.
        if ($subjectId !== null && ! $subjects->contains('id', $subjectId)) {
            $subjectId = null;
        }

        $status = in_array($request->query('status'), ['published', 'draft', 'hidden'], true)
            ? $request->query('status')
            : 'all';

        $search = $request->query('search');

        return view('teacher.lectures.index', [
            'lectures' => Lecture::query()
                ->forTeacher($teacher)
                ->with(['subject.branch'])
                ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when(filled($search), fn ($query) => $query->where('title', 'like', '%'.trim($search).'%'))
                ->latest('created_at')
                ->paginate(config('app.paginate_page'))
                ->withQueryString(),

            'subjects' => $subjects,
            'filters' => ['subject_id' => $subjectId, 'status' => $status, 'search' => $search],

            /*
             * 🔴 Surfaced because a lecture cannot exist without a term:
             * `lectures.academic_term_id` is NOT NULL. With none marked
             * current the upload form is disabled and says why, rather than
             * failing at the insert with a database error.
             */
            'currentTerm' => AcademicTerm::current(),

            /*
             * ⚠️ الوحدات المستعملة فعلاً في مواد هذا المعلّم — اقتراحات
             * لا قائمة مغلقة. المطابقة بالنصّ، و«الوحدة الأولى» مقابل
             * «الوحده الاولى» مجموعتان منفصلتان؛ الاقتراح يقلّل ذلك بلا
             * أن يمنع وحدة جديدة.
             */
            'knownUnits' => Lecture::query()
                ->forTeacher($teacher)
                ->whereNotNull('unit_name')
                ->distinct()
                ->orderBy('unit_name')
                ->pluck('unit_name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $request->user()->userable;

        $data = $this->validated($request, $teacher);

        $term = AcademicTerm::current();

        if (! $term) {
            /*
             * ⚠️ A guard, not an assumption. Inventing a term here would
             * silently attach content to a period nobody declared, and
             * `AcademicTerm::makeCurrent()` is the only writer of that flag.
             */
            return back()
                ->withInput()
                ->withErrors(['subject_id' => 'لا يوجد فصل دراسي حالي — تواصل مع الإدارة قبل رفع المحاضرات.']);
        }

        Lecture::create([
            'subject_id' => $data['subject_id'],
            'academic_term_id' => $term->id,
            // The uploader is the author; the subject decides who may manage it.
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'],
            'unit_name' => $data['unit_name'],
            'display_order' => $this->nextOrder($data['subject_id']),

            'video_source' => VideoSource::YouTube,
            // 🔴 The ID alone, never the pasted URL: the embed address is
            // rebuilt from it, so playlists, timestamps and tracking
            // parameters cannot ride along into an iframe on a student's page.
            'video_reference' => $data['video_id'],
            // A YouTube video is playable the moment it is linked — there is
            // no upload for us to process.
            'video_status' => VideoStatus::Ready,

            'status' => $data['status'],
            'published_at' => $data['status'] === LectureStatus::Published->value ? now() : null,
        ]);

        flash()->success('تمت إضافة المحاضرة بنجاح.');

        return redirect()->route('teacher.lectures', $this->filterState($request));
    }

    public function update(Request $request, Lecture $lecture): RedirectResponse
    {
        $teacher = $request->user()->userable;

        $this->authorizeLecture($lecture, $teacher);

        $data = $this->validated($request, $teacher);

        $lecture->update([
            'subject_id' => $data['subject_id'],
            'title' => $data['title'],
            /*
             * ⚠️ The slug follows the title only when the title changed —
             * regenerating it on every save would break a link already shared
             * for an edit that fixed a typo in the description.
             */
            'slug' => $lecture->title === $data['title']
                ? $lecture->slug
                : $this->uniqueSlug($data['title'], $lecture->id),
            'description' => $data['description'],
            'unit_name' => $data['unit_name'],
            'video_source' => VideoSource::YouTube,
            'video_reference' => $data['video_id'],
            'status' => $data['status'],
            'published_at' => $data['status'] === LectureStatus::Published->value
                // Publishing an already-published lecture keeps its first date.
                ? ($lecture->published_at ?? now())
                : null,
        ]);

        flash()->success('تم تعديل المحاضرة بنجاح.');

        return redirect()->route('teacher.lectures', $this->filterState($request));
    }

    public function destroy(Request $request, Lecture $lecture): RedirectResponse
    {
        $this->authorizeLecture($lecture, $request->user()->userable);

        // Soft delete: the teacher's trash screen restores it.
        $lecture->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        return redirect()->route('teacher.lectures', $this->filterState($request));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * 🔴 404, not 403. A lecture outside this teacher's subjects is
     * indistinguishable from one that does not exist as far as they are
     * concerned — answering "forbidden" would confirm the id is real and that
     * a colleague has content there.
     */
    private function authorizeLecture(Lecture $lecture, Teacher $teacher): void
    {
        abort_unless(
            $teacher->subjects()->whereKey($lecture->subject_id)->exists(),
            404
        );
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, Teacher $teacher): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            /*
             * 🔴 Restricted to THIS teacher's subjects, not to `exists`. A
             * plain existence rule would let a hand-edited form move a lecture
             * into a colleague's subject — or into another branch entirely,
             * which is how content reaches the wrong students.
             */
            'subject_id' => ['required', Rule::in($teacher->subjects()->pluck('subjects.id')->all())],
            'video_url' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_name' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in([LectureStatus::Draft->value, LectureStatus::Published->value])],
        ], [
            'subject_id.in' => 'اختر مادة من موادك المسندة.',
        ]);

        $videoId = Lecture::youtubeId($validated['video_url']);

        if ($videoId === null) {
            /*
             * ⚠️ Thrown here rather than expressed as a regex rule: the
             * parser is the single definition of what this project accepts,
             * and a second pattern in the rules would drift from it. The
             * error is attached to the field the teacher typed into.
             */
            throw ValidationException::withMessages([
                'video_url' => 'الرابط ليس رابط يوتيوب صالحاً — انسخه من شريط العنوان أو من زرّ المشاركة.',
            ]);
        }

        return [
            'title' => $validated['title'],
            'subject_id' => (int) $validated['subject_id'],
            'description' => $validated['description'] ?? null,
            'unit_name' => filled($validated['unit_name'] ?? null) ? trim($validated['unit_name']) : null,
            'status' => $validated['status'],
            'video_id' => $videoId,
        ];
    }

    /** Keeps the teacher on the list they were filtering. */
    private function filterState(Request $request): array
    {
        return array_filter([
            'subject_id' => $request->integer('filter_subject_id') ?: null,
            'status' => $request->query('filter_status'),
            'search' => $request->query('filter_search'),
        ]);
    }

    /** Appends to the end of the subject's list rather than the platform's. */
    private function nextOrder(int $subjectId): int
    {
        return (int) Lecture::where('subject_id', $subjectId)->max('display_order') + 1;
    }

    /**
     * 🔴 Str::slug() strips non-latin characters, so an Arabic title reduces
     * to an EMPTY string and every lecture would collide on ''. The fallback
     * keeps the Arabic, replacing only whitespace.
     *
     * ⚠️ withTrashed: `slug` is unique and the index does not ignore
     * deleted_at, so a trashed lecture still occupies its slug.
     */
    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = trim(preg_replace('/\s+/u', '-', trim($title)), '-');
        }

        $base = $base === '' ? 'lecture' : $base;
        $slug = $base;
        $suffix = 2;

        while (Lecture::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
