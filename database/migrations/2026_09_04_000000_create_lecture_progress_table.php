<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تقدّم مشاهدة الطالب لكل محاضرة — م-5.
 *
 * 🔴 اكتمال المشاهدة تحقّق خادمي موثوق لا علم عميل: المشغّل يرسل المقطع
 * المُشاهَد منذ آخر نبضة، والخادم يدمجه في `covered_intervals` ويعيد حساب
 * `watched_seconds` (مجموع الثواني الفريدة). لا حقل «اكتمل» يرسله العميل.
 *
 * ⚠️ صفّ لكل (طالب × محاضرة) — فريد. لا SoftDeletes: هذا سجلّ نشاط لا محتوى
 * مستخدم، ولا مكان له في سلّة المحذوفات.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecture_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('lecture_id')->constrained('lectures')->cascadeOnDelete();

            /*
             * فترات التغطية المدموجة: [[start,end], …] بالثواني، غير متداخلة
             * ومرتّبة. هي مصدر الحقيقة؛ watched_seconds مشتقّ محفوظ للاستعلام.
             */
            $table->json('covered_intervals')->nullable();
            $table->unsignedInteger('watched_seconds')->default(0);

            // لقطة مدّة الفيديو وقت آخر نبضة — تتغيّر المدّة نادراً، وحفظها
            // يجعل حساب النسبة مستقلاً عن قراءة lectures لاحقاً.
            $table->unsignedInteger('duration_seconds')->nullable();

            // 🔴 يكتبه الخادم فقط عند بلوغ العتبة. null = لم تكتمل بعد.
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'lecture_id']);
            // شاشة تقدّم الطالب تستعلم بـstudent_id + completed_at.
            $table->index(['student_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecture_progress');
    }
};
