<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    public function index(): View
    {
        return view('admin.sliders.index', [
            'sliders' => Slider::query()->with('image')->ordered()->paginate(15),
            // المحذوفات لم تعد تُعرَض هنا — السلّة مركزية تحت /admin/trash.
        ]);
    }

    public function create(): View
    {
        return view('admin.sliders.create', [
            'slider' => new Slider([
                'display_order' => (int) Slider::query()->max('display_order') + 1,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'badge_text' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:200'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'btn1_text' => ['nullable', 'required_with:btn1_link', 'string', 'max:80'],
            'btn1_link' => ['nullable', 'required_with:btn1_text', 'string', 'max:500'],
            'btn2_text' => ['nullable', 'required_with:btn2_link', 'string', 'max:80'],
            'btn2_link' => ['nullable', 'required_with:btn2_text', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:4096'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $slider = Slider::query()->create([
            'badge_text' => $validated['badge_text'] ?? null,
            'title' => $validated['title'],
            'sub_title' => $validated['sub_title'] ?? null,
            'btn1_text' => $validated['btn1_text'] ?? null,
            'btn1_link' => $validated['btn1_link'] ?? null,
            'btn2_text' => $validated['btn2_text'] ?? null,
            'btn2_link' => $validated['btn2_link'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncImage($request, $slider);

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.sliders.index');
    }

    public function edit(Slider $slider): View
    {
        $slider->load('image');

        return view('admin.sliders.edit', ['slider' => $slider]);
    }

    public function update(Request $request, Slider $slider): RedirectResponse
    {
        $validated = $request->validate([
            'badge_text' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:200'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'btn1_text' => ['nullable', 'required_with:btn1_link', 'string', 'max:80'],
            'btn1_link' => ['nullable', 'required_with:btn1_text', 'string', 'max:500'],
            'btn2_text' => ['nullable', 'required_with:btn2_link', 'string', 'max:80'],
            'btn2_link' => ['nullable', 'required_with:btn2_text', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:4096'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $slider->update([
            'badge_text' => $validated['badge_text'] ?? null,
            'title' => $validated['title'],
            'sub_title' => $validated['sub_title'] ?? null,
            'btn1_text' => $validated['btn1_text'] ?? null,
            'btn1_link' => $validated['btn1_link'] ?? null,
            'btn2_text' => $validated['btn2_text'] ?? null,
            'btn2_link' => $validated['btn2_link'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncImage($request, $slider);

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.sliders.index');
    }

    public function toggle(Slider $slider): RedirectResponse
    {
        $slider->update(['is_active' => ! $slider->is_active]);

        flash()->info('تم تغيير الحالة بنجاح.');

        return back();
    }

    public function destroy(Slider $slider): RedirectResponse
    {
        $slider->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        // زرّ «تراجع» نموذج PATCH بـCSRF تعرضه القشرة، لا رابطاً داخل توست:
        // الاستعادة تغيّر حالة الخادم، ومسار GET يُنفَّذ بجالب مسبق أو بزاحف.
        // ويشير الآن إلى السلّة المركزية بعد نقل الاستعادة إليها.
        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'sliders', 'id' => $slider->id]),
            'label' => 'تراجع',
        ]);

        return back();
    }

    private function syncImage(Request $request, Slider $slider): void
    {
        /** @var UploadedFile|null $file */
        $file = $request->file('image');

        if ($file === null) {
            $slider->image?->update(['alt_text' => $request->input('alt_text')]);

            return;
        }

        $previous = $slider->image?->path;

        $path = $file->store('sliders', 'public');

        $slider->media()->updateOrCreate([], [
            'path' => 'storage/'.$path,
            'type' => 'image',
            'alt_text' => $request->input('alt_text'),
            'display_order' => 1,
        ]);

        $this->deleteFile($previous);
    }

    private function deleteFile(?string $path): void
    {
        // البادئة شرط: الأصول المبذورة تحت public/images ملفات مستودع،
        // حذفها يكسر البذرة في كل بيئة ولا تُستعاد من سلّة المحذوفات.
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $path));
        }
    }
}
