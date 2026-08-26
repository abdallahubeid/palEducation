<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| branches — الفروع
|--------------------------------------------------------------------------
| خارج نطاق «جداول المصادقة الأربعة» شكلاً، لكنه تبعية إجبارية لها:
| شاشة التسجيل تطلب من الطالب اختيار فرع، و students.branch_id بلا هذا
| الجدول قيمة معلّقة على لا شيء — أي كسر لمبدأ الإقصاء المخصّص من أول صفّ.
|
| 🔴 accent_token يخزّن اسم توكن لا قيمة hex. واسم التوكن القادم من قاعدة
| البيانات يجب أن يمرّ عبر مصفوفة PHP ثابتة تربطه بصنف Tailwind كاملاً
| حرفياً — لا تجميع نصّي (فاحص Tailwind لا ينفّذ PHP). راجع doctrine.md.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('accent_token', 40)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
