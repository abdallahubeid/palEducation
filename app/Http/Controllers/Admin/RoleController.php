<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount('permissions')->orderBy('id')->get(),
            'permissionCount' => Permission::query()->count(),
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => Permission::query()->orderBy('name')->get(),
            'assigned' => $role->permissions->pluck('name')->all(),
            'locked' => $this->lockedFor($role),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // المقفلة تبقى مهما أرسل النموذج — نزع manage-roles عن دور admin
        // يقفل هذه الشاشة على كل أدمن بلا عودة إلا من قاعدة البيانات.
        $permissions = array_unique([
            ...$validated['permissions'] ?? [],
            ...$this->lockedFor($role),
        ]);

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        flash()->success('تم تحديث البيانات بنجاح.');

        return redirect()->route('admin.roles.edit', $role);
    }

    public function togglePermission(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', 'exists:permissions,name'],
            'granted' => ['required', 'boolean'],
        ]);

        if (! $validated['granted'] && in_array($validated['permission'], $this->lockedFor($role), true)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => __('admin.role_permission_locked')], 422);
            }

            flash()->error('يرجى التأكد من تصحيح الأخطاء في النموذج.');

            return back();
        }

        $validated['granted']
            ? $role->givePermissionTo($validated['permission'])
            : $role->revokePermissionTo($validated['permission']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        flash()->info('تم تغيير الحالة بنجاح.');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => __('admin.role_permission_toggled')]);
        }

        return back();
    }

    private function lockedFor(Role $role): array
    {
        return $role->name === 'admin' ? RoleSeeder::LOCKED_ADMIN_PERMISSIONS : [];
    }
}
