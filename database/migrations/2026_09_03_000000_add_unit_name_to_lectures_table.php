<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| lectures.unit_name
|--------------------------------------------------------------------------
| 🔴 نصّ حرّ لا جدول وحدات. الوحدة عند المعلّم عنوان تنظيمي («الوحدة
| الأولى: أساسيات التفاضل») لا كيان له صلاحيات ولا محتوى خاص به، وجدول
| مستقل كان سيفرض شاشة إدارة كاملة لقيمة يكتبها المعلّم مرة.
|
| ⚠️ nullable عمداً: كل المحاضرات القائمة بلا وحدة، ولا يجوز أن يمنعها ذلك
| من الظهور — تُجمَّع تحت مجموعة افتراضية في العرض.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->string('unit_name', 160)->nullable()->after('description');

            // الاستعلام الوحيد الذي يلمسه: تجميع محاضرات مادة واحدة.
            $table->index(['subject_id', 'unit_name']);
        });
    }

    public function down(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->dropIndex(['subject_id', 'unit_name']);
            $table->dropColumn('unit_name');
        });
    }
};
