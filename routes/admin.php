<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NewsletterCampaignController;
use App\Http\Controllers\Admin\NewsletterCampaignHistoryController;
use App\Http\Controllers\Admin\NewsletterSubscriberController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PricingPlanController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\StepController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\UserController;
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
        /*
        |----------------------------------------------------------------
        | جرس الإشعارات — JSON
        |----------------------------------------------------------------
        | 🔴 داخل مجموعة اللوحة نفسها، فيحرسها auth + role:admin. لا صلاحية
        | إضافية: هذه إشعارات **حسابك أنت**، والحارس بنيوي — لا مُعرِّف حساب
        | في أي مسار، والكنترولر يعمل على $request->user() حصراً.
        |
        | ⚠️ index يُستدعى كل ١٥ ثانية من كل تبويب مفتوح. أي شيء يُضاف إليه
        | يعمل على مؤقّت ما دامت اللوحة مفتوحة.
        */
        /*
        |----------------------------------------------------------------
        | صفحة الإشعارات الكاملة
        |----------------------------------------------------------------
        | ⚠️ الاسم index لهذه الصفحة، ومسار JSON صار feed. كانا يتنازعان على
        | الاسم نفسه: الجرس يستهلك JSON والصفحة تُعرض، وهما شيئان مختلفان
        | رغم أنهما يخدمان البيانات ذاتها.
        */
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');

            // DELETE لا GET: كلاهما يمحو صفوفاً.
            Route::delete('read', [NotificationController::class, 'clearRead'])->name('clear-read');
            Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
            Route::post('{id}/toggle', [NotificationController::class, 'toggle'])->name('toggle');
        });

        Route::prefix('api/notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'feed'])->name('feed');

            // POST لا GET: كلاهما يكتب read_at، ومسار GET يُنفَّذ بجالب مسبق.
            Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::post('{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        });

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.password');

        /*
         | صورة الحساب — POST للرفع وDELETE للحذف.
         |
         | ⚠️ لا مسار GET لأيّهما: كلاهما يغيّر حالة الخادم ويكتب على القرص،
         | ومسار GET يُنفَّذ بجالب مسبق في المتصفّح أو بزاحف بلا نيّة.
         |
         | 🔴 بلا can:: هذه شاشة **حسابك أنت**، والحارس هو أن الكنترولر
         | يعمل على $request->user() حصراً — لا مُعرِّف في المسار يمكن
         | تبديله لتعديل صورة زميل.
         */
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])
            ->name('profile.avatar');

        Route::delete('profile/avatar', [ProfileController::class, 'destroyAvatar'])
            ->name('profile.avatar.destroy');

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
        | الفروع — كيان مستقلّ بشاشاته
        |----------------------------------------------------------------
        | 🔴 خارج الإعدادات عمداً: الفرع سجلّ مجال يملك معرّفاً في الرابط
        | وترتيباً وحذفاً ناعماً وأربع صلاحيات، لا زوج مفتاح/قيمة. ما بقي
        | في تبويب الإعدادات هو **ترويسة القسم** وحدها.
        */
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::get('/', [BranchController::class, 'index'])
                ->middleware('can:view-branches')->name('index');

            Route::middleware('can:create-branches')->group(function () {
                Route::get('create', [BranchController::class, 'create'])->name('create');
                Route::post('/', [BranchController::class, 'store'])->name('store');
            });

            Route::middleware('can:edit-branches')->group(function () {
                Route::get('{branch}/edit', [BranchController::class, 'edit'])->name('edit');
                Route::put('{branch}', [BranchController::class, 'update'])->name('update');
                Route::patch('{branch}/toggle', [BranchController::class, 'toggle'])->name('toggle');
            });

            Route::delete('{branch}', [BranchController::class, 'destroy'])
                ->middleware('can:delete-branches')->name('destroy');
        });

        /*
        |----------------------------------------------------------------
        | المميزات — كيان مستقلّ بشاشاته
        |----------------------------------------------------------------
        | 🔴 خارج الإعدادات كالفروع: البطاقة سجلّ مجال له ترتيب وحذف ناعم
        | وأربع صلاحيات. ما يبقى في تبويب الإعدادات هو **ترويسة القسم**
        | (الشارة والعنوان) وحدها.
        */
        Route::prefix('features')->name('features.')->group(function () {
            Route::get('/', [FeatureController::class, 'index'])
                ->middleware('can:view-features')->name('index');

            Route::middleware('can:create-features')->group(function () {
                Route::get('create', [FeatureController::class, 'create'])->name('create');
                Route::post('/', [FeatureController::class, 'store'])->name('store');
            });

            Route::middleware('can:edit-features')->group(function () {
                Route::get('{feature}/edit', [FeatureController::class, 'edit'])->name('edit');
                Route::put('{feature}', [FeatureController::class, 'update'])->name('update');
                Route::patch('{feature}/toggle', [FeatureController::class, 'toggle'])->name('toggle');
            });

            Route::delete('{feature}', [FeatureController::class, 'destroy'])
                ->middleware('can:delete-features')->name('destroy');
        });

        /*
        |----------------------------------------------------------------
        | خطط الأسعار — كيان مستقلّ بشاشاته
        |----------------------------------------------------------------
        | 🔴 نسخة تسويقية لا نموذج فوترة: ما اشتراه الطالب فعلاً يعيش في
        | جدول subscriptions (المرحلة 8، غير مبنية). تعديل سعر هنا يجب
        | ألّا يعيد كتابة ما دفعه أحد.
        |
        | المعامل اسمه {pricing} لا {pricingPlan}: البادئة والاسم كلاهما
        | `pricing`، فالربط النموذجي يقرأ نفس الكلمة في المسار والدالة.
        */
        Route::prefix('pricing')->name('pricing.')->group(function () {
            Route::get('/', [PricingPlanController::class, 'index'])
                ->middleware('can:view-pricings')->name('index');

            Route::middleware('can:create-pricings')->group(function () {
                Route::get('create', [PricingPlanController::class, 'create'])->name('create');
                Route::post('/', [PricingPlanController::class, 'store'])->name('store');
            });

            Route::middleware('can:edit-pricings')->group(function () {
                Route::get('{pricing}/edit', [PricingPlanController::class, 'edit'])->name('edit');
                Route::put('{pricing}', [PricingPlanController::class, 'update'])->name('update');
                Route::patch('{pricing}/toggle', [PricingPlanController::class, 'toggle'])->name('toggle');
            });

            Route::delete('{pricing}', [PricingPlanController::class, 'destroy'])
                ->middleware('can:delete-pricings')->name('destroy');
        });

        /*
        |----------------------------------------------------------------
        | آراء الطلبة — كيان مستقلّ بشاشاته
        |----------------------------------------------------------------
        | 🔴 الرأي مرتبط بحساب طالب ولا يملكه: الاقتباس ما قاله الطالب،
        | والاسم والفرع والمدينة تُقرأ من حسابه ما دام قائماً. حذف الحساب
        | يفكّ الارتباط ولا يمحو كلامه.
        */
        Route::prefix('testimonials')->name('testimonials.')->group(function () {
            Route::get('/', [TestimonialController::class, 'index'])
                ->middleware('can:view-testimonials')->name('index');

            Route::middleware('can:create-testimonials')->group(function () {
                Route::get('create', [TestimonialController::class, 'create'])->name('create');
                Route::post('/', [TestimonialController::class, 'store'])->name('store');
            });

            Route::middleware('can:edit-testimonials')->group(function () {
                Route::get('{testimonial}/edit', [TestimonialController::class, 'edit'])->name('edit');
                Route::put('{testimonial}', [TestimonialController::class, 'update'])->name('update');
                Route::patch('{testimonial}/toggle', [TestimonialController::class, 'toggle'])->name('toggle');
            });

            Route::delete('{testimonial}', [TestimonialController::class, 'destroy'])
                ->middleware('can:delete-testimonials')->name('destroy');
        });

        /*
        |----------------------------------------------------------------
        | الأسئلة الشائعة — كيان مستقلّ بشاشاته
        |----------------------------------------------------------------
        | 🔴 خارج الإعدادات كبقية الكيانات: السؤال سجلّ له ترتيب وظهور
        | وحذف ناعم وأربع صلاحيات. ما يبقى في تبويب الإعدادات هو **ترويسة
        | القسم** وزرّ التواصل وحدهما.
        */
        Route::prefix('faqs')->name('faqs.')->group(function () {
            Route::get('/', [FaqController::class, 'index'])
                ->middleware('can:view-faqs')->name('index');

            Route::middleware('can:create-faqs')->group(function () {
                Route::get('create', [FaqController::class, 'create'])->name('create');
                Route::post('/', [FaqController::class, 'store'])->name('store');
            });

            Route::middleware('can:edit-faqs')->group(function () {
                Route::get('{faq}/edit', [FaqController::class, 'edit'])->name('edit');
                Route::put('{faq}', [FaqController::class, 'update'])->name('update');
                Route::patch('{faq}/toggle', [FaqController::class, 'toggle'])->name('toggle');
            });

            Route::delete('{faq}', [FaqController::class, 'destroy'])
                ->middleware('can:delete-faqs')->name('destroy');
        });

        /*
        |----------------------------------------------------------------
        | رسائل التواصل — صندوق وارد لا CRUD
        |----------------------------------------------------------------
        | 🔴 بلا create ولا edit: الرسائل يكتبها الزوّار عبر النموذج العام،
        | والأدمن يقرأ ويردّ ويحذف. صلاحية «إنشاء» هنا كانت ستكون مفتاحاً
        | في شاشة الأدوار لا يحرس شيئاً.
        |
        | ⚠️ الردّ POST لا GET: يكتب في القاعدة ويُرسل بريداً.
        */
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [ContactMessageController::class, 'index'])
                ->middleware('can:view-messages')->name('index');

            Route::get('{message}', [ContactMessageController::class, 'show'])
                ->middleware('can:view-messages')->name('show');

            Route::post('{message}/reply', [ContactMessageController::class, 'reply'])
                ->middleware('can:reply-messages')->name('reply');

            Route::delete('{message}', [ContactMessageController::class, 'destroy'])
                ->middleware('can:delete-messages')->name('destroy');
        });

        /*
        |----------------------------------------------------------------
        | المواد الدراسية
        |----------------------------------------------------------------
        | ⚠️ تبديل النشر PATCH والحذف DELETE — لا GET لأيّهما.
        */
        Route::prefix('subjects')->name('subjects.')->group(function () {
            Route::get('/', [SubjectController::class, 'index'])
                ->middleware('can:view-subjects')->name('index');

            Route::get('create', [SubjectController::class, 'create'])
                ->middleware('can:create-subjects')->name('create');

            Route::post('/', [SubjectController::class, 'store'])
                ->middleware('can:create-subjects')->name('store');

            Route::get('{subject}', [SubjectController::class, 'show'])
                ->middleware('can:view-subjects')->name('show');

            Route::get('{subject}/edit', [SubjectController::class, 'edit'])
                ->middleware('can:edit-subjects')->name('edit');

            Route::put('{subject}', [SubjectController::class, 'update'])
                ->middleware('can:edit-subjects')->name('update');

            Route::patch('{subject}/toggle', [SubjectController::class, 'toggle'])
                ->middleware('can:edit-subjects')->name('toggle');

            Route::delete('{subject}', [SubjectController::class, 'destroy'])
                ->middleware('can:delete-subjects')->name('destroy');
        });
        /*
        |----------------------------------------------------------------
        | الأخبار
        |----------------------------------------------------------------
        | ⚠️ تبديل النشر PATCH والحذف DELETE — لا GET لأيّهما: كلاهما يغيّر
        | حالة الخادم، ومسار GET يُنفَّذ بجالب مسبق في المتصفّح أو بزاحف.
        */
        Route::prefix('news')->name('news.')->group(function () {
            Route::get('/', [NewsController::class, 'index'])
                ->middleware('can:view-news')->name('index');

            Route::get('create', [NewsController::class, 'create'])
                ->middleware('can:create-news')->name('create');

            Route::post('/', [NewsController::class, 'store'])
                ->middleware('can:create-news')->name('store');

            Route::get('{news}/edit', [NewsController::class, 'edit'])
                ->middleware('can:edit-news')->name('edit');

            Route::put('{news}', [NewsController::class, 'update'])
                ->middleware('can:edit-news')->name('update');

            Route::patch('{news}/toggle', [NewsController::class, 'toggle'])
                ->middleware('can:edit-news')->name('toggle');

            Route::delete('{news}', [NewsController::class, 'destroy'])
                ->middleware('can:delete-news')->name('destroy');
        });
        /*
        |----------------------------------------------------------------
        | النشرة البريدية
        |----------------------------------------------------------------
        | 🔴 الإرسال POST محروس بصلاحية منفصلة عن العرض: قراءة قائمة
        | المشتركين فعل بلا أثر، وإرسال حملة يغادر الخادم إلى صناديق
        | بريد لا يمكن سحبه منها. من يرى القائمة لا يُفترض أن يراسلها.
        |
        | ⚠️ تبديل الحالة PATCH والحذف DELETE — لا GET لأيّهما.
        */
        Route::prefix('newsletter')->name('newsletter.')->group(function () {
            Route::get('subscribers', [NewsletterSubscriberController::class, 'index'])
                ->middleware('can:view-newsletter')->name('subscribers.index');

            // ⚠️ يسبق {subscriber} عمداً: لولا ذلك التقط المسار المتغيّر
            // كلمة «export» كمُعرِّف وأرجع 404.
            Route::get('subscribers/export', [NewsletterSubscriberController::class, 'export'])
                ->middleware('can:view-newsletter')->name('subscribers.export');

            Route::patch('subscribers/{subscriber}/toggle', [NewsletterSubscriberController::class, 'toggle'])
                ->middleware('can:view-newsletter')->name('subscribers.toggle');

            Route::delete('subscribers/bulk', [NewsletterSubscriberController::class, 'bulkDestroy'])
                ->middleware('can:delete-newsletter')->name('subscribers.bulk-destroy');

            Route::delete('subscribers/{subscriber}', [NewsletterSubscriberController::class, 'destroy'])
                ->middleware('can:delete-newsletter')->name('subscribers.destroy');

            /*
             * سجلّ الحملات — قراءة وحذف فقط.
             *
             * 🔴 بلا edit ولا resend في المكان: الرسالة في صناديق بريد الناس
             * أصلاً، و«نسخ كحملة جديدة» يملأ نموذج الإرسال ولا يرسل شيئاً —
             * مسار GET يُرسل بريداً كان سيُنفَّذ بجالب مسبق في المتصفّح.
             */
            Route::get('campaigns', [NewsletterCampaignHistoryController::class, 'index'])
                ->middleware('can:view-campaigns')->name('campaigns.index');

            Route::delete('campaigns/{campaign}', [NewsletterCampaignHistoryController::class, 'destroy'])
                ->middleware('can:delete-campaigns')->name('campaigns.destroy');
            Route::get('send', [NewsletterCampaignController::class, 'create'])
                ->middleware('can:send-newsletter')->name('send');

            Route::post('send', [NewsletterCampaignController::class, 'store'])
                ->middleware('can:send-newsletter')->name('send.store');
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
        /*
         | 🔴 لا `can:` واحدة على المجموعة بعد الآن. الصلاحية صارت **لكل نوع**
         | (TrashableResources::permissionFor)، فاستعادة فرع لم تعد تتطلّب
         | صلاحية حذف الشرائح — وهي عبارة لم يكن أحد ليدافع عنها بصوت عالٍ.
         | الحراسة داخل الكنترولر: 404 لنوع مجهول، و403 لنوع معروف لا يملكه
         | الفاعل، وشاشة السلّة نفسها 403 لمن لا يملك أي نوع.
        */
        Route::prefix('trash')->name('trash.')->group(function () {
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
        // صفحة واحدة بتبويبات: مسار عرض واحد، ومسار حفظ لكل تبويب.
        Route::middleware('can:manage-settings')->prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::put('navbar', [SettingController::class, 'updateNavbar'])->name('navbar.update');
            Route::put('cta', [SettingController::class, 'updateCta'])->name('cta.update');
            Route::put('how', [SettingController::class, 'updateHow'])->name('how.update');
            Route::put('about', [SettingController::class, 'updateAbout'])->name('about.update');
            Route::put('why-us', [SettingController::class, 'updateWhyUs'])->name('why-us.update');
            Route::put('branches', [SettingController::class, 'updateBranches'])->name('branches.update');
            Route::put('features', [SettingController::class, 'updateFeatures'])->name('features.update');
            Route::put('pricing', [SettingController::class, 'updatePricing'])->name('pricing.update');
            Route::put('teachers', [SettingController::class, 'updateTeachers'])->name('teachers.update');
            Route::put('testimonials', [SettingController::class, 'updateTestimonials'])->name('testimonials.update');
            Route::put('faq', [SettingController::class, 'updateFaq'])->name('faq.update');
            Route::put('contact', [SettingController::class, 'updateContact'])->name('contact.update');
            Route::put('final-cta', [SettingController::class, 'updateFinalCta'])->name('final-cta.update');
            Route::put('footer', [SettingController::class, 'updateFooter'])->name('footer.update');
            Route::put('video-tour', [SettingController::class, 'updateVideoTour'])->name('video-tour.update');
            Route::put('news', [SettingController::class, 'updateNews'])->name('news.update');

            /*
             | خطوات «آلية العمل» — تُدار داخل لوح الإعدادات لا في شاشة
             | مستقلّة، فمساراتها تحت settings وتحرسها manage-settings
             | نفسها. بلا index/create/edit: النموذج معروض في اللوح.
             */
            Route::post('steps', [StepController::class, 'store'])->name('steps.store');
            Route::put('steps/{step}', [StepController::class, 'update'])->name('steps.update');
            Route::delete('steps/{step}', [StepController::class, 'destroy'])->name('steps.destroy');
        });

        /*
        |----------------------------------------------------------------
        | المستخدمون
        |----------------------------------------------------------------
        | صلاحية لكل إجراء: أدمن يراجع الحسابات قد لا يملك إنشاءها ولا حذفها.
        */
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->middleware('can:view-users')->name('index');

            Route::middleware('can:create-users')->group(function () {
                Route::get('create', [UserController::class, 'create'])->name('create');
                Route::post('/', [UserController::class, 'store'])->name('store');
            });

            Route::middleware('can:edit-users')->group(function () {
                Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
                Route::put('{user}', [UserController::class, 'update'])->name('update');
                Route::patch('{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
            });

            Route::delete('{user}', [UserController::class, 'destroy'])
                ->middleware('can:delete-users')->name('destroy');
        });

        /*
        |----------------------------------------------------------------
        | الأدوار والصلاحيات
        |----------------------------------------------------------------
        | 🔴 الأدوار الثلاثة النظامية (student · teacher · admin) تقابل
        | جداول المجال ولا تُحذف ولا يُغيَّر اسمها — User::roleName() تشتقّ
        | الدور من userable_type بهذه الأسماء حرفياً. أدوار إضافية تُنشأ
        | وتُحذف بحرّية.
        */
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])
                ->middleware('can:view-roles')->name('index');

            Route::middleware('can:create-roles')->group(function () {
                Route::get('create', [RoleController::class, 'create'])->name('create');
                Route::post('/', [RoleController::class, 'store'])->name('store');
            });

            Route::middleware('can:manage-roles')->group(function () {
                Route::get('{role}/edit', [RoleController::class, 'edit'])->name('edit');
                Route::put('{role}', [RoleController::class, 'update'])->name('update');
                Route::patch('{role}/permissions', [RoleController::class, 'togglePermission'])
                    ->name('permissions.toggle');
                Route::patch('{role}/permissions/group', [RoleController::class, 'toggleGroup'])
                    ->name('permissions.group');
            });

            Route::delete('{role}', [RoleController::class, 'destroy'])
                ->middleware('can:delete-roles')->name('destroy');
        });
    });
