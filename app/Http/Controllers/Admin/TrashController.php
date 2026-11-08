<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TrashManager;
use App\Support\TrashableResources;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Centralised trash.
 *
 * 🔴 Authorisation is PER TYPE, not one permission for the whole screen.
 * The route group used to require `delete-sliders` for everything, which
 * meant restoring a branch depended on a slider permission — an artefact of
 * the trash being built when sliders were the only trashable entity. Each
 * action now checks TrashableResources::permissionFor($type).
 */
class TrashController extends Controller
{
    public function __construct(private TrashManager $trash) {}

    public function index(Request $request): View
    {
        $allowed = $this->allowedTypes();

        // No trashable type at all means nothing to show and nothing to do.
        abort_if($allowed === [], 403);

        $type = $this->sanitizeAllowed($request->string('type')->toString());

        return view('admin.trash.index', [
            'items' => $this->trash->items($type, $allowed),
            'types' => TrashableResources::allowedFor($request->user()),
            'counts' => $this->trash->countsByType($allowed),
            'activeType' => $type,
            'totalCount' => $this->trash->count(null, $allowed),
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorizeType($request, $type);

        $this->trash->restore($type, $id)
            ? flash()->success('تم استعادة العنصر بنجاح.')
            : flash()->error('حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.');

        return $this->backToTrash($request);
    }

    public function forceDestroy(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorizeType($request, $type);

        $this->trash->forceDestroy($type, $id)
            ? flash()->warning('تم الحذف النهائي للعنصر.')
            : flash()->error('حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.');

        return $this->backToTrash($request);
    }

    public function restoreSelected(Request $request): RedirectResponse
    {
        $count = $this->trash->restoreMany($this->selectedTokens($request), $this->allowedTypes());

        $count > 0
            ? flash()->success("تم استعادة {$count} عنصر.")
            : flash()->info('لم يُحدَّد أي عنصر.');

        return $this->backToTrash($request);
    }

    public function forceSelected(Request $request): RedirectResponse
    {
        $count = $this->trash->forceDestroyMany($this->selectedTokens($request), $this->allowedTypes());

        $count > 0
            ? flash()->warning("تم الحذف النهائي لـ {$count} عنصر.")
            : flash()->info('لم يُحدَّد أي عنصر.');

        return $this->backToTrash($request);
    }

    public function restoreAll(Request $request): RedirectResponse
    {
        $type = $this->sanitizeAllowed($request->input('type'));
        $count = $this->trash->restoreAll($type, $this->allowedTypes());

        $count > 0
            ? flash()->success("تم استعادة {$count} عنصر.")
            : flash()->info('السلة فارغة بالفعل.');

        return $this->backToTrash($request);
    }

    public function empty(Request $request): RedirectResponse
    {
        $type = $this->sanitizeAllowed($request->input('type'));
        $count = $this->trash->empty($type, $this->allowedTypes());

        $count > 0
            ? flash()->warning("تم تفريغ السلة ({$count} عنصر).")
            : flash()->info('السلة فارغة بالفعل.');

        return $this->backToTrash($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorisation
    |--------------------------------------------------------------------------
    */

    /**
     * 404 for an unknown type, 403 for a known one the actor may not touch.
     *
     * The distinction matters: 404 says "no such thing", 403 says "not
     * yours". Collapsing them would let a probe map the registry.
     */
    private function authorizeType(Request $request, string $type): void
    {
        abort_unless(TrashableResources::has($type), 404);
        abort_unless(TrashableResources::allows($request->user(), $type), 403);
    }

    /** @return array<int, string> */
    private function allowedTypes(): array
    {
        return TrashableResources::allowedKeysFor(request()->user());
    }

    /** Keeps a filter only when it is both known AND permitted. */
    private function sanitizeAllowed(?string $type): ?string
    {
        return TrashableResources::allows(request()->user(), $type)
            ? TrashableResources::sanitize($type)
            : null;
    }

    /**
     * الرموز المحدَّدة كما أرسلها النموذج.
     *
     * ⚠️ بلا تحقّق exists هنا عمداً: الرمز مركّب (type:id) لا مفتاح جدول،
     * والتحقّق الحقيقي يقع في TrashManager الذي يتجاهل أي رمز مجهول النوع
     * أو غير رقمي المعرّف — أو من نوع لا يملك الفاعل صلاحيته — بصمت.
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
        $type = $this->sanitizeAllowed($request->input('type'));

        return redirect()->route('admin.trash.index', array_filter(['type' => $type]));
    }
}
