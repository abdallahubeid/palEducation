<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| teachers — سجلّ المجال للمعلم
|--------------------------------------------------------------------------
| صفوفه تُقرأ من زوّار غير مسجّلين (صفحة الفرع)، ولهذا فُصل عن جدول
| الاعتماد. is_publicly_listed افتراضياً false — الظهور العام اختيار
| صريح من المعلم لا أمر واقع.
|
| 🔴 social_links محتوى يكتبه المعلم ويُعرَض في صفحة عامة. تحقّق من
| شكله ومن مخطّط الرابط (https فقط) وقت الحفظ لا وقت العرض.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->string('full_name', 160);
            $table->string('title', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path', 255)->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('is_publicly_listed')->default(false)->index();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
