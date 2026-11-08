<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LibraryFile;
use App\Models\StudentLibraryAccess;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * مكتبة الطالب — ملفات فرعه المنشورة.
 *
 * 🔴 النطاق الجذري = فرع الطالب: subject.branch_id = students.branch_id، مع
 * visible() (منشور وحلّ تاريخه). لا Global Scope بعد، فالفلترة يدوية.
 *
 * 🔴 التسليم عبر رابط موقَّع قصير (10 د) لا رابط ملف مباشر. الملف على القرص
 * الخاص (private)، ويُسلَّم response()->file بعد فحص التوقيع **والفرع** معاً —
 * توقيع مسرَّب لا يكفي لطالب فرع آخر. مسار المعلّم (teacher.library.download)
 * لا يُعاد استعماله: ذاك يفحص المادة المسندة، وهذا يفحص الفرع.
 *
 * ⚠️ بلا فحص اشتراك: لا جدول subscriptions بعد. هذا موضع الجدار المالي
 * المستقبلي — نفس نصّ LectureViewController.
 */
class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->userable;

        $branchSubjectIds = Subject::query()
            ->where('branch_id', $student->branch_id)
            ->pluck('id');

        // مادة خارج الفرع تُسقَط بدل أن تُطاع.
        $subjectId = $request->integer('subject') ?: null;
        if ($subjectId !== null && ! $branchSubjectIds->contains($subjectId)) {
            $subjectId = null;
        }

        $search = $request->query('search');

        $files = LibraryFile::query()
            ->visible()
            ->whereIn('subject_id', $branchSubjectIds)
            ->with(['subject:id,name', 'media'])
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when(filled($search), fn ($query) => $query->where('title', 'like', '%'.trim($search).'%'))
            ->latest('published_at')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('pages.student.library', [
            'files' => $files,
            'subjects' => Subject::query()
                ->whereIn('id', $branchSubjectIds)
                ->where('is_published', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => ['subject' => $subjectId, 'search' => $search],
            // معرّفات ما وصل إليه هذا الطالب — لشارة «نُزّل».
            'accessedIds' => $student->libraryAccess()->pluck('library_file_id')->all(),
        ]);
    }

    public function download(Request $request, LibraryFile $file): BinaryFileResponse
    {
        $student = $request->user()->userable;

        $file->loadMissing('subject', 'media');

        // 🔴 فحص الفرع فوق فحص التوقيع: توقيع مسرَّب لا يخترق فرعاً آخر.
        abort_unless(
            $file->is_published
                && ($file->published_at === null || $file->published_at->isPast())
                && $file->subject?->branch_id === $student->branch_id,
            404,
        );

        $media = $file->media;

        abort_if($media === null || ! Storage::disk($media->disk)->exists($media->path), 404);

        // العدّاد الكلّي على الملف + سجلّ «مَن ومتى» لهذا الطالب.
        $file->increment('download_count');

        $access = StudentLibraryAccess::firstOrNew([
            'student_id' => $student->id,
            'library_file_id' => $file->id,
        ]);
        $access->download_count = $access->download_count + 1;
        $access->first_accessed_at ??= now();
        $access->last_accessed_at = now();
        $access->save();

        return response()->file(
            Storage::disk($media->disk)->path($media->path),
            ['Content-Disposition' => 'inline; filename="'.$media->original_filename.'"'],
        );
    }
}
