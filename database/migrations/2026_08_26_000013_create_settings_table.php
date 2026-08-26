<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| settings — مخزن مفتاح/قيمة لإعدادات الموقع
|--------------------------------------------------------------------------
| key فريد **عالمياً** بلا مجموعة: التسمية بالنقاط تحمل المجموعة داخل
| المفتاح نفسه (site.name · contact.phone · social.facebook)، فيبقى
| الاستعلام مفتاحاً واحداً لا زوجاً مركّباً.
|
| 🔴 تحفّظ مقصود ومسجَّل. §3.32 من وثيقة المعمارية تُعرّف جدولاً أغنى
| للمرحلة 10، فيه group و value_type و **is_public** و updated_by. هذه
| النسخة المبسّطة تخدم إعدادات الموقع العامة (اسم · هاتف · روابط تواصل)
| وهي كافية لها تماماً.
|
| ⚠️ الأثر الأمني الوحيد: بلا is_public لا تستطيع القاعدة التمييز بين
| «آمن للتذييل العام» و«خاص». ولهذا:
| **لا تُخزَّن هنا إعدادات التقييم (عتبة 90% · سقف المحاولات · سياسة
| الاحتساب) ولا أي قيمة لا يجوز أن يراها زائر غير مسجّل** حتى يُضاف
| is_public. أي استعلام مهمل هنا اليوم يُسرّب كل ما في الجدول.
| المصالحة مؤجّلة للمرحلة 10: إمّا توسيع هذا الجدول أو إعادة كتابة §3.32.
|
| بلا حذف ناعم: الإعداد يُحدَّث أو يُحذف نهائياً، ولا معنى لـ«سلّة محذوفات»
| لصفّ مفتاح/قيمة.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // unique() يُنشئ الفهرس ضمناً — لا حاجة لـindex() منفصل عليه.
            $table->string('key', 120)->unique('settings_key_unique');
            $table->text('value')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
