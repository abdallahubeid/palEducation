<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| academic_terms — الفصول الدراسية
|--------------------------------------------------------------------------
| القرار 5: جدول صريح لا حقل مشتقّ من التواريخ. السبب أن المحاضرات
| والطلبات والاشتراكات تربط به بمفتاح أجنبي إجباري — وهذا لا يُبنى على
| نطاق زمني محسوب.
|
| 🔴 بلا branch_id عمداً. الفصل الدراسي وحدة **تقويمية** لا أكاديمية:
| «الفصل الأول 2026/2027» هو نفسه للعلمي والأدبي والتجاري والصناعي.
| إضافة branch_id تعني أربعة صفوف متطابقة لكل فصل، وأربعة أخطاء محتملة
| عند ربط محاضرة بفصل «فرع آخر». الفرع يدخل عبر subjects.branch_id.
|
| 🔴 عمودان يبدوان متكرّرين وليسا كذلك:
|   status     → دورة الحياة: هل يقبل طلبات جديدة؟ (سطح فتح/إغلاق الفصل)
|   is_current → الافتراضي: أي فصل تتبنّاه الاشتراكات الجديدة؟
| فصلان قد يكونان active معاً (طالب يفتح محتوى فصل سابق دفع ثمنه بينما
| الفصل الجديد يبيع)، لكن is_current صفّ واحد. الثابت التطبيقي:
| is_current = true يستلزم status = active.
|
| ⚠️ صفّ واحد فقط يحمل is_current = true — يُفرض في التطبيق لا في القاعدة:
| الفهرس الفريد الجزئي (WHERE is_current = 1) غير محمول بين المحرّكات.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();

            $table->string('name', 80);
            $table->string('academic_year', 9)->index('academic_terms_academic_year_index');

            $table->date('starts_on');
            $table->date('ends_on');

            $table->string('status', 20)->default('upcoming')
                ->index('academic_terms_status_index');
            $table->boolean('is_current')->default(false)
                ->index('academic_terms_is_current_index');

            $table->timestamps();

            // لا حذف ناعم: الفصل سجلّ تاريخي تشير إليه محاضرات وطلبات
            // واشتراكات بمفاتيح restrict. الإغلاق يكون بـ status = archived.

            // فصل واحد بهذا الاسم في هذه السنة — يمنع «الفصل الأول» مكرّراً.
            $table->unique(['academic_year', 'name'], 'academic_terms_year_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
