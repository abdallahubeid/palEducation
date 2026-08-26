<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| subjects — المواد
|--------------------------------------------------------------------------
| 🔴 بلا academic_term_id عمداً. المادة كيان **دائم** («رياضيات العلمي»)،
| والفصل الدراسي يربط بمحتواها لا بها: lectures.academic_term_id. لو ربطت
| المادة بفصل لاحتاج كل فصل نسخة جديدة من كل مادة — وانكسر كل ما يشير
| إليها (اشتراكات، أسعار، إسناد معلمين) عند كل فصل.
|
| 🔴 slug فريد **داخل الفرع** لا عالمياً: العلمي والأدبي كلاهما يدرّس
| «لغة إنجليزية»، وهما منتجان مختلفان بسعرين مختلفين ومعلّمين مختلفين.
|
| restrictOnDelete على branch_id: حذف فرع فيه مواد يفشل بصوت عالٍ. التتالي
| هنا يعني أن ضغطة واحدة في جدول أدمن تمحو مواد ومحاضرات وكويزات ومحاولات
| ومدفوعات تحتها — خسارة غير قابلة للاسترجاع من قرار عابر.
|
| price_override_agora عدد صحيح بالأغورة لا عشري — المال لا يكون float
| أبداً (الثابت 20). null يعني «استعمل سعر الخطة».
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->string('name', 140);
            $table->string('slug', 160);
            $table->text('description')->nullable();

            // 🔴 اسم مكوّن أيقونة واسم توكن تصميم — لا مسار ملف ولا قيمة hex.
            // واسم التوكن القادم من القاعدة يمرّ عبر مصفوفة PHP ثابتة تربطه
            // بصنف Tailwind كاملاً حرفياً (فاحص Tailwind لا ينفّذ PHP).
            $table->string('icon', 60)->nullable();
            $table->string('accent_token', 40)->nullable();

            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedInteger('price_override_agora')->nullable();
            $table->boolean('is_published')->default(false);

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('deleted_by', 'subjects_deleted_by_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->unique(['branch_id', 'slug'], 'subjects_branch_slug_unique');

            // قائمة مواد الفرع المنشورة مرتّبة — استعلام كل شاشة طالب.
            $table->index(['branch_id', 'is_published', 'display_order'], 'subjects_branch_published_order_index');

            // ⚠️ softDeletes() لا يُنشئ فهرساً، وكل قراءة Eloquent تضيف
            // deleted_at IS NULL ضمنياً. يُعلَن صراحةً دائماً.
            $table->index('deleted_at', 'subjects_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
