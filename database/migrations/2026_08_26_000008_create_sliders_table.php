<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| sliders — شرائح الهيرو في الصفحة الرئيسية
|--------------------------------------------------------------------------
| تُحوّل مصفوفة $slides الثابتة في home.blade.php إلى محتوى يديره الأدمن.
|
| زرّان لا زرّ واحد: الشريحة تحمل دعوة أساسية (ابدأ الآن) وأخرى ثانوية
| (تصفّح الفروع). كلاهما قابل للفراغ — شريحة إعلانية بلا أزرار مشروعة.
|
| 🔴 قاعدة التصميم «زر أساسي واحد لكل شاشة» تبقى قائمة: btn1 هو الأساسي
| و btn2 ثانوي في العرض، لا زرّان أساسيان.
|
| 🔴 الصورة **ليست عموداً هنا** — تأتي من جدول media متعدّد الأشكال.
| هذا يسمح بشريحة بصورتين (سطح مكتب/جوال) بلا تعديل الجدول.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();

            $table->string('badge_text', 120)->nullable();
            $table->string('title', 200);
            $table->string('sub_title', 300)->nullable();

            $table->string('btn1_text', 80)->nullable();
            $table->string('btn1_link', 500)->nullable();
            $table->string('btn2_text', 80)->nullable();
            $table->string('btn2_link', 500)->nullable();

            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // الاستعلام الوحيد لهذا الجدول: الشرائح الفعّالة مرتّبة.
            $table->index(['is_active', 'display_order'], 'sliders_active_order_index');
            $table->index('deleted_at', 'sliders_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
