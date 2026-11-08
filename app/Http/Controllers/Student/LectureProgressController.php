<?php

namespace App\Http\Controllers\Student;

use App\Enums\LectureStatus;
use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\LectureProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * نبضة تقدّم المشاهدة — م-5.
 *
 * 🔴 الجسم يحمل المقطع المُشاهَد منذ آخر نبضة فقط: [from,to] بالثواني. لا
 * حقل «اكتمل» ولا «الموضع الحالي» — الخادم يدمج المقاطع ويقرّر الاكتمال من
 * مجموع الثواني الفريدة مقابل عتبة المحاضرة.
 *
 * ⚠️ نفس فحص النطاق في LectureViewController: محاضرة مسودّة أو خارج فرع
 * الطالب = 404 لا 403.
 */
class LectureProgressController extends Controller
{
    public function heartbeat(Request $request, Lecture $lecture): JsonResponse
    {
        $student = $request->user()->userable;

        $lecture->loadMissing('subject');

        // 🔴 لا Policy بعد — الفحص يدوي وصارم هنا كما في LectureViewController.
        if ($lecture->status !== LectureStatus::Published
            || $lecture->subject?->branch_id !== $student->branch_id) {
            throw new NotFoundHttpException;
        }

        $validated = $request->validate([
            'from' => ['required', 'integer', 'min:0', 'max:360000'],
            'to' => ['required', 'integer', 'gt:from', 'max:360000'],
        ]);

        $progress = LectureProgress::firstOrNew([
            'student_id' => $student->id,
            'lecture_id' => $lecture->id,
        ]);

        $progress->recordSegment($validated['from'], $validated['to'], $lecture);

        return response()->json([
            'watched_seconds' => $progress->watched_seconds,
            'percent' => $progress->percent(),
            'completed' => $progress->isComplete(),
        ]);
    }
}
