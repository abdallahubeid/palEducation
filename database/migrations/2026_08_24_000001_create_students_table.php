<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| students — سجلّ المجال للطالب
|--------------------------------------------------------------------------
| هنا يعيش الاسم والفرع، لا في users.
|
| 🔴 branch_id إجباري بمفتاح أجنبي. هذا بالضبط ما لم تستطعه النسخة
| السابقة من التصميم (حين كان العمود في users اضطرّ أن يكون قابلاً
| للفراغ لأن المعلم والأدمن بلا فرع). كل صفّ هنا طالب بالتعريف، فصار
| «طالب بلا فرع» مستحيلاً بنيوياً لا ممنوعاً بقاعدة.
|
| restrictOnDelete: حذف فرع فيه طلاب يجب أن يفشل بصوت عالٍ لا أن يمسحهم.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('full_name', 160);

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->string('school_name', 160)->nullable();
            $table->string('governorate', 80)->nullable();
            $table->string('country_code', 2)->nullable();

            // هاتف الطالب نفسه — بيانات تواصل لا هوية (الهوية بريد إلكتروني).
            $table->string('phone', 32)->nullable();
            $table->string('guardian_phone', 32)->nullable();

            $table->string('avatar_path', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
