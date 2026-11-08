<?php

namespace App\Http\Controllers\Student;

use App\Enums\LectureStatus;
use App\Http\Controllers\Controller;
use App\Models\LectureProgress;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * مواد الطالب — فرعه حصراً.
 *
 * 🔴 الفرع فلتر جذري لا خيار عرض. لا Global Scope بعد، فالفلترة يدوية في كل
 * استعلام هنا: branch_id = students.branch_id، is_published = true. مادة خارج
 * الفرع = 404 (لا 403 — «ممنوع» تؤكّد أن خلف المُعرِّف محتوى).
 *
 * ⚠️ «التسجيل المباشر» في المتطلّبات لا يُمثَّل بجدول بعد؛ النطاق الوحيد
 * الموجود هو الفرع.
 */
class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->userable;

        $subjects = Subject::query()
            ->where('branch_id', $student->branch_id)
            ->where('is_published', true)
            ->with('teachers:id,full_name')
            ->withCount(['lectures as lectures_count' => fn ($query) => $query->where('status', LectureStatus::Published)])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $completedBySubject = $this->completedCountsBySubject($student->id);

        return view('pages.student.subjects', [
            'subjects' => $subjects->map(fn (Subject $subject) => [
                'model' => $subject,
                'slug' => $subject->slug,
                'name' => $subject->name,
                'icon' => $subject->icon ?: 'book',
                'tone' => $this->tone($subject->accent_token),
                'teacher' => $subject->teachers->pluck('full_name')->join('، ') ?: '—',
                'lectures_count' => $subject->lectures_count,
                'completed' => $completedBySubject[$subject->id] ?? 0,
                'percent' => $subject->lectures_count > 0
                    ? (int) round(($completedBySubject[$subject->id] ?? 0) / $subject->lectures_count * 100)
                    : 0,
            ]),
        ]);
    }

    public function show(Request $request, string $subject): View
    {
        $student = $request->user()->userable;

        $model = Subject::query()
            ->where('slug', $subject)
            ->where('branch_id', $student->branch_id)   // 🔴 slug فريد داخل الفرع لا عالمياً
            ->where('is_published', true)
            ->with('teachers:id,full_name')
            ->firstOr(fn () => abort(404));

        $lectures = $model->lectures()
            ->where('status', LectureStatus::Published)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'unit_name', 'display_order', 'video_duration_seconds']);

        $completedIds = LectureProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('lecture_id', $lectures->pluck('id'))
            ->whereNotNull('completed_at')
            ->pluck('lecture_id')
            ->all();

        $completed = count($completedIds);

        $files = $model->libraryFiles()
            ->visible()
            ->latest('published_at')
            ->with('media:id,mediable_id,mediable_type,size_bytes,mime_type')
            ->get(['id', 'title', 'category', 'published_at', 'subject_id']);

        return view('pages.student.subject', [
            'subject' => $model,
            'teacher' => $model->teachers->pluck('full_name')->join('، ') ?: '—',
            'completed' => $completed,
            'total' => $lectures->count(),
            'files' => $files,
            'percent' => $lectures->count() > 0 ? (int) round($completed / $lectures->count() * 100) : 0,
            // مجموعة بالوحدة — محاضرة بلا وحدة تسقط في مجموعة افتراضية مسمّاة.
            'lecturesByUnit' => $lectures
                ->map(fn ($lecture) => tap($lecture)->setAttribute('is_completed', in_array($lecture->id, $completedIds, true)))
                ->groupBy(fn ($lecture) => filled($lecture->unit_name)
                    ? $lecture->unit_name
                    : __('student.lecture_default_unit')),
        ]);
    }

    /**
     * عدد المحاضرات المكتملة لهذا الطالب، مفهرساً بـsubject_id.
     *
     * @return Collection<int, int>
     */
    private function completedCountsBySubject(int $studentId): Collection
    {
        return LectureProgress::query()
            ->where('lecture_progress.student_id', $studentId)
            ->whereNotNull('completed_at')
            ->join('lectures', 'lectures.id', '=', 'lecture_progress.lecture_id')
            ->groupBy('lectures.subject_id')
            ->selectRaw('lectures.subject_id, count(*) as aggregate')
            ->pluck('aggregate', 'lectures.subject_id');
    }

    /** توكن تصميم القاعدة → نغمة subject-card، أو accent افتراضاً. */
    private function tone(?string $token): string
    {
        return in_array($token, ['accent', 'tag', 'amber', 'warn'], true) ? $token : 'accent';
    }
}
