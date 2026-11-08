<?php

use App\Support\AdminNotifier;
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
        // 🔴 خلف وكيل عكسي (Vercel وغيره): بدون الثقة بترويسات X-Forwarded-*
        // يرى Laravel الطلب http فيولّد روابط الأصول والتوقيعات بـhttp،
        // فيحجب المتصفّح الأنماط والسكربتات كمحتوى مختلط.
        $middleware->trustProxies(at: '*');

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
            // ⚠️ بوابة المعلّم أيضاً: كانت تسقط بصمت بلا رسالة.
            if (request()->is('admin/*', 'teacher/*') && ! request()->expectsJson()) {
                flash()->error('حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.');
            }

            /*
             | 🔴 مخنوق داخل reportError نفسها: استثناء داخل حلقة، أو عامل
             | طابور يعيد محاولة مهمة مكسورة، ينتج آلاف الأخطاء المتطابقة في
             | الدقيقة — وبلا حارس يمتلئ جدول الإشعارات أسرع مما يُقرأ،
             | فيصير الجرس عديم الفائدة في اللحظة التي يهمّ فيها فعلاً.
             |
             | ⚠️ داخل try: فشل الإشعار نفسه (قاعدة بيانات ساقطة مثلاً) يجب
             | ألّا يرمي استثناءً ثانياً من داخل معالج الاستثناءات.
             */
            try {
                AdminNotifier::reportError($e, request()->fullUrl());
            } catch (Throwable) {
                // لا شيء: الإبلاغ عن الخطأ ليس أهمّ من الخطأ نفسه.
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            /*
             | admin فقط — لا teacher. شاشات الأدمن نماذج كاملة الصفحة،
             | والرسالة بند في جدول الإشعارات الستّ المثبَّت في FlashMatrixTest.
             |
             | 🔴 بوابة المعلّم مُستثناة عمداً: نماذجها نوافذ منبثقة تُعاد فتحها
             | (data-reopen) وبداخلها أخطاء الحقول الظاهرة (@error) — توست ✗ ملء
             | الشاشة فوقها يحجب النموذج الذي يجب تصحيحه، ويكرّر ما هو معروض
             | أصلاً. خطأ التحقّق مدخل مستخدم لا عطل خادم؛ الاستثناء الحقيقي
             | (reportable أعلاه) ما زال يومض للمعلّم.
             */
            if ($request->is('admin/*') && ! $request->expectsJson()) {
                flash()->error('يرجى التأكد من تصحيح الأخطاء في النموذج.');
            }

            return null;   // يكمل السلوك الافتراضي: رجوع بالأخطاء والمدخلات
        });
    })->create();
