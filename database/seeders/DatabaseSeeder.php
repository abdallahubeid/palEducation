<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 🔴 الأدوار قبل أي حساب. الترتيب ليس تفضيلاً: syncRoleToShape()
        // تسند دوراً يجب أن يكون موجوداً، وحساب يُنشأ قبل دوره يبقى بلا
        // صلاحيات حتى يُردَم يدوياً — وهو بالضبط الانفصال الذي تحذّر منه
        // وثيقة المعمارية (المرحلتان 1 و2 تهبطان معاً).
        // 🔴 AcademicTermSeeder قبل أي محاضرة: lectures.academic_term_id
        // إجباري، فلا محاضرة قبل فصل — بما في ذلك في البذور.
        // وSubjectSeeder بعد BranchSeeder: subjects.branch_id إجباري.
        $this->call([
            RoleSeeder::class,
            BranchSeeder::class,
            AcademicTermSeeder::class,
            SubjectSeeder::class,
            // After SubjectSeeder: the roster attaches real lectures, which need
            // both a subject and an academic term to exist first.
            TeacherSeeder::class,
            SliderSeeder::class,
            NavbarSettingSeeder::class,
            CtaSettingSeeder::class,
            HowSettingSeeder::class,
            StepSeeder::class,
            FeatureSeeder::class,
            PricingPlanSeeder::class,
            // After BranchSeeder: each review creates a student, and
            // students.branch_id is NOT NULL with a foreign key.
            TestimonialSeeder::class,
            FaqSeeder::class,
            NewsSeeder::class,
            SettingSeeder::class,
        ]);

        if (! app()->environment('local')) {
            return;
        }

        // ── حسابات تطوير: واحد لكل نوع ───────────────────────────
        // سجلّ المجال أولاً ثم الحساب — لأن userable_id إجباري.

        $student = Student::create([
            'full_name' => 'طالب تجريبي',
            'branch_id' => Branch::where('slug', 'scientific')->value('id'),
            'school_name' => 'مدرسة تجريبية',
            'governorate' => 'nablus',
            'country_code' => 'ps',
            'phone' => '0599000001',
        ]);
        $student->user()->create([
            'email' => 'student@paleducation.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ])->syncRoleToShape();

        // 🔴 is_publicly_listed = false. This is a LOGIN fixture, not a
        // profile: the public roster now renders straight from this table,
        // so leaving it true put "معلّم تجريبي" with no portrait and zero
        // lectures on the landing page beside the four real teachers.
        $teacher = Teacher::create([
            'full_name' => 'معلّم تجريبي',
            'title' => 'معلّم الرياضيات',
            'is_publicly_listed' => false,
        ]);
        $teacher->user()->create([
            'email' => 'teacher@paleducation.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ])->syncRoleToShape();

        $admin = Admin::create(['full_name' => 'أدمن تجريبي']);
        $adminUser = $admin->user()->create([
            'email' => 'admin@paleducation.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $adminUser->syncRoleToShape();

        // ── إسناد مادة للمعلّم التجريبي ──────────────────────────
        // teacher_id مرجع مجال (teachers) و assigned_by مرجع فاعل (users) —
        // عمودان متشابهان شكلاً، مختلفان معنى تماماً.
        $math = Subject::where('slug', 'math')
            ->whereRelation('branch', 'slug', 'scientific')
            ->first();

        $math?->teachers()->syncWithoutDetaching([
            $teacher->id => [
                'is_primary' => true,
                'assigned_at' => now(),
                'assigned_by' => $adminUser->id,
            ],
        ]);
    }
}
