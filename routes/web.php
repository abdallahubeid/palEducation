<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| المنطقة العامة — قبل تسجيل الدخول
|--------------------------------------------------------------------------
| الموجة 1 · الشاشة 1 من 3
*/

Route::view('/', 'pages.home')->name('home');

/*
|--------------------------------------------------------------------------
| دليل النظام — سطح تطوير داخلي لا واجهة منتج
|--------------------------------------------------------------------------
| الموجة 0 · كان مخطَّطاً منذ البداية ولم يُبنَ حتى الآن.
| يعرض كل مكوّن في كل حالاته وفي الاتجاهين، وهو سطح العمل الذي
| صُمّمت عليه مكوّنات النماذج ذات الحالة (Select/Checkbox/Radio/Password).
|
| ⚠️ يجب استثناؤه أو حجبه قبل أي نشر إنتاجي — noindex وحدها لا تكفي.
*/
Route::view('/styleguide', 'pages.styleguide')->name('styleguide');

/*
|--------------------------------------------------------------------------
| المصادقة والهوية — الموجة 4
|--------------------------------------------------------------------------
| واجهات فقط: لا مصادقة ولا خادم بعد. النماذج تُدار بـdata-demo-submit
| في app.js (تمنع الإرسال وتنتقل) — عمداً بدل GET حتى لا تظهر كلمة
| المرور في شريط العنوان.
|
| شاشة دخول واحدة للأدوار الثلاثة (توصية م-7)، والتوجيه بعد الدخول
| حسب الدور عند بناء المصادقة.
*/
Route::view('/login', 'pages.auth.login')->name('auth.login');
Route::view('/register', 'pages.auth.register')->name('auth.register');
Route::view('/forgot-password', 'pages.auth.forgot-password')->name('auth.forgot');

/*
| الشاشة 16 — اختيار الفرع · قرار شبه دائم
| ⏳ يلامس م-6 (تغيير الفرع لاحقاً): النصّ هنا يتبع التوصية المسجّلة
| (تغيير عبر طلب للأدمن فقط) وليس قراراً محسوماً.
*/
Route::view('/select-branch', 'pages.auth.select-branch')->name('auth.branch');

/*
|--------------------------------------------------------------------------
| حالات النظام — 402 · 403 · 404
|--------------------------------------------------------------------------
| القوالب تعيش في resources/views/errors/ فتلتقطها Laravel تلقائياً عند
| رمي الاستثناء المقابل. المسارات أدناه للمعاينة أثناء التطوير فقط.
*/
Route::view('/preview/402', 'errors.402')->name('preview.402');
Route::view('/preview/403', 'errors.403')->name('preview.403');
Route::view('/preview/404', 'errors.404')->name('preview.404');

/*
|--------------------------------------------------------------------------
| منطقة الطالب — قبل المصادقة والصلاحيات
|--------------------------------------------------------------------------
| الموجة 1 · الشاشة 2 من 3 — بيانات عرض ثابتة، بلا نموذج مجال بعد.
| سيُنقَل لمجموعة محمية بـ middleware('auth', 'role:student', 'subscription.active')
| عند بناء المصادقة.
*/

Route::view('/student/dashboard', 'pages.student.dashboard')->name('student.dashboard');

/*
| الموجة 1 · الشاشة 3 من 3 — صفحة المادة (نقطة توقّف إلزامية بعدها)
| {subject} غير مستخدم بعد — بيانات عرض ثابتة بلا نموذج مجال.
*/
/*
| الموجة 5 · الشاشة 18 — موادي (القائمة الرئيسية)
| بُنيت هنا خارج الترتيب لأن روابط لها موجودة أصلاً (الشريط الجانبي،
| اللوحة، صفحة المادة) وكانت تتساقط جميعها إلى '#' حتى الآن.
*/
Route::view('/student/subjects', 'pages.student.subjects')->name('student.subjects.index');

Route::view('/student/subjects/{subject}', 'pages.student.subject')->name('student.subjects.show');

/*
| الموجة 2 · الشاشة 4 — مشاهدة المحاضرة (الحلقة المغلقة)
| ⏳ معلّق على م-5 (مزوّد الفيديو) — المشغّل هنا عرض تجريبي بديل،
| يُستبدل بمُضمَّن SDK حقيقي (Bunny Stream/Vimeo) عند حسم القرار.
*/
Route::view('/student/lectures/{lecture}', 'pages.student.lecture')->name('student.lectures.show');

/*
| الموجة 2 · الشاشة 5 — الكويز
| ⏳ معلّق على م-2 — القواعد هنا توصية docs/01 §7 (محاولتان، لا قفل)
| بانتظار قرار فعلي. بلا شريط جانبي عمداً — layouts.focus.
*/
Route::view('/student/lectures/{lecture}/quiz', 'pages.student.quiz')->name('student.lectures.quiz');

/*
| الموجة 2 · الشاشة 6 — نتيجة الكويز (تُغلق الحلقة)
| مضمّنة أصلاً داخل student.lectures.show (نمط Sprints.ai من الشاشة
| السابقة) — هذا المسار احتياطي فقط لإنهاء الكويز من صفحته المستقلة.
*/
Route::view('/student/lectures/{lecture}/quiz/result', 'pages.student.quiz-result')->name('student.lectures.quiz-result');

/*
| الموجة 5 — بقية منطقة الطالب (٥ شاشات مبنية دفعة واحدة)
| بيانات عرض ثابتة، بلا نموذج مجال ولا مصادقة بعد — نفس نمط بقية المشروع.
*/
Route::view('/student/library', 'pages.student.library')->name('student.library');
Route::view('/student/progress', 'pages.student.progress')->name('student.progress');
Route::view('/student/subscription', 'pages.student.subscription')->name('student.subscription');
Route::view('/student/profile', 'pages.student.profile')->name('student.profile');
Route::view('/student/notifications', 'pages.student.notifications')->name('student.notifications');
