<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| subject_teacher — إسناد المواد للمعلمين (كيان اقتراني لا جسر خام)
|--------------------------------------------------------------------------
| م-10: معلّم واحد لكل مادة اليوم، والجدول يحتمل الكثرة بلا هجرة لاحقاً.
|
| 🔴 هذا الصفّ يُظهر قاعدة المفاتيح الأجنبية في المشروع مصغّرة:
|   teacher_id  → مرجع **مجال**  → يشير إلى teachers  (مَن صاحب المادة؟)
|   assigned_by → مرجع **فاعل**  → يشير إلى users     (مَن أسندها؟)
| العمودان متشابهان شكلاً ومختلفان معنى تماماً. ولمّا كان teacher_id يشير
| إلى teachers لا users، صار إسناد **طالب** ليُدرّس مادة مستحيلاً بنيوياً
| لا ممنوعاً بسياسة.
|
| cascadeOnDelete على الطرفين: إسناد بلا مادة أو بلا معلّم لا معنى له —
| هذا الصفّ علاقة لا كيان مستقل. وهو الاستثناء الوحيد من قاعدة «العمود
| الفقري الأكاديمي يقيّد ولا يتتالى»، لأن ما يُحذف هنا ليس محتوى.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            // الوجه العام للمادة حين يُسنَد إليها أكثر من معلّم.
            $table->boolean('is_primary')->default(true);

            $table->timestamp('assigned_at')->useCurrent();

            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->foreign('assigned_by', 'subject_teacher_assigned_by_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();

            // بلا timestamps: صفّ اقتراني، وassigned_at يحمل زمنه الوحيد المفيد.

            $table->unique(['subject_id', 'teacher_id'], 'subject_teacher_unique');

            // «موادّي» — الاستعلام الأول في كل شاشة معلّم.
            $table->index(['teacher_id', 'is_primary'], 'subject_teacher_teacher_primary_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_teacher');
    }
};
