<?php

namespace App\Http\Controllers\Student;

use App\Enums\LectureStatus;
use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\LectureProgress;
use App\Models\LibraryFile;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * تقدّم الطالب — أرقام حقيقية أو حالة فارغة، لا شيء مخترَع.
 *
 * 🔴 كل رقم من فرع الطالب حصراً. «مكتمل» يعني completed_at غير null على
 * lecture_progress — تحقّق خادمي عبر فترات تغطية (م-5)، لا علم عميل.
 */
class ProgressController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->userable;

        $branchSubjectIds = Subject::query()
            ->where('branch_id', $student->branch_id)
            ->where('is_published', true)
            ->pluck('id');

        $branchLectureIds = Lecture::query()
            ->whereIn('subject_id', $branchSubjectIds)
            ->where('status', LectureStatus::Published)
            ->pluck('id');

        $totalLectures = $branchLectureIds->count();

        // 🔴 المكتملة تُقصر على محاضرات الفرع: تقدّم قديم على محاضرة نُقلت
        // خارج الفرع لا يُحتسب.
        $completedLectures = LectureProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('lecture_id', $branchLectureIds)
            ->whereNotNull('completed_at')
            ->count();

        $inProgressLectures = LectureProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('lecture_id', $branchLectureIds)
            ->whereNull('completed_at')
            ->where('watched_seconds', '>', 0)
            ->count();

        $totalFiles = LibraryFile::query()
            ->visible()
            ->whereIn('subject_id', $branchSubjectIds)
            ->count();

        $accessedFiles = $student->libraryAccess()->count();

        $watchedSeconds = (int) LectureProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('lecture_id', $branchLectureIds)
            ->sum('watched_seconds');

        return view('pages.student.progress', [
            'hasSubjects' => $branchSubjectIds->isNotEmpty(),
            'hasActivity' => $completedLectures > 0 || $inProgressLectures > 0 || $accessedFiles > 0,

            'completedLectures' => $completedLectures,
            'inProgressLectures' => $inProgressLectures,
            'totalLectures' => $totalLectures,
            'completionRate' => $totalLectures > 0 ? (int) round($completedLectures / $totalLectures * 100) : 0,

            'accessedFiles' => $accessedFiles,
            'totalFiles' => $totalFiles,

            'watchedHours' => round($watchedSeconds / 3600, 1),

            'subjects' => $this->perSubject($student->id, $branchSubjectIds),
        ]);
    }

    /**
     * تقدّم كل مادة: مكتمل / إجمالي.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function perSubject(int $studentId, Collection $branchSubjectIds): Collection
    {
        $subjects = Subject::query()
            ->whereIn('id', $branchSubjectIds)
            ->withCount(['lectures as lectures_count' => fn ($query) => $query->where('status', LectureStatus::Published)])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'icon']);

        $completedBySubject = LectureProgress::query()
            ->where('lecture_progress.student_id', $studentId)
            ->whereNotNull('completed_at')
            ->join('lectures', 'lectures.id', '=', 'lecture_progress.lecture_id')
            ->groupBy('lectures.subject_id')
            ->selectRaw('lectures.subject_id, count(*) as aggregate')
            ->pluck('aggregate', 'lectures.subject_id');

        return $subjects->map(fn (Subject $subject) => [
            'name' => $subject->name,
            'icon' => $subject->icon ?: 'book',
            'completed' => $completedBySubject[$subject->id] ?? 0,
            'total' => $subject->lectures_count,
            'percent' => $subject->lectures_count > 0
                ? (int) round(($completedBySubject[$subject->id] ?? 0) / $subject->lectures_count * 100)
                : 0,
        ]);
    }
}
