<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * وصول الطالب إلى ملفات المكتبة — لعدّاد «موارد مكتبة اطُّلع عليها».
 *
 * 🔴 library_files.download_count عدّاد مجرّد لا يعرف مَن نزّل. هذا الجدول
 * يجعل «كم مورداً وصل إليه هذا الطالب» رقماً حقيقياً. صفّ واحد لكل
 * (طالب × ملف)، وعدّاد داخلي لتكرار التنزيل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_library_access', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('library_file_id')->constrained('library_files')->cascadeOnDelete();

            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'library_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_library_access');
    }
};
