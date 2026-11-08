<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| المهام المجدولة
|--------------------------------------------------------------------------
| ⚠️ تحتاج `php artisan schedule:work` (أو مدخلة cron) لتعمل فعلاً. غيابه
| لا يكسر شيئاً — التنبيه لا يصل فحسب، والسلّة تبقى كما هي لأن الأمر
| **ينبّه ولا يحذف**.
*/
Schedule::command('notifications:trash-warning')
    // مرة أسبوعياً لا يومياً: تنبيه يومي عن نفس العناصر يُقرأ ضجيجاً
    // ويُدرّب العين على تجاهل الجرس.
    ->weeklyOn(0, '08:00')
    ->withoutOverlapping();
