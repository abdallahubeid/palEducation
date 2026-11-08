<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Frequently asked questions on the public home page.
 *
 * 🔴 A dedicated module rather than settings keys: a question carries an
 * order, a visibility flag and a soft delete, and the list has no fixed
 * length. Flattening it into key/value pairs would put the record boundary
 * inside the key string.
 *
 * ⚠️ The `faqs` table already existed (landing-content cluster) with
 * `display_order` and `is_active` beyond the requested question/answer pair.
 * Reused rather than duplicated — a second table would have split the same
 * content across two places.
 */
class FaqController extends Controller
{
    public function index(): View
    {
        return view('admin.faqs.index', [
            'faqs' => Faq::query()->ordered()->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('admin.faqs.create', [
            'faq' => new Faq([
                'display_order' => (int) Faq::query()->max('display_order') + 1,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Faq::query()->create($this->validated($request));

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.faqs.index');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', ['faq' => $faq]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.faqs.index');
    }

    public function toggle(Faq $faq): RedirectResponse
    {
        $faq->update(['is_active' => ! $faq->is_active]);

        flash()->info('تم تغيير الحالة بنجاح.');

        return back();
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        // Undo is a real PATCH form with CSRF in the shell, never a link in
        // the notice: a restore mutates server state.
        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'faqs', 'id' => $faq->id]),
            'label' => 'تراجع',
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            /*
             * ⚠️ Bounded to the column widths (question 255, answer text).
             * Validation looser than the column means MySQL truncates on
             * write in non-strict mode — silently, with no error.
             */
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'display_order' => $validated['display_order'] ?? 0,
            // boolean() reads an unchecked box as false rather than absent —
            // without it a question could never be hidden.
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
