<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\PricingPlan;
use App\Models\Setting;
use App\Models\Step;
use App\Models\Teacher;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

/**
 * الصفحة الرئيسية.
 *
 * ⚠️ حلّ محلّ Route::view لأن الصفحة صار لها محتوى من القاعدة. كنترولر
 * لا closure عمداً: مسار بـclosure يُفشل `route:cache` كلّه، لا مساره
 * وحده — والصفحة الرئيسية آخر مكان يُقبل فيه إسقاط الذاكرة المؤقّتة.
 *
 * ⏳ بقيّة أقسام الصفحة ما زالت بيانات عرض في القالب. تنتقل هنا قسماً
 * قسماً كلّما وُصل قسم بجدوله.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'cta' => Setting::cta(),
            'how' => Setting::how(),

            // active() هنا لا في العرض: الفلترة في الاستعلام قاعدة
            // المشروع، وخطوة مخفيّة يجب ألّا تصل القالب أصلاً.
            'steps' => Step::active()->ordered()->get(),

            'about' => Setting::about(),
            'featuresHeader' => Setting::features(),

            'teachersHeader' => Setting::teachers(),

            /*
             * Everything on a teacher card is derived, so all of it is eager
             * loaded here rather than reached from the template:
             *
             *   image            → the polymorphic portrait
             *   subjects.branch  → the branch, reached through the subject
             *   lectures_count   → counted, never stored
             *
             * ⚠️ Only `subjects.branch`, NOT `primarySubjects.branch` too:
             * both relations point at the same table, so loading each one
             * fetched the subjects and branches twice. The primary is picked
             * out of the loaded collection by its pivot instead.
             *
             * The count is restricted to PUBLISHED lectures — a draft is not
             * something to advertise on the landing page.
             */
            'teacherCards' => Teacher::query()
                ->publiclyListed()
                ->with(['image', 'subjects.branch'])
                ->withCount(['lectures as lectures_count' => fn ($query) => $query->published()])
                ->ordered()
                ->get(),

            'faqHeader' => Setting::faq(),

            // active() in the query, not the view — project rule. A hidden
            // question must never reach the template in the first place.
            'faqs' => Faq::query()->active()->ordered()->get(),

            'testimonialsHeader' => Setting::testimonials(),

            /*
             * ⚠️ student.branch is two hops (testimonial → student → branch)
             * and the card shows the branch on every slide, so without this
             * eager load it is two queries per review.
             *
             * `photo` is the attached portrait; the student's own avatar is
             * the fallback inside photoUrl().
             */
            'testimonials' => Testimonial::query()
                ->active()
                ->with(['student.branch', 'photo'])
                ->ordered()
                ->get(),

            'pricingHeader' => Setting::pricing(),

            // active() in the query, not the view — project rule. A withdrawn
            // plan must never reach the template in the first place.
            'pricingPlans' => PricingPlan::query()->active()->ordered()->get(),

            // active() in the query, not the view — project rule. A hidden
            // card must never reach the template in the first place.
            'featureCards' => Feature::query()->active()->ordered()->get(),

            'whyUs' => Setting::whyUs(),
            'branchesHeader' => Setting::branchesHeader(),
            'videoTour' => Setting::videoTour(),

            /*
             * Cards for the branches section.
             *
             * withCardCounts() derives the subject and teacher figures from
             * the live relations — they used to be columns on `branches`,
             * which meant the card advertised whatever was typed there long
             * after the content moved on.
             *
             * Filtering in the query, not the view: project rule.
             */
            'branchCards' => Branch::query()
                ->where('is_active', true)
                ->withCardCounts()
                ->ordered()
                ->get(),
        ]);
    }
}
