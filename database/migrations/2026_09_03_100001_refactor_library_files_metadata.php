<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| library_files — بيانات تعليمية فقط
|--------------------------------------------------------------------------
| 🔴 مقاييس الملف (المسار · النوع · الحجم · الاسم الأصلي) تنتقل إلى `media`،
| ويبقى هنا ما هو **تعليمي**: المادة والوحدة والفئة والنشر.
|
| ⚠️ آمن بلا ترحيل بيانات: الجدول فارغ (صفر صفوف) وقت هذه الهجرة. لو كان
| يحمل صفوفاً لوجب نسخها إلى media **قبل** إسقاط الأعمدة — إسقاطها أولاً
| حذفُ محتوى لا تغييرُ بنية.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_files', function (Blueprint $table) {
            /*
             * الفئة التعليمية — غير mime_type تماماً. «امتحان» و«ملخّص»
             * نيّتان مختلفتان للطالب، وكلاهما PDF.
             */
            $table->string('category', 20)->default('summary')->after('title');

            // نصّ حرّ، نفس اصطلاح lectures.unit_name — لا جدول وحدات.
            $table->string('unit_name', 160)->nullable()->after('category');

            // يُملأ للـPDF حين يُعرَف فقط؛ لا يُخترع.
            $table->unsignedSmallInteger('page_count')->nullable()->after('unit_name');

            // تاريخ النشر ≠ تاريخ الرفع: «امتحان 2024» يحتاج تاريخه.
            $table->timestamp('published_at')->nullable()->after('is_published');

            $table->index(['subject_id', 'category']);
            $table->index(['subject_id', 'unit_name']);
        });

        Schema::table('library_files', function (Blueprint $table) {
            $table->dropColumn(['storage_path', 'mime_type', 'size_bytes', 'original_filename']);
        });
    }

    public function down(): void
    {
        Schema::table('library_files', function (Blueprint $table) {
            $table->string('original_filename', 255)->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->dropIndex(['subject_id', 'category']);
            $table->dropIndex(['subject_id', 'unit_name']);
            $table->dropColumn(['category', 'unit_name', 'page_count', 'published_at']);
        });
    }
};
