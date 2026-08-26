<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| lectures — المحاضرات
|--------------------------------------------------------------------------
| ثلاثة مفاتيح إجبارية تحدّد كل محاضرة: مادتها · فصلها · معلّمها.
|
| 🔴 academic_term_id إجباري (القرار 5) — ولهذا **يجب أن يوجد فصل دراسي
| قبل أول محاضرة، بما في ذلك في البذور**. هذه أشهر عثرة في هذا الترتيب.
|
| 🔴 teacher_id ليس «مَن أنشأ» بل **مَن يملك**. عليه تقوم قاعدة نطاق
| المعلم (يرى مواده فقط)، وإشارته إلى teachers لا users تجعل محاضرة
| يملكها طالب مستحيلة بنيوياً.
|
| 🔴 video_duration_seconds حامل للوزن لا معلومة عرض. م-5 يجعل الاكتمال
| تحقّقاً خادمياً عند 90%، و90% من مجهول لا تُحسب. **يجب أن تأتي القيمة
| من الخادم** — من واجهة المزوّد أو بفحص الملف — لا من العميل: المدة
| المُصرَّح بها من المتصفّح هي بالضبط الرقم الذي يريد المهاجم التحكّم به،
| فتقليلها يجعل 90% تصل باكراً.
|
| status و published_at منفصلان: محاضرة أُخفيت تحتفظ بتاريخ نشرها الأصلي.
| video_reference يبقى قابلاً للفراغ لأن المسودّة توجد قبل الرفع.
|
| ⏳ يبقى للمرحلة 5: hasOne(Quiz) إجبارية عند النشر (م-3)، وتحقّق أن
| video_duration_seconds موجودة قبل السماح بالانتقال إلى published.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lectures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->restrictOnDelete();

            $table->foreignId('academic_term_id')
                ->constrained('academic_terms')
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->string('slug', 220);
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);

            // ── الفيديو ───────────────────────────────────────────
            $table->string('video_source', 20)->default('local_private');
            $table->string('video_reference', 500)->nullable();
            $table->unsignedInteger('video_duration_seconds')->nullable();
            $table->string('video_status', 20)->default('pending');
            $table->string('thumbnail_path', 255)->nullable();

            // ── النشر ─────────────────────────────────────────────
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable()
                ->index('lectures_published_at_index');

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('deleted_by', 'lectures_deleted_by_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->unique(['subject_id', 'slug'], 'lectures_subject_slug_unique');

            // قائمة محاضرات المادة في فصل معيّن، منشورة ومرتّبة — الاستعلام
            // الأكثر تكراراً في المنصة. ترتيب الأعمدة مقصود: الأكثر انتقائية
            // أولاً، فتبقى المطابقة الجزئية على البادئة صالحة.
            $table->index(
                ['subject_id', 'academic_term_id', 'status', 'display_order'],
                'lectures_subject_term_status_order_index'
            );

            // نطاق المعلّم — «محاضراتي».
            $table->index(['teacher_id', 'status'], 'lectures_teacher_status_index');

            // طابور المعالجة: ما زال يُعالَج أو فشل.
            $table->index('video_status', 'lectures_video_status_index');

            $table->index('deleted_at', 'lectures_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lectures');
    }
};
