<?php

/*
|--------------------------------------------------------------------------
| مدخل Vercel لتطبيق Laravel (مشغّل vercel-php)
|--------------------------------------------------------------------------
| نظام ملفات النشر للقراءة فقط عدا مجلد المؤقّتات، فكل مسار يكتب عليه
| Laravel يُوجَّه إلى هناك: التخزين، وملفات الكاش المولَّدة، والقوالب
| المصرَّفة. والإعدادات الافتراضية تحت تعمل ما لم تُحدَّد القيمة نفسها في
| متغيّرات المشروع على Vercel، فيمكن استبدال أي منها دون لمس الكود.
|
| 🔴 الوضع الافتراضي عرض تجريبي: قاعدة SQLite منسوخة من database/demo إلى
| مجلد المؤقّتات، والجلسات ملفات فيه أيضاً. ما يُكتب أثناء التشغيل (ومعه
| الجلسات) يضيع حين تُعاد تهيئة الدالة. لاستعمال قاعدة حقيقية عيّن DB_CONNECTION (مع بقية
| DB_*) في متغيّرات المشروع، فيتوقّف هذا الملف عن لمس قاعدة البيانات.
*/

use Illuminate\Http\Request;

// /tmp لا sys_get_temp_dir(): Laravel يعدّ المسار مطلقاً فقط إن بدأ بـ"/"،
// وكل مسار آخر يُلصَق بمسار المشروع. و/tmp هو المجلد القابل للكتابة على Vercel.
$tmp = '/tmp/laravel';

foreach ([
    'storage/app/private',
    'storage/app/public',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
] as $directory) {
    if (! is_dir($path = $tmp.'/'.$directory)) {
        @mkdir($path, 0777, true);
    }
}

$defaults = [
    // مسارات الكتابة
    'LARAVEL_STORAGE_PATH' => $tmp.'/storage',
    'VIEW_COMPILED_PATH' => $tmp.'/storage/framework/views',
    'APP_PACKAGES_CACHE' => $tmp.'/bootstrap/cache/packages.php',
    'APP_SERVICES_CACHE' => $tmp.'/bootstrap/cache/services.php',
    'APP_CONFIG_CACHE' => $tmp.'/bootstrap/cache/config.php',
    'APP_ROUTES_CACHE' => $tmp.'/bootstrap/cache/routes-v7.php',
    'APP_EVENTS_CACHE' => $tmp.'/bootstrap/cache/events.php',

    // بيئة إنتاج لا تعرض أخطاء داخلية
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'LOG_CHANNEL' => 'stderr',

    // 🔴 الجلسة ملفات في المؤقّتات لا كوكي: إشعار php-flasher بعد الدخول يُخزَّن
    // في الجلسة (~2KB)، والتشفير المزدوج يضخّم كوكي الجلسة إلى ~4.9KB، وكل
    // متصفّح يرفض كوكياً فوق 4096 بايت — فيضيع الدخول بصمت بعد كل تحويل.
    // ثمنه أن الجلسة تتبع نسخة الدالة، كقاعدة SQLite تماماً.
    'SESSION_DRIVER' => 'file',
    'SESSION_SECURE_COOKIE' => 'true',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'log',
];

if (getenv('DB_CONNECTION') === false) {
    $database = $tmp.'/database.sqlite';

    if (! is_file($database)) {
        copy(__DIR__.'/../database/demo/demo.sqlite', $database);
    }

    $defaults += [
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $database,
        'DB_FOREIGN_KEYS' => 'true',
    ];
}

foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
