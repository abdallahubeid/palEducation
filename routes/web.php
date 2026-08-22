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
| المنطقة العامة — الموجة 6
|--------------------------------------------------------------------------
| سطح الاكتساب: زائر يتعرّف على الفروع والأخبار قبل التسجيل.
| البحث والفلترة على العميل (بلا خادم بعد) — نفس نمط بقية المشروع.
|
| ملاحظة: «من نحن» و«الأسعار» ليسا مسارين — لهما قسمان على الصفحة
| الرئيسية (#about و#pricing) ويربط الشريط العلوي بهما مباشرة.
*/
Route::view('/branches', 'pages.branches')->name('branches.index');
Route::view('/branches/{branch}', 'pages.branch')->name('branches.show');
Route::view('/news', 'pages.news')->name('news.index');
Route::view('/news/{slug}', 'pages.news-detail')->name('news.show');

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
/*
|--------------------------------------------------------------------------
| منطقة المعلم — الموجة 7 (الدفعة الأولى)
|--------------------------------------------------------------------------
| واجهات فقط ببيانات وهمية. 🔴 نطاق المعلم (مواده المسندة فقط) مُحاكى في
| البيانات لا مفروضاً بعد — يُفرَض عبر Policy وGlobal Scope عند بناء الخادم.
|
| الدفعة الثانية (رفع محاضرة · بناء الكويز · رفع ملف · ملفي · السلة)
| تُبنى لاحقاً؛ روابطها هنا تتساقط إلى '#' عبر حرس Route::has.
*/
Route::view('/teacher/dashboard', 'pages.teacher.dashboard')->name('teacher.dashboard');
Route::view('/teacher/subjects', 'pages.teacher.subjects')->name('teacher.subjects');
Route::view('/teacher/lectures', 'pages.teacher.lectures')->name('teacher.lectures');
Route::view('/teacher/performance', 'pages.teacher.performance')->name('teacher.performance');

/*
| الدفعة الثانية — الرفع والبناء والملف والسلة والإشعارات
| ⏳ شاشة رفع المحاضرة تلامس م-5: منطقة الرفع وشريط التقدّم محاكاة صريحة،
| تُستبدل بتكامل حقيقي (Bunny/Vimeo) عند حسم القرار.
| ⏳ بناء الكويز يطبّق توصية م-3 (لا نشر بلا سؤال واحد) كسلوك واجهة فقط.
*/
Route::view('/teacher/files/create', 'pages.teacher.file-create')->name('teacher.files.create');
Route::view('/teacher/lectures/create', 'pages.teacher.lecture-create')->name('teacher.lectures.create');
Route::view('/teacher/lectures/{lecture}/quiz', 'pages.teacher.quiz-builder')->name('teacher.lectures.quiz');
Route::view('/teacher/profile', 'pages.teacher.profile')->name('teacher.profile');
Route::view('/teacher/trash', 'pages.teacher.trash')->name('teacher.trash');
Route::view('/teacher/notifications', 'pages.teacher.notifications')->name('teacher.notifications');

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
