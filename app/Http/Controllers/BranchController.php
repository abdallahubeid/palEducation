<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The public branch pages.
 *
 * 🔴 "Branch" here is an ACADEMIC STREAM — علمي · أدبي · تجاري · صناعي — not
 * a physical office. It is the platform's root content filter: a student
 * belongs to exactly one, and sees nothing outside it. That is why these
 * pages carry subjects, teachers and lecture counts rather than an address
 * and opening hours.
 *
 * ⚠️ Everything public is filtered twice: the branch must be active, AND the
 * subjects counted or listed must be published. A draft subject is not
 * something to advertise, and an inactive branch must not be reachable by
 * guessing its slug.
 */
class BranchController extends Controller
{
    public function index(): View
    {
        return view('pages.branches', [
            'branches' => Branch::query()
                ->where('is_active', true)
                ->withCardCounts()
                ->ordered()
                /*
                 * Loaded for the client-side search box only: the visitor
                 * filters by subject name as well as branch name, and one
                 * query here beats one per card.
                 */
                ->with(['subjects' => fn ($query) => $query->published()->select('id', 'branch_id', 'name')])
                ->get(),
        ]);
    }

    public function show(Branch $branch): View
    {
        /*
         * 🔴 404, not 403. An inactive branch is indistinguishable from one
         * that never existed as far as a visitor is concerned — answering
         * "forbidden" would confirm the slug is real.
         *
         * ⚠️ Checked here rather than by scoping the route binding, so the
         * same model can still be bound by admin routes.
         */
        if (! $branch->is_active) {
            throw new NotFoundHttpException;
        }

        /*
         * 🔴 One named scope instead of an eager-load list written out here.
         * The nested loads (subjects → teachers → their portraits) are what
         * keep this page off a per-row query, and a screen that copied half
         * the list would regress silently — invisible with three rows in
         * development, quadratic in production.
         */
        $branch->load(Branch::publicContentRelations());

        /*
         * ⚠️ One teacher can carry two subjects in the same branch. The
         * deduplication lives on the model rather than here, so the public
         * page and any admin screen that asks the same question cannot answer
         * it differently — a duplicate face in the grid AND a wrong number in
         * the stats is one bug with two symptoms.
         */
        $teachers = $branch->uniqueTeachers();

        return view('pages.branch', [
            'branch' => $branch,
            'teachers' => $teachers,

            /*
             * Derived from the loaded rows rather than counted again: the
             * subjects are already in memory with their counts, so four extra
             * aggregate queries would buy nothing.
             */
            'stats' => [
                'subjects' => $branch->subjects->count(),
                'teachers' => $teachers->count(),
                'lectures' => $branch->subjects->sum('lectures_count'),
                'files' => $branch->subjects->sum('library_files_count'),
            ],
        ]);
    }
}
