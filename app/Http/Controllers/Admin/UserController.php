<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * حسابات المنصّة — طلبة ومعلّمون وأدمن.
 *
 * 🔴 الهوية هنا **متعدّدة الأشكال**: كل حساب يحلّ إلى سجلّ مجال واحد
 * (Student · Teacher · Admin) يحمل الاسم، و userable_id إجباري. لذلك
 * إنشاء مستخدم = إنشاء صفّين في معاملة واحدة، والنوع يُختار عند الإنشاء
 * **ولا يُغيَّر بعده**: تغييره يعني نقل الاسم والفرع والمحتوى بين جداول
 * مختلفة، وهو ترحيل بيانات لا تعديل حقل.
 */
class UserController extends Controller
{
    /** النوع ⇐ [التسمية، صنف سجلّ المجال]. */
    private const TYPES = [
        'student' => ['label' => 'طالب', 'model' => Student::class],
        'teacher' => ['label' => 'معلّم', 'model' => Teacher::class],
        'admin' => ['label' => 'أدمن', 'model' => Admin::class],
    ];

    public function index(Request $request): View
    {
        $type = array_key_exists($request->query('type'), self::TYPES)
            ? $request->query('type')
            : null;

        $users = User::query()
            // with('userable') لا حلقة على العلاقة: بلا التحميل المسبق يصير
            // كل صفّ استعلاماً منفصلاً على جدول مجاله.
            ->with(['userable', 'roles'])
            ->when($type, fn ($q) => $q->where('userable_type', self::TYPES[$type]['model']))
            ->latest()
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'types' => self::TYPES,
            'activeType' => $type,
            'counts' => collect(self::TYPES)->map(
                fn (array $t): int => User::query()->where('userable_type', $t['model'])->count()
            )->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'types' => self::TYPES,
            'branches' => Branch::query()->orderBy('display_order')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            // 🔴 الفرع إجباري للطالب وحده: students.branch_id عمود إجباري
            // بمفتاح أجنبي، ولا فرع للمعلّم ولا للأدمن.
            'branch_id' => ['required_if:type,student', 'nullable', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $model = self::TYPES[$validated['type']]['model'];

            // سجلّ المجال أولاً ثم الحساب — لأن userable_id إجباري.
            $domain = $model::create(array_filter([
                'full_name' => $validated['full_name'],
                'branch_id' => $validated['type'] === 'student' ? $validated['branch_id'] : null,
            ], fn ($value) => $value !== null));

            $domain->user()->create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),   // ينشئه أدمن، فلا توثيق مطلوب
                'is_active' => $request->boolean('is_active', true),
                'locale' => app()->getLocale(),
                // الدور يُسنَد داخل نفس المعاملة: حساب بلا دور لا يشتكي —
                // يعمل ثم يسقط عند أول فحص صلاحية. (الثابت 4)
            ])->syncRoleToShape();
        });

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('userable'),
            'types' => self::TYPES,
            'branches' => Branch::query()->orderBy('display_order')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            // كلمة المرور اختيارية: تركها فارغة يعني «أبقِ الحالية».
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request, $user) {
            $user->userable->update(array_filter([
                'full_name' => $validated['full_name'],
                'branch_id' => $user->isStudent() ? $validated['branch_id'] : null,
            ], fn ($value) => $value !== null));

            $payload = [
                'email' => $validated['email'],
                'is_active' => $request->boolean('is_active'),
            ];

            if (filled($validated['password'] ?? null)) {
                $payload['password'] = Hash::make($validated['password']);
            }

            $user->update($payload);
        });

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.users.index');
    }

    /** تبديل التفعيل — الحظر ليس حذفاً، وهما عمودان منفصلان. */
    public function toggle(User $user): RedirectResponse
    {
        if ($this->isSelf($user)) {
            flash()->error('لا يمكنك تعطيل حسابك الحالي.');

            return back();
        }

        $user->update(['is_active' => ! $user->is_active]);

        flash()->info('تم تغيير الحالة بنجاح.');

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($this->isSelf($user)) {
            flash()->error('لا يمكنك حذف حسابك الحالي.');

            return back();
        }

        if ($user->isAdmin() && $this->activeAdminCount() <= 1) {
            flash()->error('لا يمكن حذف آخر حساب أدمن.');

            return back();
        }

        // 🔴 حذف ناعم للاثنين معاً: حساب حيّ يشير إلى سجلّ مجال محذوف يعني
        // حساباً يستطيع الدخول إلى فراغ. المعاملة تُبقي الزوج متّسقاً.
        DB::transaction(function () use ($user) {
            $user->userable?->delete();
            $user->delete();
        });

        flash()->warning('تم حذف العنصر بنجاح.');

        return redirect()->route('admin.users.index');
    }

    private function isSelf(User $user): bool
    {
        return $user->is(auth()->user());
    }

    private function activeAdminCount(): int
    {
        return User::query()->where('userable_type', Admin::class)->count();
    }
}
