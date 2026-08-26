<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| features — بطاقات المزايا في الصفحة الرئيسية
|--------------------------------------------------------------------------
| تُحوّل مصفوفة $features الثابتة في home.blade.php إلى محتوى مُدار.
|
| 🔴 نصّي بالكامل — **لا وسائط**. لا عمود صورة ولا علاقة morph في النموذج.
| البطاقة تُعرَض بأيقونة من x-icon، لا بصورة مرفوعة.
|
| زرّ واحد اختياري لا زرّان: بطاقة الميزة دعوة فرعية داخل صفحة لها دعوتها
| الأساسية. أربع بطاقات بأربعة أزرار أساسية تُفتّت انتباه الزائر.
|
| accent_token يخزّن **اسم توكن** لا قيمة hex — ويجب أن يمرّ عبر مصفوفة
| PHP ثابتة تربطه بصنف Tailwind كاملاً حرفياً. فاحص Tailwind لا ينفّذ PHP،
| فـ"bg-{$tone}/14" لا يولّد أي CSS. (راجع doctrine.md)
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();

            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->string('btn_text', 80)->nullable();
            $table->string('btn_link', 500)->nullable();

            $table->string('icon', 60)->nullable();
            $table->string('accent_token', 40)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'display_order'], 'features_active_order_index');
            $table->index('deleted_at', 'features_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
