<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // مسارات المصادقة ولوحة الأدمن في ملفّين مستقلّين، ضمن نفس
            // مجموعة web — الحراسة معلَنة داخل كل ملف لا هنا.
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('auth.login'));

        // 🔴 spatie/laravel-permission لا يسجّل هذه الأسماء تلقائياً — تُعلَن
        // هنا صراحةً. بدونها يفشل أي مسار يستعمل `role:` بـBadMethodCall
        // وقت الطلب لا وقت البناء، أي أن الحراسة تسقط بلا إنذار مبكر.
        // (`can:` مدمج في Laravel ويعمل مع Spatie لأنها تسجّل قدرات Gate.)
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         | صفّان من جدول الإشعارات يعيشان هنا لا في الكنترولرز: خطأ التحقّق
         | يُرمى قبل أن يصل أي سطر في الكنترولر، والاستثناء غير المتوقّع قد
         | يقع في أي منها. مكان واحد بدل try/catch في كل دالة.
         |
         | مقصور على مسارات admin: نماذج الطالب والزائر لها رسائلها الحقلية.
         */
        $exceptions->reportable(function (Throwable $e) {
            if (request()->is('admin/*') && ! request()->expectsJson()) {
                flash()->error('حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.');
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('admin/*') && ! $request->expectsJson()) {
                flash()->error('يرجى التأكد من تصحيح الأخطاء في النموذج.');
            }

            return null;   // يكمل السلوك الافتراضي: رجوع بالأخطاء والمدخلات
        });
    })->create();
