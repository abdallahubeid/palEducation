<?php

namespace Database\Seeders;

use App\Enums\LectureStatus;
use App\Enums\MediaType;
use App\Models\AcademicTerm;
use App\Models\Lecture;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The four teachers shown on the public roster.
 *
 * 🔴 The lecture counts on the cards are DERIVED, so this seeds REAL
 * lectures — 42 + 36 + 31 + 38 = 147 rows. Storing the figures on a column
 * instead would put a number on the public page that the content contradicts
 * the moment anything is published or removed; that pattern was deliberately
 * taken off `branches` for exactly this reason.
 *
 * ⚠️ Ordering matters: this must run AFTER BranchSeeder, AcademicTermSeeder
 * and SubjectSeeder. `lectures.academic_term_id` is mandatory and subjects
 * carry the branch, so neither can be created here.
 *
 * 🔁 REVERSAL, recorded. These four used to be seeded WITHOUT accounts, on
 * the reasoning that a marketing card does not need credentials. That was
 * overruled: a teacher is a User, and a roster entry detached from the
 * accounts table is a profile nobody can ever sign in as, edit through the
 * Users screen, or assign content to.
 *
 * 🔴 Each account gets a RANDOM password that is never printed or stored
 * anywhere readable. Seeding a known password would ship four working logins
 * with public email addresses. Sign-in goes through password reset.
 */
class TeacherSeeder extends Seeder
{
    /** [full name, public title, subject slug, branch slug, portrait, lectures, email] */
    private const TEACHERS = [
        ['أ. سامر خليل', 'الرياضيات', 'math', 'scientific', 'images/teachers/team-1.jpg', 42, 'samer.khalil@paleducation.test'],
        ['أ. رنا عوض', 'الفيزياء', 'physics', 'scientific', 'images/teachers/team-2.jpg', 36, 'rana.awad@paleducation.test'],
        ['أ. وليد حمد', 'الكيمياء', 'chemistry', 'scientific', 'images/teachers/team-3.jpg', 31, 'walid.hamad@paleducation.test'],
        ['أ. مازن سالم', 'اللغة العربية', 'arabic', 'literary', 'images/teachers/team-4.jpg', 38, 'mazen.salem@paleducation.test'],
    ];

    public function run(): void
    {
        $term = AcademicTerm::query()->where('is_current', true)->first()
            ?? AcademicTerm::query()->orderBy('id')->first();

        if (! $term) {
            // Nothing to attach lectures to. Silence here would seed four
            // teachers whose cards all read "0 محاضرة".
            $this->command?->warn('TeacherSeeder skipped: no academic term. Run AcademicTermSeeder first.');

            return;
        }

        foreach (self::TEACHERS as [$name, $title, $subjectSlug, $branchSlug, $portrait, $lectureCount, $email]) {
            $subject = Subject::query()
                ->where('slug', $subjectSlug)
                ->whereRelation('branch', 'slug', $branchSlug)
                ->first();

            if (! $subject) {
                $this->command?->warn("TeacherSeeder: subject {$subjectSlug} missing, skipped {$name}.");

                continue;
            }

            $teacher = Teacher::firstOrCreate(
                ['full_name' => $name],
                [
                    // Stated explicitly as well as derivable: this is the
                    // branch the public card shows.
                    'branch_id' => $subject->branch_id,
                    'title' => $title,
                    'avatar_path' => $portrait,
                    'is_publicly_listed' => true,
                ],
            );

            $this->attachAccount($teacher, $email);

            // Content still reaches a branch through the SUBJECT, which is
            // what this assignment establishes. teachers.branch_id above only
            // labels the public card.
            $teacher->subjects()->syncWithoutDetaching([
                $subject->id => ['is_primary' => true, 'assigned_at' => now()],
            ]);

            $this->attachPortrait($teacher, $portrait, $name);
            $this->seedLectures($teacher, $subject, $term->id, $lectureCount);
        }
    }

    /**
     * Gives the teacher a real account, or leaves an existing one alone.
     *
     * 🔴 syncRoleToShape() inside the same transaction. A user created
     * without a role does not complain — it works until the first permission
     * check and then fails, which is exactly the drift the role rules warn
     * about.
     *
     * ⚠️ The password is random and discarded. Nobody, including whoever runs
     * the seeder, learns it: these are real addresses on the public roster,
     * and a known seeded password would be four open doors.
     */
    private function attachAccount(Teacher $teacher, string $email): void
    {
        if ($teacher->user()->withTrashed()->exists()) {
            return;
        }

        // A rerun after a manual account change must not collide on email.
        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->command?->warn("TeacherSeeder: {$email} already in use, account skipped.");

            return;
        }

        DB::transaction(function () use ($teacher, $email) {
            $teacher->user()->create([
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                // Verified: an admin-seeded account has nobody to click a link.
                'email_verified_at' => now(),
                'is_active' => true,
                'locale' => 'ar',
            ])->syncRoleToShape();
        });
    }

    /**
     * 🔴 Allowed on the media table: a teacher portrait is a PUBLIC marketing
     * asset, the same category as sliders and testimonials. The paid-content
     * boundary is untouched — lectures and library files still declare no
     * morph and must never gain one.
     */
    private function attachPortrait(Teacher $teacher, string $path, string $name): void
    {
        if ($teacher->media()->exists()) {
            return;
        }

        $teacher->media()->create([
            'path' => $path,
            'type' => MediaType::Image,
            'alt_text' => $name,
            'display_order' => 1,
        ]);
    }

    /**
     * ⚠️ Inserted in one statement rather than 42 model saves: this seeder
     * writes 147 rows, and a save per row turns `migrate:fresh --seed` into
     * a noticeable wait for no benefit.
     *
     * The slug is unique per subject, so it carries the teacher id — two
     * teachers on the same subject would otherwise collide on lecture 1.
     */
    private function seedLectures(Teacher $teacher, Subject $subject, int $termId, int $count): void
    {
        $existing = $teacher->lectures()->count();

        if ($existing >= $count) {
            return;
        }

        $rows = [];
        $now = now();

        for ($i = $existing + 1; $i <= $count; $i++) {
            $rows[] = [
                'subject_id' => $subject->id,
                'academic_term_id' => $termId,
                'teacher_id' => $teacher->id,
                'title' => $subject->name.' — المحاضرة '.$i,
                'slug' => 't'.$teacher->id.'-lecture-'.$i,
                'display_order' => $i,
                // Published: a draft is not something the public card counts.
                'status' => LectureStatus::Published->value,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('lectures')->insert($chunk);
        }
    }
}
