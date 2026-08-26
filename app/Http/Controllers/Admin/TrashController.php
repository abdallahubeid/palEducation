<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TrashManager;
use App\Support\TrashableResources;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function __construct(private TrashManager $trash) {}

    public function index(Request $request): View
    {
        $type = TrashableResources::sanitize($request->string('type')->toString());

        return view('admin.trash.index', [
            'items' => $this->trash->items($type),
            'types' => TrashableResources::all(),
            'counts' => $this->trash->countsByType(),
            'activeType' => $type,
            'totalCount' => $this->trash->count(),
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(TrashableResources::has($type), 404);

        $this->trash->restore($type, $id)
            ? flash()->success('تم استعادة العنصر بنجاح.')
            : flash()->error('حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.');

        return $this->backToTrash($request);
    }

    public function forceDestroy(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(TrashableResources::has($type), 404);

        $this->trash->forceDestroy($type, $id)
            ? flash()->warning('تم الحذف النهائي للعنصر.')
            : flash()->error('حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.');

        return $this->backToTrash($request);
    }

    public function restoreSelected(Request $request): RedirectResponse
    {
        $count = $this->trash->restoreMany($this->selectedTokens($request));

        $count > 0
            ? flash()->success("تم استعادة {$count} عنصر.")
            : flash()->info('لم يُحدَّد أي عنصر.');

        return $this->backToTrash($request);
    }

    public function forceSelected(Request $request): RedirectResponse
    {
        $count = $this->trash->forceDestroyMany($this->selectedTokens($request));

        $count > 0
            ? flash()->warning("تم الحذف النهائي لـ {$count} عنصر.")
            : flash()->info('لم يُحدَّد أي عنصر.');

        return $this->backToTrash($request);
    }

    public function restoreAll(Request $request): RedirectResponse
    {
        $type = TrashableResources::sanitize($request->input('type'));
        $count = $this->trash->restoreAll($type);

        $count > 0
            ? flash()->success("تم استعادة {$count} عنصر.")
            : flash()->info('السلة فارغة بالفعل.');

        return $this->backToTrash($request);
    }

    public function empty(Request $request): RedirectResponse
    {
        $type = TrashableResources::sanitize($request->input('type'));
        $count = $this->trash->empty($type);

        $count > 0
            ? flash()->warning("تم تفريغ السلة ({$count} عنصر).")
            : flash()->info('السلة فارغة بالفعل.');

        return $this->backToTrash($request);
    }

    /**
     * الرموز المحدَّدة كما أرسلها النموذج.
     *
     * ⚠️ بلا تحقّق exists هنا عمداً: الرمز مركّب (type:id) لا مفتاح جدول،
     * والتحقّق الحقيقي يقع في TrashManager الذي يتجاهل أي رمز مجهول النوع
     * أو غير رقمي المعرّف بصمت.
     *
     * @return array<int, string>
     */
    private function selectedTokens(Request $request): array
    {
        $validated = $request->validate([
            'items' => ['array'],
            'items.*' => ['string', 'max:64'],
        ]);

        return $validated['items'] ?? [];
    }

    /** يعيد للفلتر الذي كان المستخدم يقف عليه لا لأعلى السلّة. */
    private function backToTrash(Request $request): RedirectResponse
    {
        $type = TrashableResources::sanitize($request->input('type'));

        return redirect()->route('admin.trash.index', array_filter(['type' => $type]));
    }
}
