<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| steps — خطوات «كيف تبدأ» في الصفحة الرئيسية
|--------------------------------------------------------------------------
| 🔴 نصّي بالكامل — **لا وسائط**. لا عمود صورة، ولا علاقة morph في النموذج.
| الخطوة تُعرَض برقمها وأيقونة من مجموعة x-icon (اسم مكوّن، لا ملف)، فلا
| شيء هنا يحتاج رفعاً.
|
| 🔴 display_order هنا **دلالي لا تجميلي**: «سجّل ← اختر فرعك ← ادفع ←
| ابدأ» تسلسل يفقد معناه إن اختلّ ترتيبه، بخلاف قائمة مزايا قابلة للخلط.
|
| icon يخزّن **اسم مكوّن** من مجموعة x-icon لا مسار ملف. اسم غير موجود
| يُنتج بطاقة بلا أيقونة **بصمت** — تحقّق من الاسم عند الحفظ.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->id();

            $table->string('title', 200);
            $table->string('sub_title', 300)->nullable();

            $table->string('icon', 60)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'display_order'], 'steps_active_order_index');
            $table->index('deleted_at', 'steps_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
