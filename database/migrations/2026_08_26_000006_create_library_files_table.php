<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| library_files — ملفات المكتبة
|--------------------------------------------------------------------------
| 🔴 storage_path يشير إلى **القرص الخاص** بلا استثناء. المحتوى مدفوع،
| ورابط مباشر قابل للوصول = تسريب مجاني يهدم سبب الاشتراك أصلاً. التسليم
| برابط موقّع قصير العمر عبر controller يقيّم الصلاحية أولاً — لا
| Storage::url() ولا أي شيء تحت public/.
|
| 🔴 lecture_id قابل للفراغ وnullOnDelete وحده في هذا الجدول: الملف قد
| يخصّ المادة كلها لا محاضرة بعينها، وحذف المحاضرة **لا يُفني الملف** بل
| يعيده إلى مستوى المادة. لهذا subject_id إجباري وlecture_id ليس كذلك —
| للملف دائماً بيت يرجع إليه.
|
| 🔴 عمودان لشخصين مختلفين:
|   teacher_id  → مرجع مجال: صاحب الملف، وعليه يقوم نطاق المعلّم
|   uploaded_by → مرجع فاعل: مَن ضغط «رفع» فعلاً (قد يكون أدمن ينوب عنه)
| الفصل يجعل «رفع الأدمن نيابةً عن معلّم» ممثَّلاً بلا كسر قاعدة أن
| المحتوى يملكه معلّم (الثابت 6).
|
| download_count عدّاد لا سجلّ — من نزّل ومتى سؤال يخصّ activity_log
| (المرحلة 10)، وجدول صفّ لكل تنزيل ينفجر عدداً بلا استعلام يخدمه.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->restrictOnDelete();

            $table->foreignId('lecture_id')
                ->nullable()
                ->constrained('lectures')
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->string('original_filename', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_published')->default(false);

            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by', 'library_files_uploaded_by_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('deleted_by', 'library_files_deleted_by_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();

            // مكتبة المادة كما يراها الطالب.
            $table->index(['subject_id', 'is_published'], 'library_files_subject_published_index');

            // مرفقات محاضرة بعينها.
            $table->index(['lecture_id', 'is_published'], 'library_files_lecture_published_index');

            // نطاق المعلّم — «ملفاتي».
            $table->index('teacher_id', 'library_files_teacher_index');

            $table->index('deleted_at', 'library_files_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_files');
    }
};
