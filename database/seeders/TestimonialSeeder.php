<?php

namespace Database\Seeders;

use App\Enums\MediaType;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Student reviews on the public home page.
 *
 * 🔴 Each review is LINKED to a real student account, so the name, branch
 * and city on the card are read live rather than typed. The snapshot columns
 * are filled anyway: they are what the card falls back to if the account is
 * ever deleted, and a review with no name at all would render as a floating
 * quote.
 *
 * ⚠️ Ordering: must run AFTER BranchSeeder. `students.branch_id` is NOT NULL
 * with a foreign key, so a student cannot be created without one.
 *
 * Accounts get a RANDOM password that is never printed or stored readably —
 * same rule as the teacher roster. Seeding a known password would ship live
 * logins for addresses shown beside a public quote.
 */
class TestimonialSeeder extends Seeder
{
    /** [name, branch, governorate, school, rate, quote, email, portrait, avatar source] */
    private const REVIEWS = [
        [
            'ليان أبو ديّة', 'scientific', 'ramallah', 'مدرسة رام الله الثانوية', 5,
            'لأول مرّة أشعر أنني أدرس بترتيب. أفتح المنصة فأجد مواد فرعي أمامي مباشرة، وما إن تنتهي المحاضرة حتى يصلني الاختبار — لم أعد أضيّع وقتي في البحث.',
            'layan.abudayyeh@paleducation.test',
            // Custom: an uploaded portrait attached morphically to the review.
            'images/voices/student-1.jpg', Testimonial::AVATAR_CUSTOM,
        ],
        [
            'أحمد الشوّا', 'literary', 'nablus', 'مدرسة نابلس الثانوية', 5,
            'الاختبار بعد كل محاضرة جعلني أكتشف مواضع ضعفي أولاً بأول، بدل أن أكتشفها في ورقة الامتحان.',
            'ahmad.shawwa@paleducation.test',
            'images/voices/student-2.jpg', Testimonial::AVATAR_CUSTOM,
        ],
        [
            'سارة قواسمة', 'commercial', 'hebron', 'مدرسة الخليل الثانوية', 4,
            'المكتبة وحدها وفّرت عليّ ساعات. كل الأوراق والامتحانات السابقة مرتّبة حسب المادة في مكان واحد.',
            'sara.qawasmeh@paleducation.test',
            /*
             * 🔴 Deliberately the OTHER branch. Seeding all three the same
             * way would leave the student-avatar path — and its fallback to
             * the placeholder — unexercised by anything a developer sees.
             */
            'images/voices/student-3.jpg', Testimonial::AVATAR_STUDENT,
        ],
    ];

    public function run(): void
    {
        foreach (self::REVIEWS as $index => [$name, $branchSlug, $governorate, $school, $rate, $quote, $email, $portrait, $avatarSource]) {
            $branch = Branch::query()->where('slug', $branchSlug)->first();

            if (! $branch) {
                // Silence here would seed reviews with no reviewer behind them.
                $this->command?->warn("TestimonialSeeder: branch {$branchSlug} missing, skipped {$name}.");

                continue;
            }

            $student = Student::firstOrCreate(
                ['full_name' => $name],
                [
                    'branch_id' => $branch->id,
                    'school_name' => $school,
                    'governorate' => $governorate,
                    'country_code' => 'ps',
                    // The account photo — what the `student` source renders.
                    'avatar_path' => $portrait,
                ],
            );

            $this->attachAccount($student, $email);

            $testimonial = Testimonial::firstOrCreate(
                ['student_id' => $student->id],
                [
                    // Snapshot fallbacks, filled even though the link exists:
                    // they are what renders if the account is ever removed.
                    'name' => $name,
                    'title' => $branch->name.' · '.__('governorates.'.$governorate),
                    'rate' => $rate,
                    'description' => $quote,
                    'display_order' => $index + 1,
                    'is_active' => true,
                    'avatar_source' => $avatarSource,
                ],
            );

            if ($avatarSource === Testimonial::AVATAR_CUSTOM) {
                $this->attachPortrait($testimonial, $portrait, $name);
            }
        }
    }

    /**
     * 🔴 Allowed on the media table: a review portrait is a PUBLIC marketing
     * asset, the same category as sliders. The paid-content boundary is
     * untouched — lectures and library files declare no morph and must
     * never gain one.
     */
    private function attachPortrait(Testimonial $testimonial, string $path, string $name): void
    {
        if ($testimonial->media()->exists()) {
            return;
        }

        $testimonial->media()->create([
            'path' => $path,
            'type' => MediaType::Image,
            'alt_text' => $name,
            'display_order' => 1,
        ]);
    }

    /**
     * 🔴 syncRoleToShape() inside the transaction. An account created without
     * a role does not complain — it works until the first permission check
     * and then fails.
     */
    private function attachAccount(Student $student, string $email): void
    {
        if ($student->user()->withTrashed()->exists()) {
            return;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->command?->warn("TestimonialSeeder: {$email} already in use, account skipped.");

            return;
        }

        DB::transaction(function () use ($student, $email) {
            $student->user()->create([
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                // Verified: an admin-seeded account has nobody to click a link.
                'email_verified_at' => now(),
                'is_active' => true,
                'locale' => 'ar',
            ])->syncRoleToShape();
        });
    }
}
