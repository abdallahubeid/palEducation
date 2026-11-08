<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LectureViewController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\LectureProgressController as StudentLectureProgressController;
use App\Http\Controllers\Student\LibraryController as StudentLibraryController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ProgressController as StudentProgressController;
use App\Http\Controllers\Student\SubjectController as StudentSubjectController;
use App\Http\Controllers\Teacher\LectureController as TeacherLectureController;
use App\Http\Controllers\Teacher\LibraryController as TeacherLibraryController;
use App\Http\Controllers\Teacher\NotificationController as TeacherNotificationController;
use App\Http\Controllers\Teacher\ProfileController as TeacherProfileController;
use App\Http\Controllers\Teacher\StudentController as TeacherStudentController;
use App\Http\Controllers\Teacher\SubjectController as TeacherSubjectController;
use App\Http\Controllers\Teacher\TrashController as TeacherTrashController;
use App\Http\Controllers\TestimonialSubmissionController;
use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| المنطقة العامة — قبل تسجيل الدخول
|--------------------------------------------------------------------------
| الموجة 1 · الشاشة 1 من 3
*/

// كنترولر لا Route::view: بانر الدعوة يُقرأ من جدول settings.
Route::get('/', HomeController::class)->name('home');

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
/*
|--------------------------------------------------------------------------
| Student review submission — from the home page modal
|--------------------------------------------------------------------------
| 🔴 auth here, and the student check inside the controller. A teacher or
| admin is authenticated but has no `students` row, and testimonials.student_id
| points at that table — letting them through would be a foreign-key 500
| rather than a refusal.
|
| ⚠️ Throttled. The form is open to every student on the platform and writes
| a row that renders on the landing page once approved; without a limit it is
| the cheapest way to fill the moderation queue with noise.
|
| POST only — never GET. This writes.
*/
Route::post('/testimonials', [TestimonialSubmissionController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('testimonials.store');

/*
| الفروع — من قاعدة البيانات لا من مصفوفات في القالب.
|
| ⚠️ الربط بالـslug (Branch::getRouteKeyName)، والفرع غير النشط يُرجع 404
| لا 403: الرد بـ«ممنوع» يؤكّد للزائر أن الـslug حقيقي.
*/
Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
/*
| الأخبار — من قاعدة البيانات.
|
| ⚠️ الربط بالـslug، والخبر غير المنشور (أو المجدول لتاريخ لاحق) يُرجع 404
| لا 403: الرد بـ«ممنوع» يؤكّد أن الـslug حقيقي وأن هناك ما يُحجب.
*/
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');

/*
| صفحة التواصل — إضافة خارج خطة الـ48 (كـ/styleguide).
| تُفعّل رابط «تواصل معنا» في التذييل الذي كان يتساقط إلى '#'.
*/
// Controller, not Route::view: the page now reads its header, channels
// and hours from `settings`.
Route::get('/contact', [ContactController::class, '__invoke'])->name('contact');

/*
 | ⚠️ Throttled: this writes a row AND queues an email on every hit, so an
 | unthrottled form is a free way to fill the inbox and the mail queue.
 | Open to guests by design — most people who write in have no account.
 */
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

/*
|--------------------------------------------------------------------------
| النشرة البريدية
|--------------------------------------------------------------------------
| ⚠️ الاشتراك مخنوق كنموذج التواصل: كل إرسال يكتب صفّاً، ونموذج الفوتر
| موجود في **كل** صفحة فهو أوسع سطح هجوم في الموقع.
|
| 🔴 إلغاء الاشتراك خطوتان: GET يعرض تأكيداً ولا يغيّر شيئاً، وPOST ينفّذ.
| عملاء البريد وفاحصات الأمان تجلب كل رابط في الرسالة قبل أن يراها إنسان،
| فإلغاء يعمل على GET كان سيشطب أناساً لم يضغطوا شيئاً.
|
| والمساران موقّعان: /unsubscribe/{id} بلا توقيع يسمح لأي أحد بالمرور على
| الأرقام وإلغاء اشتراك القائمة كلها.
*/
Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');

Route::get('/newsletter/unsubscribe/{subscriber}', [UnsubscribeController::class, 'show'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe');

Route::post('/newsletter/unsubscribe/{subscriber}', [UnsubscribeController::class, 'update'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe.confirm');

Route::get('/newsletter/unsubscribed', [UnsubscribeController::class, 'done'])
    ->name('newsletter.unsubscribed');

/*
|--------------------------------------------------------------------------
| دليل النظام — سطح تطوير داخلي لا واجهة منتج
|--------------------------------------------------------------------------
| الموجة 0 · كان مخطَّطاً منذ البداية ولم يُبنَ حتى الآن.
| يعرض كل مكوّن في كل حالاته وفي الاتجاهين، وهو سطح العمل الذي
| صُمّمت عليه مكوّنات النماذج ذات الحالة (Select/Checkbox/Radio/Password).
|
| 🔴 محجوب في الإنتاج (404): noindex وحدها لا تكفي، والمسار يبقى مسجَّلاً
| باسمه لأن قوالب أخرى تستدعي route('styleguide').
*/
Route::get('/styleguide', function () {
    abort_if(app()->isProduction(), 404);

    return view('pages.styleguide');
})->name('styleguide');

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
// 🔁 نُقلت إلى routes/auth.php — صارت كنترولرز حقيقية لا Route::view.

/*
| الشاشة 16 — اختيار الفرع · قرار شبه دائم
| ⏳ يلامس م-6 (تغيير الفرع لاحقاً): النصّ هنا يتبع التوصية المسجّلة
| (تغيير عبر طلب للأدمن فقط) وليس قراراً محسوماً.
*/
// 🔁 اختيار الفرع صار الخطوة الأخيرة في تسجيل جوجل — routes/auth.php
// (auth.google.branch). ما تبقّى هنا مسار معاينة أثناء التطوير فقط.
Route::view('/preview/select-branch', 'pages.auth.select-branch')->name('preview.branch');

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

/*
| الدفعة الثانية — الرفع والبناء والملف والسلة والإشعارات
| ⏳ شاشة رفع المحاضرة تلامس م-5: منطقة الرفع وشريط التقدّم محاكاة صريحة،
| تُستبدل بتكامل حقيقي (Bunny/Vimeo) عند حسم القرار.
| ⏳ بناء الكويز يطبّق توصية م-3 (لا نشر بلا سؤال واحد) كسلوك واجهة فقط.
*/
Route::view('/teacher/lectures/{lecture}/quiz', 'pages.teacher.quiz-builder')->name('teacher.lectures.quiz');
/*
|--------------------------------------------------------------------------
| ملف المعلّم الشخصي
|--------------------------------------------------------------------------
| 🔴 محروس فعلاً — auth + role:teacher. بقية مسارات بوابة المعلّم أعلاه ما
| زالت Route::view معاينات ثابتة **بلا أي حراسة**، وهو دَين قائم لا علاج له
| هنا: تحويلها كلها خارج نطاق هذه المهمة، لكن هذا المسار يكتب في قاعدة
| البيانات فلا يجوز أن يبقى مكشوفاً.
|
| ⚠️ الصورة POST والحذف DELETE — لا GET لأيّهما: كلاهما يكتب على القرص.
| ولا مُعرِّف حساب في أي مسار: الكنترولر يعمل على $request->user() حصراً.
*/
Route::middleware(['auth', 'role:teacher'])->group(function () {
    /*
     | موادي المسندة — أول شاشة حقيقية في بوابة المعلّم.
     |
     | 🔴 النطاق بنيوي: الاستعلام يبدأ من $teacher->subjects()، فلا فلتر
     | يُنسى ولا مُعرِّف في المسار يُبدَّل. بقية مسارات البوابة ما زالت
     | Route::view معاينات ثابتة بلا حراسة — دَين قائم موثَّق.
     */
    Route::get('/teacher/subjects', [TeacherSubjectController::class, 'index'])->name('teacher.subjects');

    /*
     | محاضراتي.
     |
     | 🔴 النطاق على **المادة** لا على lectures.teacher_id، ومطبَّق في
     | Lecture::forTeacher فلا يُنسى نصفه. وكل كتابة تُعيد فحص المادة
     | الهدف: subject_id المُرسَل رقم يمكن تحريره.
     |
     | ⚠️ الحذف DELETE والتعديل PUT — لا GET لأيّهما.
     */
    /*
     | مكتبة المعلّم.
     |
     | 🔴 الملف على القرص الخاص، والتسليم عبر مسار يفحص أن الملف يخصّ مادة
     | مسندة لهذا المعلّم. لا رابط مباشر ولا مسار تحت public/.
     |
     | ⚠️ التنزيل هنا **للمعلّم** حصراً. مسار الطالب (فرع + اشتراك) لم يُبنَ
     | بعد، ولا يجوز إعادة استعمال هذا له.
     */
    Route::get('/teacher/library', [TeacherLibraryController::class, 'index'])->name('teacher.library');
    Route::post('/teacher/library', [TeacherLibraryController::class, 'store'])->name('teacher.library.store');
    Route::put('/teacher/library/{library}', [TeacherLibraryController::class, 'update'])->name('teacher.library.update');
    Route::delete('/teacher/library/{library}', [TeacherLibraryController::class, 'destroy'])->name('teacher.library.destroy');
    Route::get('/teacher/library/{library}/file', [TeacherLibraryController::class, 'download'])->name('teacher.library.download');
    Route::get('/teacher/lectures', [TeacherLectureController::class, 'index'])->name('teacher.lectures');
    Route::post('/teacher/lectures', [TeacherLectureController::class, 'store'])->name('teacher.lectures.store');
    Route::put('/teacher/lectures/{lecture}', [TeacherLectureController::class, 'update'])->name('teacher.lectures.update');
    Route::delete('/teacher/lectures/{lecture}', [TeacherLectureController::class, 'destroy'])->name('teacher.lectures.destroy');

    Route::get('/teacher/profile', [TeacherProfileController::class, 'edit'])->name('teacher.profile.edit');
    Route::put('/teacher/profile', [TeacherProfileController::class, 'update'])->name('teacher.profile.update');
    Route::put('/teacher/profile/password', [TeacherProfileController::class, 'updatePassword'])
        ->name('teacher.profile.password');
    Route::post('/teacher/profile/avatar', [TeacherProfileController::class, 'updateAvatar'])
        ->name('teacher.profile.avatar');
    Route::delete('/teacher/profile/avatar', [TeacherProfileController::class, 'destroyAvatar'])
        ->name('teacher.profile.avatar.destroy');

    /*
     | طلبة فروعي — قائمة لا لوحة أداء.
     |
     | 🔴 لا بيانات أداء في قاعدة البيانات (لا امتحانات/واجبات/حضور، ولا جدول
     | يربط الطالب بمادة المعلّم). النطاق الوحيد الممكن: طلبة **فروع** مواد
     | المعلّم المسندة. المقاييس تُضاف حين تُبنى أنظمتها.
     */
    Route::get('/teacher/students', [TeacherStudentController::class, 'index'])->name('teacher.students');

    /*
     | سلّة محذوفات المعلّم — استرجاع فقط (م-9)، محاضرات وملفات مواده.
     | لا مسار حذف نهائي: صلاحية إدارة، والحذف التلقائي قرار معلّق.
     */
    Route::get('/teacher/trash', [TeacherTrashController::class, 'index'])->name('teacher.trash');
    Route::patch('/teacher/trash/{type}/{id}/restore', [TeacherTrashController::class, 'restore'])
        ->name('teacher.trash.restore');

    /*
     | إشعارات المعلّم — مقيَّدة كلّها بـ$request->user() ولا مُعرِّف حساب في
     | أي مسار: uuid مُخمَّن لزميل يُحَلّ إلى لا شيء لا إلى صفّه.
     |
     | ⚠️ api/notifications يُستطلَع كل 15ث من كل تبويب مفتوح؛ الصفحة الكاملة
     | لا. منفصلان بميزانيتين مختلفتين، ولذلك اسمَان مختلفان.
     */
    Route::get('/teacher/notifications', [TeacherNotificationController::class, 'index'])->name('teacher.notifications');
    Route::delete('/teacher/notifications/read', [TeacherNotificationController::class, 'clearRead'])
        ->name('teacher.notifications.clear-read');
    Route::delete('/teacher/notifications/{id}', [TeacherNotificationController::class, 'destroy'])
        ->name('teacher.notifications.destroy');
    Route::post('/teacher/notifications/{id}/toggle', [TeacherNotificationController::class, 'toggle'])
        ->name('teacher.notifications.toggle');

    Route::get('/teacher/api/notifications', [TeacherNotificationController::class, 'feed'])
        ->name('teacher.notifications.feed');
    Route::post('/teacher/api/notifications/read-all', [TeacherNotificationController::class, 'markAllAsRead'])
        ->name('teacher.notifications.read-all');
    Route::post('/teacher/api/notifications/{id}/read', [TeacherNotificationController::class, 'markAsRead'])
        ->name('teacher.notifications.read');
});

Route::view('/preview/402', 'errors.402')->name('preview.402');
Route::view('/preview/403', 'errors.403')->name('preview.403');
Route::view('/preview/404', 'errors.404')->name('preview.404');

/*
|--------------------------------------------------------------------------
| منطقة الطالب — الشاشات الحيّة (auth + role:student)
|--------------------------------------------------------------------------
| 🔴 بلا بوّابة اشتراك: لا جدول subscriptions بعد، والجدار المالي مكانه
| المستقبلي داخل الكنترولرات (نفس نصّ LectureViewController). النطاق
| الجذري = الفرع، مفروضاً يدوياً في كل استعلام (لا Global Scope بعد).
|
| ما بقي ثابتاً (subscription · notifications · quiz · checkout) يُحوَّل
| في موجات لاحقة — نفس النمط التدريجي لبوابة المعلّم.
*/
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');

    Route::get('/student/subjects', [StudentSubjectController::class, 'index'])->name('student.subjects.index');
    Route::get('/student/subjects/{subject}', [StudentSubjectController::class, 'show'])->name('student.subjects.show');

    Route::get('/student/progress', [StudentProgressController::class, 'index'])->name('student.progress');

    /*
     | المكتبة — مسار طالب **جديد**. مسار المعلّم (teacher.library.download)
     | لا يُعاد استعماله: هذا يفحص الفرع، وذاك يفحص المادة المسندة.
     |
     | 🔴 التنزيل عبر رابط موقَّع قصير (5 د) — اصطلاح laravel-conventions §4.
     | الملف على القرص الخاص، والتسليم response()->file بعد فحص التوقيع
     | والفرع معاً.
     */
    Route::get('/student/library', [StudentLibraryController::class, 'index'])->name('student.library');
    Route::get('/student/library/{file}/download', [StudentLibraryController::class, 'download'])
        ->middleware('signed')
        ->name('student.library.download');

    /*
     | نبضة تقدّم المشاهدة — م-5: تحقّق خادمي موثوق عبر فترات تغطية لا علم
     | عميل. الجسم يحمل المقطع المُشاهَد منذ آخر نبضة [from,to]؛ الخادم
     | يدمجه ويعيد حساب النسبة ويضبط completed_at عند بلوغ العتبة.
     */
    Route::post('/student/lectures/{lecture}/progress', [StudentLectureProgressController::class, 'heartbeat'])
        ->name('student.lectures.progress');

    Route::get('/student/profile', [StudentProfileController::class, 'edit'])->name('student.profile');
    Route::put('/student/profile', [StudentProfileController::class, 'update'])->name('student.profile.update');
    Route::put('/student/profile/password', [StudentProfileController::class, 'updatePassword'])
        ->name('student.profile.password');
    Route::post('/student/profile/avatar', [StudentProfileController::class, 'updateAvatar'])
        ->name('student.profile.avatar');
    Route::delete('/student/profile/avatar', [StudentProfileController::class, 'destroyAvatar'])
        ->name('student.profile.avatar.destroy');
});

/*
| الموجة 2 · الشاشة 4 — مشاهدة المحاضرة (الحلقة المغلقة)
| ⏳ معلّق على م-5 (مزوّد الفيديو) — المشغّل هنا عرض تجريبي بديل،
| يُستبدل بمُضمَّن SDK حقيقي (Bunny Stream/Vimeo) عند حسم القرار.
*/
/*
| صفحة مشاهدة المحاضرة — داخل المنصّة لا على يوتيوب.
|
| 🔴 نفس الشاشة للمعلّم (معاينة) وللطالب (درس): مكان واحد يلتقي فيه
| المشغّل والتقدّم والكويز لاحقاً. وإرسال أيّهما إلى youtube.com يضع الدرس
| في صفحة لا نتحكّم بها.
|
| ⚠️ بلا auth عمداً: المحاضرة المنشورة متاحة اليوم لأن الاشتراكات لم
| تُبنَ بعد (لا جدول subscriptions). الفحص داخل الكنترولر، وهو مكان
| الجدار المالي حين يصل.
*/
Route::get('/student/lectures/{lecture}', [LectureViewController::class, 'show'])
    ->name('student.lectures.show');

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
| ما بقي ثابتاً في منطقة الطالب — يُحوَّل في موجات لاحقة.
*/
Route::view('/student/subscription', 'pages.student.subscription')->name('student.subscription');
Route::view('/student/notifications', 'pages.student.notifications')->name('student.notifications');

/*
|--------------------------------------------------------------------------
| مسار التحويل — الموجة 3 (الشاشات 7–10)
|--------------------------------------------------------------------------
| فُتحت بحسم القرار م-1 (نموذجا اشتراك: بالمادة · شامل للفرع) وم-4
| (دفع يدوي: تحويل بنكي · محفظة · وكيل · نقداً).
|
| صفحة الأسعار عامة — الزائر يجب أن يرى السعر قبل التسجيل.
| شاشات الدفع الثلاث داخل منطقة الطالب، وستنتقل لاحقاً إلى مجموعة
| محمية بـ middleware('auth', 'role:student').
*/
Route::view('/pricing', 'pages.pricing')->name('pricing');
Route::view('/student/checkout', 'pages.student.checkout')->name('student.checkout');
Route::view('/student/checkout/proof', 'pages.student.checkout-proof')->name('student.checkout.proof');
Route::view('/student/checkout/pending', 'pages.student.checkout-pending')->name('student.checkout.pending');
