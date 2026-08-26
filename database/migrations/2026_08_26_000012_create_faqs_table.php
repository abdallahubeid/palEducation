<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| faqs — الأسئلة الشائعة
|--------------------------------------------------------------------------
| answer نصّ طويل: الجواب المفيد فقرة لا سطر، وقصّه إلى string(255) يجبر
| المحرّر على اختصار يُفقد الجواب فائدته.
|
| ⚠️ الترتيب هنا يخدم الزائر لا المحرّر: السؤال الأكثر تكراراً أولاً.
| المسألة ليست تنظيمية — من لا يجد جوابه في أول سطرين يغادر.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();

            $table->string('question', 300);
            $table->text('answer');

            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'display_order'], 'faqs_active_order_index');
            $table->index('deleted_at', 'faqs_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
