<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\LectureStatus;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The teacher's own assigned subjects.
 *
 * 🔴 The scope rule, stated plainly: a teacher sees the subjects assigned to
 * THEM and nothing else — not their colleagues', not every subject in a
 * branch. The query starts from `$teacher->subjects()`, so there is no filter
 * to forget and no id in the route to tamper with.
 *
 * ⚠️ Every figure on this page is derived from a table that actually exists.
 * The static mock this replaces showed an "average score" per subject; there
 * is no quizzes, attempts or progress table in this project yet, so that
 * number had nothing behind it and is not rendered. A teacher acting on an
 * invented average is worse off than one shown nothing.
 */
class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user()->userable;

        $subjects = $teacher->subjects()
            /*
             * ⚠️ branch for the badge, image for the cover, and both counts —
             * each replacing a per-card query. A teacher with eight subjects
             * would otherwise cost about thirty.
             */
            ->with(['branch', 'image'])
            ->withCount([
                'lectures',
                'libraryFiles',
                // 🔴 Drafts counted separately, not filtered out of the total:
                // the card shows how much content exists AND how much of it is
                // still unpublished, which is the only actionable number here.
                'lectures as draft_lectures_count' => fn ($query) => $query
                    ->where('status', '!=', LectureStatus::Published),
            ])
            ->orderBy('display_order')
            ->orderBy('subjects.id')
            ->get();

        return view('teacher.subjects.index', [
            'subjects' => $subjects,

            /*
             * 🔴 Students per BRANCH, not per subject — and the view labels it
             * that way. There is no enrolment table: a student belongs to a
             * branch and sees every published subject in it, so "students
             * taking this subject" is not a question the schema can answer
             * yet. Printing a branch count under a "students" label would be
             * a number that quietly means something else.
             *
             * ⚠️ One grouped query for every branch on the page, not one per
             * card.
             */
            'branchStudentCounts' => Student::query()
                ->whereIn('branch_id', $subjects->pluck('branch_id')->filter()->unique())
                ->groupBy('branch_id')
                ->select('branch_id', DB::raw('count(*) as total'))
                ->pluck('total', 'branch_id'),
        ]);
    }
}
