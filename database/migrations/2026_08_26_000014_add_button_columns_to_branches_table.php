<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| branches — إضافة زرّ دعوة قابل للتخصيص
|--------------------------------------------------------------------------
| هجرة **تعديل** لا إنشاء: جدول branches حيّ منذ 2026-08-24 وفيه بيانات
| (الفروع الأربعة + طلاب مرتبطون به بمفتاح restrict). إعادة إنشائه تعني
| فقدانها.
|
| العمودان قابلان للفراغ عمداً: الفروع الأربعة القائمة لا تحمل زرّاً،
| وعمود إجباري كان سيتطلّب قيمة افتراضية مخترَعة لكل صفّ موجود.
| null يعني «استعمل الزرّ الافتراضي للبطاقة» لا «زرّ فارغ».
|
| ⚠️ إضافة العمودين إلى $fillable في نموذج Branch جزء من هذه الدفعة نفسها.
| الهجرة والنموذج يتحرّكان معاً دائماً — عمود موجود في القاعدة وغائب عن
| $fillable يُسقطه Eloquent بصمت بلا خطأ ولا تحذير.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('btn_text', 80)->nullable()->after('accent_token');
            $table->string('btn_link', 500)->nullable()->after('btn_text');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['btn_text', 'btn_link']);
        });
    }
};
