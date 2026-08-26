<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\TrashController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات لوحة الأدمن
|--------------------------------------------------------------------------
| مسجَّل في bootstrap/app.php عبر then() ضمن مجموعة web.
|
| 🔴 الحراسة في المسار لا في الواجهة:
|   auth        → مسجّل دخول
|   role:admin  → شكل الحساب
|   can:*       → الصلاحية الدقيقة لكل إجراء
|
| ⚠️ إخفاء الرابط ليس حراسة. كل إجراء محروس هنا، فطلب مباشر بـPOST على
| مسار لا يملك المستخدم صلاحيته يُرفض 403 لا يُنفَّذ.
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ── لوحة التحكّم ────────────────────────────────────────
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('can:view-sliders')
            ->name('dashboard');

        // ── الملف الشخصي ────────────────────────────────────────
        // بلا can: — كل أدمن يملك ملفه الشخصي بحكم كونه مسجّل دخول.
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.password');

        /*
        |----------------------------------------------------------------
        | الهيرو
        |----------------------------------------------------------------
        | 🔴 سلّة المحذوفات لم تعد هنا: صارت مركزية تحت /admin/trash تجمع كل
        | الكيانات القابلة للحذف الناعم. سلّة لكل قسم تعني أن الأدمن يبحث في
        | أربعة أماكن عن عنصر واحد حذفه بالخطأ.
        */
        Route::middleware('can:view-sliders')->group(function () {
            Route::get('sliders', [SliderController::class, 'index'])->name('sliders.index');
        });

        Route::middleware('can:create-sliders')->group(function () {
            Route::get('sliders/create', [SliderController::class, 'create'])->name('sliders.create');
            Route::post('sliders', [SliderController::class, 'store'])->name('sliders.store');
        });

        Route::middleware('can:edit-sliders')->group(function () {
            Route::get('sliders/{slider}/edit', [SliderController::class, 'edit'])->name('sliders.edit');
            Route::put('sliders/{slider}', [SliderController::class, 'update'])->name('sliders.update');
            Route::patch('sliders/{slider}/toggle', [SliderController::class, 'toggle'])->name('sliders.toggle');
        });

        Route::middleware('can:delete-sliders')->group(function () {
            Route::delete('sliders/{slider}', [SliderController::class, 'destroy'])->name('sliders.destroy');
        });

        /*
        |----------------------------------------------------------------
        | سلّة المحذوفات المركزية
        |----------------------------------------------------------------
        | 🔴 الاستعادة PATCH والحذف النهائي DELETE — لا GET لأيّهما: كلاهما
        | يغيّر حالة الخادم، ومسار GET يُنفَّذ بجالب مسبق في المتصفّح أو
        | بزاحف بلا نيّة من المستخدم.
        |
        | ⚠️ مسارات الإجراءات الجماعية تسبق {type}/{id} عمداً: لولا ذلك
        | التقط المسار المتغيّر كلمة «restore-selected» كنوع وأرجع 404.
        */
        Route::middleware('can:delete-sliders')->prefix('trash')->name('trash.')->group(function () {
            Route::get('/', [TrashController::class, 'index'])->name('index');

            Route::patch('restore-selected', [TrashController::class, 'restoreSelected'])->name('restore-selected');
            Route::patch('restore-all', [TrashController::class, 'restoreAll'])->name('restore-all');
            Route::delete('force-selected', [TrashController::class, 'forceSelected'])->name('force-selected');
            Route::delete('empty', [TrashController::class, 'empty'])->name('empty');

            Route::patch('{type}/{id}/restore', [TrashController::class, 'restore'])->name('restore');
            Route::delete('{type}/{id}/force', [TrashController::class, 'forceDestroy'])->name('force-destroy');
        });

        /*
        |----------------------------------------------------------------
        | الإعدادات
        |----------------------------------------------------------------
        */
        Route::middleware('can:manage-settings')->prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::get('navbar', [SettingController::class, 'navbar'])->name('navbar');
            Route::put('navbar', [SettingController::class, 'updateNavbar'])->name('navbar.update');
        });

        /*
        |----------------------------------------------------------------
        | الأدوار والصلاحيات
        |----------------------------------------------------------------
        | الأدوار الثلاثة ثابتة (تقابل جداول المجال) — لا إنشاء ولا حذف.
        */
        Route::middleware('can:manage-roles')->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('{role}', [RoleController::class, 'update'])->name('update');
            Route::patch('{role}/permissions', [RoleController::class, 'togglePermission'])
                ->name('permissions.toggle');
        });
    });
