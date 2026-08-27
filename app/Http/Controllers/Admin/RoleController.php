<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount(['permissions', 'users'])->orderBy('id')->get(),
            'permissionCount' => count(PermissionCatalog::names()),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'groups' => PermissionCatalog::groups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::names())],
        ], [
            'name.regex' => 'اسم الدور بالإنجليزية الصغيرة والشرطات فقط (مثال: content-editor).',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => config('auth.defaults.guard', 'web'),
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        $this->flushCache();

        flash()->success('تم إنشاء الحساب / الإضافة بنجاح.');

        return redirect()->route('admin.roles.edit', $role);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'groups' => PermissionCatalog::groups(),
            'assigned' => $role->permissions->pluck('name')->all(),
            'locked' => PermissionCatalog::lockedFor($role->name),
            'isSystem' => PermissionCatalog::isSystemRole($role->name),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $isSystem = PermissionCatalog::isSystemRole($role->name);

        $validated = $request->validate([
            // 🔴 اسم الدور النظامي غير قابل للتعديل: User::roleName() تشتقّ
            // الدور من userable_type بهذه الأسماء الثلاثة حرفياً. تغيير
            // الاسم يقطع الاشتقاق ويترك كل حساب بلا صلاحيات بصمت.
            'name' => [
                $isSystem ? 'nullable' : 'required',
                'string', 'max:60', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::names())],
        ]);

        if (! $isSystem && filled($validated['name'] ?? null)) {
            $role->update(['name' => $validated['name']]);
        }

        // المقفلة تبقى مهما أرسل النموذج — الحارس خادمي لا يعتمد على حقل مخفي.
        $role->syncPermissions(array_unique([
            ...$validated['permissions'] ?? [],
            ...PermissionCatalog::lockedFor($role->name),
        ]));

        $this->flushCache();

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.roles.edit', $role);
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (PermissionCatalog::isSystemRole($role->name)) {
            flash()->error('لا يمكن حذف الأدوار النظامية الثلاثة.');

            return back();
        }

        // دور يحمله مستخدمون: حذفه يتركهم بلا صلاحيات بصمت.
        if ($role->users()->count() > 0) {
            flash()->error('لا يمكن حذف دور مرتبط بمستخدمين. انقلهم أولاً.');

            return back();
        }

        $role->delete();

        $this->flushCache();

        flash()->warning('تم حذف العنصر بنجاح.');

        return redirect()->route('admin.roles.index');
    }

    /**
     * تبديل صلاحية واحدة — يستدعيه مفتاح Alpine بلا إعادة تحميل.
     */
    public function togglePermission(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', Rule::in(PermissionCatalog::names())],
            'granted' => ['required', 'boolean'],
        ]);

        $locked = PermissionCatalog::lockedFor($role->name);

        if (! $validated['granted'] && in_array($validated['permission'], $locked, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'لا يمكن نزع هذه الصلاحية — نزعها يُغلق شاشة الأدوار على كل أدمن.',
            ], 422);
        }

        $validated['granted']
            ? $role->givePermissionTo($validated['permission'])
            : $role->revokePermissionTo($validated['permission']);

        $this->flushCache();

        return response()->json([
            'ok' => true,
            'message' => 'تم تغيير الحالة بنجاح.',
            'count' => $role->permissions()->count(),
        ]);
    }

    /**
     * تبديل وحدة كاملة — مفتاح القسم.
     *
     * إجراء واحد بدل حلقة نداءات من المتصفّح: تشغيل قسم فيه أربع صلاحيات
     * كان سيُصدر أربعة طلبات متسابقة، وآخرها يفوز على مخزن Spatie المؤقّت.
     */
    public function toggleGroup(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', Rule::in(array_keys(PermissionCatalog::groups()))],
            'granted' => ['required', 'boolean'],
        ]);

        $groupPermissions = array_keys(PermissionCatalog::groups()[$validated['group']]['permissions']);
        $locked = PermissionCatalog::lockedFor($role->name);

        if ($validated['granted']) {
            $role->givePermissionTo($groupPermissions);
        } else {
            // المقفلة تُستثنى من الإطفاء الجماعي لا تُرفَض معه: إطفاء القسم
            // يجب أن يعمل على البقية بدل أن يفشل كاملاً بسبب صلاحية واحدة.
            $role->revokePermissionTo(array_diff($groupPermissions, $locked));
        }

        $this->flushCache();

        return response()->json([
            'ok' => true,
            'message' => 'تم تغيير الحالة بنجاح.',
            'locked' => array_values(array_intersect($groupPermissions, $locked)),
            'count' => $role->permissions()->count(),
        ]);
    }

    /**
     * ⚠️ الحزمة تُخزّن الأدوار والصلاحيات مؤقتاً. بلا تفريغ صريح يبقى
     * الطلب التالي يقرأ الخريطة القديمة، فيبدو التبديل كأنه لم يُحفظ.
     */
    private function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
