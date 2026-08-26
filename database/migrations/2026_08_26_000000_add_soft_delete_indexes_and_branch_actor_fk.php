<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| هجرة إصلاحية على جداول الهوية والفروع
|--------------------------------------------------------------------------
| أمران فاتا الدفعة الأولى، وكلاهما يكبر ثمنه مع نمو الجداول:
|
| 1) فهرس على deleted_at في الجداول الخمسة.
|    $table->softDeletes() لا يُنشئ فهرساً — والمواصفة تضع IX على العمود
|    (§3.1 · §3.2 · §3.3). كل استعلام Eloquent يضيف deleted_at IS NULL
|    ضمنياً، فبلا فهرس يصير كل قراءة مسحاً كاملاً. وشاشتا سلة المحذوفات
|    (26 · 47) تقرآن بالعمود نفسه مقلوباً (NOT NULL).
|
| 2) مفتاح أجنبي لـ branches.deleted_by → users.id.
|    students و teachers و admins تُعلنه كلها؛ branches وحدها سقط منها،
|    فبقي العمود مجرّداً من أي قيد. الثابت 21 (سجلّ التدقيق ينجو من حذف
|    من يسمّيه) يتطلّب nullOnDelete لا cascade: حذف الأدمن يُفرغ الحقل
|    ولا يمحو الفرع.
|
| ⚠️ العمود يُفهرس أولاً ثم يُقيَّد: MySQL يتطلّب فهرساً على عمود المفتاح
| الأجنبي، وتركه لـ MySQL يُنتج فهرساً باسم تلقائي غير متوقَّع.
*/
return new class extends Migration
{
    /** الجداول الخمسة التي تحمل حذفاً ناعماً حتى الآن. */
    private const SOFT_DELETE_TABLES = ['users', 'students', 'teachers', 'admins', 'branches'];

    public function up(): void
    {
        foreach (self::SOFT_DELETE_TABLES as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->index('deleted_at', "{$name}_deleted_at_index");
            });
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->index('deleted_by', 'branches_deleted_by_index');
            $table->foreign('deleted_by', 'branches_deleted_by_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign('branches_deleted_by_foreign');
            $table->dropIndex('branches_deleted_by_index');
        });

        foreach (self::SOFT_DELETE_TABLES as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropIndex("{$name}_deleted_at_index");
            });
        }
    }
};
