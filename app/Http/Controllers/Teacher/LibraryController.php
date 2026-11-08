<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\LibraryCategory;
use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\LibraryFile;
use App\Models\Teacher;
use App\Notifications\LibraryFileDownloadedNotification;
use App\Support\TeacherNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * مكتبة المعلّم.
 *
 * 🔴 الملف نفسه على القرص **الخاص**، وبياناته في `media` بقرص معلَن. لا
 * Storage::url() ولا مسار تحت public/: التسليم يمرّ من هنا بعد فحص أن
 * الملف يخصّ مادة مسندة لهذا المعلّم.
 *
 * ⚠️ النطاق على المادة لا على uploaded_by — المعلّم يدير ملفات مادته كلها
 * بما رفعه زميل سبقه، تماماً كالمحاضرات.
 */
class LibraryController extends Controller
{
    /** ⚠️ يُفحص على الخادم: قيود الواجهة ادّعاء عميل. */
    private const MAX_KILOBYTES = 20480;

    private const ALLOWED = 'pdf,doc,docx,ppt,pptx,zip';

    public function index(Request $request): View
    {
        $teacher = $request->user()->userable;

        $subjects = $teacher->subjects()->with('branch')->orderBy('name')->get();

        $subjectId = $request->integer('subject_id') ?: null;

        // مادة لا يملكها تُسقَط بدل أن تُطاع: سلسلة استعلام محرَّرة يجب ألّا
        // تصير طريقة لاستكشاف المعرّفات الموجودة.
        if ($subjectId !== null && ! $subjects->contains('id', $subjectId)) {
            $subjectId = null;
        }

        $category = in_array($request->query('category'), array_keys(LibraryCategory::options()), true)
            ? $request->query('category')
            : 'all';

        $search = $request->query('search');

        return view('teacher.library.index', [
            'files' => LibraryFile::query()
                ->forTeacher($teacher)
                /*
                 * ⚠️ media محمّلة مسبقاً: الحجم والنوع يُقرآن لكل صفّ، وبدونها
                 * يصير الجدول استعلاماً لكل ملف — والصفحة تعرض خمسة عشر.
                 */
                ->with(['subject', 'lecture', 'media'])
                ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
                ->when($category !== 'all', fn ($query) => $query->where('category', $category))
                ->when(filled($search), fn ($query) => $query->where('title', 'like', '%'.trim($search).'%'))
                ->latest('created_at')
                ->paginate(config('app.paginate_page'))
                ->withQueryString(),

            'subjects' => $subjects,
            'categories' => LibraryCategory::options(),
            'filters' => ['subject_id' => $subjectId, 'category' => $category, 'search' => $search],

            // اقتراحات الوحدة من محاضرات هذا المعلّم — نفس نمط شاشة المحاضرات.
            'knownUnits' => Lecture::query()
                ->forTeacher($teacher)
                ->whereNotNull('unit_name')
                ->distinct()
                ->orderBy('unit_name')
                ->pluck('unit_name'),

            // ربط اختياري بمحاضرة — من مواد هذا المعلّم وحدها.
            'lectures' => Lecture::query()
                ->forTeacher($teacher)
                ->orderBy('subject_id')
                ->orderBy('display_order')
                ->get(['id', 'subject_id', 'title', 'unit_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $request->user()->userable;

        $data = $this->validated($request, $teacher, fileRequired: true);

        DB::transaction(function () use ($request, $teacher, $data): void {
            $file = LibraryFile::create([
                ...$data['attributes'],
                'teacher_id' => $teacher->id,
                'uploaded_by' => $request->user()->id,
            ]);

            // ⚠️ بعد الإنشاء: صفّ morph يحتاج مُعرِّف أبيه.
            $this->storeMedia($file, $request->file('file'));
        });

        flash()->success('تم رفع الملف بنجاح.');

        return redirect()->route('teacher.library', $this->filterState($request));
    }

    public function update(Request $request, LibraryFile $library): RedirectResponse
    {
        $teacher = $request->user()->userable;

        $this->authorizeFile($library, $teacher);

        // ⚠️ الملف اختياري عند التعديل: النموذج يُرسَل غالباً لتصحيح عنوان.
        $data = $this->validated($request, $teacher, fileRequired: false);

        DB::transaction(function () use ($request, $library, $data): void {
            $library->update($data['attributes']);

            if ($request->hasFile('file')) {
                $this->storeMedia($library, $request->file('file'));
            }
        });

        flash()->success('تم تعديل الملف بنجاح.');

        return redirect()->route('teacher.library', $this->filterState($request));
    }

    public function destroy(Request $request, LibraryFile $library): RedirectResponse
    {
        $this->authorizeFile($library, $request->user()->userable);

        /*
         * 🔴 حذف ناعم للسجلّ، **والملف يبقى على القرص**. الاسترجاع من السلّة
         * يجب أن يعيد ملفاً قابلاً للفتح لا سجلّاً يشير إلى لا شيء؛ ومسح
         * القرص يحدث عند الحذف النهائي عبر TrashManager::purgeMedia.
         */
        $library->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        return redirect()->route('teacher.library', $this->filterState($request));
    }

    /**
     * تسليم الملف للمعلّم.
     *
     * 🔴 المسار الوحيد الذي يخرج ملفاً من القرص الخاص في هذه الشاشة، وهو
     * يفحص الملكية أولاً. بدونه المكتبة واجهة تعرض ملفات لا يفتحها أحد.
     *
     * ⚠️ هذا تسليم **للمعلّم** فقط. مسار الطالب (فرع + اشتراك) شاشة أخرى
     * لم تُبنَ بعد، ولا يجوز أن يُعاد استعمال هذا المسار لها.
     */
    public function download(Request $request, LibraryFile $library): BinaryFileResponse
    {
        $this->authorizeFile($library, $request->user()->userable);

        $media = $library->media;

        abort_if($media === null || ! Storage::disk($media->disk)->exists($media->path), 404);

        // ⚠️ العدّاد يزيد على التنزيل لا على العرض — خلطهما يجعل الرقم بلا معنى.
        $library->increment('download_count');

        /*
         * ⚠️ صاحب الملف يُخطَر — والمُنزِّل مُستثنى، فتنزيل المعلّم لملفه لا
         * يُصدر شيئاً. اليوم مسار التنزيل للمعلّم حصراً فيبقى صامتاً؛ موصول
         * الآن ليبدأ العمل تلقائياً يوم يُبنى تسليم الطالب.
         */
        if ($library->teacher) {
            TeacherNotifier::notify(
                $library->teacher,
                new LibraryFileDownloadedNotification($library->title),
                except: $request->user(),
            );
        }

        return response()->file(
            Storage::disk($media->disk)->path($media->path),
            ['Content-Disposition' => 'inline; filename="'.$media->original_filename.'"'],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | مساعدات
    |--------------------------------------------------------------------------
    */

    /**
     * 🔴 404 لا 403: ملف خارج مواد هذا المعلّم لا يختلف عن ملف غير موجود
     * من وجهة نظره، و«ممنوع» تؤكّد أن المُعرِّف حقيقي وأن خلفه محتوى.
     */
    private function authorizeFile(LibraryFile $file, Teacher $teacher): void
    {
        abort_unless(
            $teacher->subjects()->whereKey($file->subject_id)->exists(),
            404
        );
    }

    /** @return array{attributes: array<string, mixed>} */
    private function validated(Request $request, Teacher $teacher, bool $fileRequired): array
    {
        $subjectIds = $teacher->subjects()->pluck('subjects.id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            /*
             * 🔴 مقصور على مواد هذا المعلّم لا exists: قاعدة وجود عادية كانت
             * تسمح بدفع ملف إلى مادة زميل — أو إلى فرع آخر، وهو الطريق الذي
             * يصل به المحتوى إلى الطلبة الخطأ.
             */
            'subject_id' => ['required', Rule::in($subjectIds)],
            'category' => ['required', Rule::enum(LibraryCategory::class)],
            'unit_name' => ['nullable', 'string', 'max:160'],
            'page_count' => ['nullable', 'integer', 'min:1', 'max:5000'],
            /*
             * ⚠️ المحاضرة اختيارية، لكنها إن وُجدت فمن مواد هذا المعلّم —
             * وتُفحص لاحقاً أنها من المادة نفسها.
             */
            'lecture_id' => ['nullable', Rule::exists('lectures', 'id')->whereNull('deleted_at')],
            'is_published' => ['sometimes', 'boolean'],
            'file' => [
                $fileRequired ? 'required' : 'nullable',
                'file',
                'mimes:'.self::ALLOWED,
                'max:'.self::MAX_KILOBYTES,
            ],
        ], [
            'subject_id.in' => 'اختر مادة من موادك المسندة.',
            'file.mimes' => 'الصيغ المقبولة: PDF · Word · PowerPoint · ZIP.',
            'file.max' => 'حجم الملف يتجاوز 20 ميغابايت.',
        ]);

        $lectureId = $validated['lecture_id'] ?? null;

        /*
         * 🔴 المحاضرة يجب أن تكون من **نفس المادة**. ربط ملف رياضيات بمحاضرة
         * فيزياء يجعله يرث وحدة لا علاقة لها به، ويظهر في تنقّل مادة أخرى.
         */
        if ($lectureId !== null) {
            $belongs = Lecture::whereKey($lectureId)
                ->where('subject_id', $validated['subject_id'])
                ->exists();

            if (! $belongs) {
                $lectureId = null;
            }
        }

        $published = $request->boolean('is_published');

        return [
            'attributes' => [
                'title' => $validated['title'],
                'subject_id' => (int) $validated['subject_id'],
                'lecture_id' => $lectureId,
                'category' => $validated['category'],
                'unit_name' => filled($validated['unit_name'] ?? null)
                    // مسافة زائدة تُنتج وحدة ثانية تبدو مطابقة للأولى.
                    ? trim($validated['unit_name'])
                    : null,
                'page_count' => $validated['page_count'] ?? null,
                'is_published' => $published,
                'published_at' => $published ? now() : null,
            ],
        ];
    }

    /**
     * يخزّن الملف ويكتب صفّ media، ويحذف السابق.
     *
     * 🔴 القرص `private` صراحةً: هذا محتوى مدفوع، ورابط مباشر إليه تسريب
     * مجاني. وصفّ media يحمل القرص معه فيرفض Media::url() بناء رابط عام له.
     */
    private function storeMedia(LibraryFile $file, UploadedFile $upload): void
    {
        $previous = $file->media;

        $path = $upload->store('library/'.$file->subject_id, 'private');

        $file->media()->updateOrCreate([], [
            'path' => $path,
            'disk' => 'private',
            'type' => 'document',
            'original_filename' => $upload->getClientOriginalName(),
            'mime_type' => $upload->getClientMimeType(),
            'size_bytes' => $upload->getSize(),
            'display_order' => 1,
        ]);

        // ⚠️ الملف القديم يُمسح بعد نجاح الجديد — لا قبله.
        if ($previous && $previous->path !== $path) {
            Storage::disk($previous->disk)->delete($previous->path);
        }
    }

    /** يُبقي المعلّم على القائمة التي كان يصفّيها. */
    private function filterState(Request $request): array
    {
        return array_filter([
            'subject_id' => $request->integer('filter_subject_id') ?: null,
            'category' => $request->query('filter_category'),
            'search' => $request->query('filter_search'),
        ]);
    }
}
