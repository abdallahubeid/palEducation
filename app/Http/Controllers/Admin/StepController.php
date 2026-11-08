<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Step;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * خطوات «آلية العمل» — إضافة وتعديل وحذف.
 *
 * 🔴 بلا index ولا create ولا edit: الخطوات تُدار داخل لوح «آلية العمل»
 * في صفحة الإعدادات لا في شاشة مستقلّة. ثلاثة حقول قصيرة لا تبرّر
 * شاشتين وشريطاً جانبياً، وفصلها عن ترويستها كان سيُفرّق نصف القسم
 * عن نصفه.
 *
 * ⚠️ الحذف ناعم (Step تستعمل SoftDeletes) لكن الخطوات **ليست مسجَّلة في
 * سلّة المحذوفات المركزية بعد** — إدراجها صفّ واحد في TrashableResources،
 * لكنه يحتاج قراراً بأي صلاحية تحرسها. لم يُتَّخذ بعد.
 */
class StepController extends Controller
{
    /** مفتاح نموذج الإضافة — صفّ لا يملك معرّفاً بعد. */
    private const NEW_KEY = 'new';

    public function store(Request $request): RedirectResponse
    {
        Step::create($this->validated($request, self::NEW_KEY));

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return $this->backToHow();
    }

    public function update(Request $request, Step $step): RedirectResponse
    {
        $step->update($this->validated($request, (string) $step->id));

        flash()->success('تم تحديث البيانات بنجاح.');

        return $this->backToHow();
    }

    public function destroy(Step $step): RedirectResponse
    {
        $step->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        return $this->backToHow();
    }

    /*
    |--------------------------------------------------------------------------
    | مساعدات
    |--------------------------------------------------------------------------
    */

    /**
     * 🔴 الحقول تحت steps[<key>][...] — key هو معرّف الصفّ أو 'new'.
     * الصفحة تعرض نموذج تعديل لكل خطوة ونموذج إضافة، و`old()` نطاقها
     * الطلب كلّه: باسم مسطّح، فشل تحقّق في خطوة كان يملأ الخطوات الثلاث
     * جميعاً بنفس المُدخَل ويرسم الخطأ تحتها كلّها.
     *
     * ⚠️ الحدود مأخوذة من الأعمدة نفسها (title 200 · sub_title 300):
     * تحقّق أوسع من العمود يُنتج قصّاً صامتاً في MySQL غير الصارم، أو
     * استثناءً وقت الطلب في الصارم.
     *
     * icon وis_active خارج النموذج عمداً: القسم يُعرَض برقم لا بأيقونة،
     * والعمودان يبقيان على قيمتيهما الافتراضيتين.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $key): array
    {
        $validator = Validator::make($request->all(), [
            "steps.$key.title" => ['required', 'string', 'max:200'],
            "steps.$key.sub_title" => ['nullable', 'string', 'max:300'],
            // unsignedSmallInteger — والسقف حدّ العمود لا رقم اعتباطي.
            "steps.$key.display_order" => ['required', 'integer', 'min:0', 'max:65535'],
        ], [
            "steps.$key.title.required" => 'عنوان الخطوة مطلوب.',
            "steps.$key.display_order.required" => 'ترتيب الخطوة مطلوب.',
        ]);

        if ($validator->fails()) {
            // 🔴 الرجوع إلى لوح «آلية العمل» صراحةً: back() تتبع ترويسة
            // referer، فتهبط الأخطاء على لوح مخفيّ إن غابت.
            throw (new ValidationException($validator))->redirectTo($this->howUrl());
        }

        return data_get($validator->validated(), "steps.$key", []);
    }

    private function backToHow(): RedirectResponse
    {
        return redirect()->to($this->howUrl());
    }

    private function howUrl(): string
    {
        return route('admin.settings.index', ['tab' => 'how']);
    }
}
