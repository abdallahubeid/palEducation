<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| testimonials — آراء الطلبة
|--------------------------------------------------------------------------
| 🔴 rate عدد صحيح صغير بلا إشارة (1–5 نجوم). لا float:
|   ١) التقييم هنا اختيار من خمسة أزرار لا قياس مستمرّ
|   ٢) مقارنة الأعداد العشرية غير دقيقة، فـ`rate = 4.5` قد لا يطابق نفسه
| المدى 1–5 يُفرض في التطبيق: unsignedTinyInteger يقبل حتى 255، والقاعدة
| لا تعرف أن 7 نجوم لا وجود لها.
|
| 🔴 name و title نصّ حرّ لا مفتاح أجنبي إلى students عمداً: الرأي محتوى
| تسويقي منشور، وربطه بحساب حقيقي يعني أن حذف الطالب لحسابه يهدم قسماً في
| الصفحة الرئيسية — أو أسوأ، يكشف أن فلاناً مشترك. الرأي **لقطة** لا مرجع.
|
| الصورة من جدول media متعدّد الأشكال، لا عموداً هنا.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();

            $table->string('name', 160);
            $table->string('title', 160)->nullable();

            $table->unsignedTinyInteger('rate')->default(5);
            $table->text('description');

            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'display_order'], 'testimonials_active_order_index');
            $table->index('deleted_at', 'testimonials_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
