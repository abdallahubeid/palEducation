<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Testimonial;
use App\Notifications\TestimonialPendingNotification;
use App\Support\AdminNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A student submitting their own review from the home page.
 *
 * 🔴 Nothing about the reviewer is accepted from the request. The student is
 * resolved from the SESSION, and their name, branch and city are read from
 * their own record — a posted `student_id` would let any signed-in account
 * file a review under somebody else's name.
 *
 * 🔴 Submissions are stored INACTIVE. This is a public page and the form is
 * open to every student on the platform; publishing straight to the landing
 * page would make it the easiest defacement surface in the product. An admin
 * approves from the dashboard toggle.
 */
class TestimonialSubmissionController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $student = $this->resolveStudent($request);

        $validated = $request->validate([
            /*
             * ⚠️ Bounded here, not just by the column. unsignedTinyInteger
             * takes anything up to 255, and the card would try to draw that
             * many stars.
             */
            'rate' => ['required', 'integer', Rule::in(range(Testimonial::MIN_RATE, Testimonial::MAX_RATE))],
            'description' => ['required', 'string', 'min:20', 'max:1000'],
        ], [
            'description.min' => 'اكتب رأياً أوضح قليلاً — 20 حرفاً على الأقل.',
        ]);

        /*
         * 🔴 One review per student, updated rather than appended.
         *
         * Without this a student can post the same quote fifty times and the
         * carousel becomes their personal feed. Re-submitting replaces their
         * existing review AND returns it to pending — an edited review has
         * not been approved, only its predecessor was.
         */
        $testimonial = Testimonial::updateOrCreate(
            ['student_id' => $student->id],
            [
                'rate' => $validated['rate'],
                'description' => $validated['description'],

                // Snapshot captured now, while the account exists: it is what
                // the card falls back to if the student is ever deleted.
                'name' => $student->full_name,
                'title' => $this->snapshotTitle($student),

                // 🔴 Always false. Not a default an admin can be talked out
                // of — a re-submission must re-enter the queue too.
                'is_active' => false,
            ],
        );

        // ⚠️ يُرسَل على التعديل كما على الإنشاء: updateOrCreate تُعيد
        // الرأي إلى الطابور، فالمراجعة مطلوبة مجدداً.
        AdminNotifier::send(new TestimonialPendingNotification($testimonial));

        $message = 'وصلنا رأيك، شكراً لك. سيظهر بعد مراجعته.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'pending' => true,
                'rate' => $testimonial->rate,
            ]);
        }

        // No-JS path: a normal POST lands here and returns to the section.
        return redirect()
            ->to(route('home').'#voices')
            ->with('testimonial.submitted', $message);
    }

    /**
     * The signed-in student, or 403.
     *
     * ⚠️ A teacher or admin is authenticated but has no `students` row, and
     * `testimonials.student_id` points at that table. Letting them through
     * would fail on the foreign key as a 500 rather than a refusal.
     */
    private function resolveStudent(Request $request): Student
    {
        $userable = $request->user()?->userable;

        abort_unless($userable instanceof Student, 403, 'هذا النموذج للطلبة فقط.');

        return $userable;
    }

    /** The "branch · city" line, frozen from the account as it stands now. */
    private function snapshotTitle(Student $student): string
    {
        return implode(' · ', array_filter([
            $student->branch?->name,
            $student->governorate ? __('governorates.'.$student->governorate) : null,
        ]));
    }
}
