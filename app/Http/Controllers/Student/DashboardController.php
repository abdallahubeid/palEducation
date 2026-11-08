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

/**
 * لوحة الطالب — نظرة سريعة، كلّها من فرعه.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $student = $user->userable;

        $branchSubjectIds = Subject::query()
            ->where('branch_id', $student->branch_id)
            ->where('is_published', true)
            ->pluck('id');

        $branchLectureIds = Lecture::query()
            ->whereIn('subject_id', $branchSubjectIds)
            ->where('status', LectureStatus::Published)
            ->pluck('id');

        $completed = LectureProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('lecture_id', $branchLectureIds)
            ->whereNotNull('completed_at')
            ->count();

        $downloads = (int) $student->libraryAccess()->sum('download_count');

        return view('pages.student.dashboard', [
            'studentName' => $user->displayName(),
            'branchName' => $student->branch?->name,

            'subjectsCount' => $branchSubjectIds->count(),
            'lecturesCompleted' => $completed,
            'lecturesTotal' => $branchLectureIds->count(),
            'completionRate' => $branchLectureIds->count() > 0
                ? (int) round($completed / $branchLectureIds->count() * 100)
                : 0,
            'downloadsCount' => $downloads,

            // آخر ما نُشر في مكتبة الفرع.
            'recentFiles' => LibraryFile::query()
                ->visible()
                ->whereIn('subject_id', $branchSubjectIds)
                ->with('subject:id,name')
                ->latest('published_at')
                ->limit(5)
                ->get(['id', 'title', 'subject_id', 'published_at']),

            // 🔴 إعلانات النظام — إشعارات الطالب الحقيقية (StudentNotifier).
            'announcements' => $user->notifications()
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
