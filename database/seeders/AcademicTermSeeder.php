<?php

namespace Database\Seeders;

use App\Enums\TermStatus;
use App\Models\AcademicTerm;
use Illuminate\Database\Seeder;

/**
 * فصلا السنة الدراسية الجارية.
 *
 * 🔴 يجب أن يعمل **قبل** أي بذرة محاضرة: lectures.academic_term_id إجباري،
 * فلا محاضرة قبل فصل. هذه أشهر عثرة في هذا الترتيب.
 *
 * الفصل الأول هو الجاري ومفتوح؛ الثاني قادم لم يبدأ. makeCurrent() هي
 * الباب الوحيد لرفع علم is_current — تُنزله عن غيره في نفس المعاملة.
 */
class AcademicTermSeeder extends Seeder
{
    public function run(): void
    {
        $year = '2026/2027';

        $first = AcademicTerm::updateOrCreate(
            ['academic_year' => $year, 'name' => 'الفصل الأول'],
            [
                'starts_on' => '2026-09-01',
                'ends_on' => '2027-01-15',
                'status' => TermStatus::Active,
            ]
        );

        AcademicTerm::updateOrCreate(
            ['academic_year' => $year, 'name' => 'الفصل الثاني'],
            [
                'starts_on' => '2027-01-25',
                'ends_on' => '2027-06-10',
                'status' => TermStatus::Upcoming,
            ]
        );

        $first->makeCurrent();
    }
}
