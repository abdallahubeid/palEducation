<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\LibraryFile;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * سلّة محذوفات المعلّم.
 *
 * 🔴 استرجاع فقط. الحذف النهائي — يدوياً أو تلقائياً — ليس صلاحية معلّم (م-9)،
 * والحذف التلقائي بعد مدّة قرار معلّق في المشروع أصلاً (NotifyTrashPurgeWarning
 * يشرح لماذا لا يُنفَّذ بعد). فلا forceDelete ولا مهمة تنظيف هنا.
 *
 * ⚠️ النطاق على المادة لا على uploaded_by/teacher_id: المعلّم يدير سلّة مواده
 * كلها بما حذفه زميل سبقه — نفس عقد شاشتَي المحاضرات والمكتبة (scopeForTeacher).
 */
class TrashController extends Controller
{
    /**
     * الأنواع القابلة للاسترجاع من هنا.
     *
     * @var array<string, array{label: string, model: class-string<Model>, icon: string}>
     */
    private const TYPES = [
        'lectures' => ['label' => 'محاضرات', 'model' => Lecture::class, 'icon' => 'play'],
        'files' => ['label' => 'ملفات المكتبة', 'model' => LibraryFile::class, 'icon' => 'folder'],
    ];

    public function index(Request $request): View
    {
        $teacher = $request->user()->userable;

        // نوع لا نعرفه يُسقَط إلى «الكل» بدل أن يُطاع.
        $filter = array_key_exists((string) $request->query('type'), self::TYPES)
            ? $request->query('type')
            : 'all';

        $visibleTypes = $filter === 'all'
            ? self::TYPES
            : [$filter => self::TYPES[$filter]];

        $items = collect($visibleTypes)
            ->flatMap(fn (array $meta, string $token) => $meta['model']::onlyTrashed()
                ->forTeacher($teacher)
                ->with('subject')
                ->latest('deleted_at')
                ->get()
                ->map(fn (Model $record): array => [
                    'token' => $token,
                    'type_label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'id' => $record->getKey(),
                    'title' => $record->title,
                    'subject' => $record->subject?->name,
                    'deleted_at' => $record->deleted_at,
                ]))
            ->sortByDesc(fn (array $row): int => $row['deleted_at']?->timestamp ?? 0)
            ->values();

        return view('teacher.trash.index', [
            'items' => $items,
            'filter' => $filter,
            'counts' => [
                'all' => collect(self::TYPES)->sum(
                    fn (array $meta): int => $meta['model']::onlyTrashed()->forTeacher($teacher)->count()
                ),
                ...collect(self::TYPES)
                    ->map(fn (array $meta): int => $meta['model']::onlyTrashed()->forTeacher($teacher)->count())
                    ->all(),
            ],
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        // 404 لا 403: نوع مجهول لا يختلف عن مسار غير موجود.
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $teacher = $request->user()->userable;

        /*
         * 🔴 onlyTrashed()->forTeacher()->whereKey() ثم first():
         *   - إعادة الجلب إجبارية — restore() على نسخة مجلوبة قبل الحذف
         *     لا-عملية صامتة (deleted_at في الذاكرة null).
         *   - عنصر خارج مواد المعلّم = 404 لا 403: «ممنوع» تؤكّد أن المُعرِّف
         *     حقيقي وأن خلفه محتوى زميل.
         */
        $record = self::TYPES[$type]['model']::onlyTrashed()
            ->forTeacher($teacher)
            ->whereKey($id)
            ->first();

        abort_if($record === null, 404);

        $record->restore();

        flash()->success('تم استرجاع العنصر بنجاح.');

        return redirect()->route('teacher.trash', array_filter([
            'type' => array_key_exists((string) $request->input('type'), self::TYPES)
                ? $request->input('type')
                : null,
        ]));
    }
}
