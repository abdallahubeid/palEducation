<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| media — مكتبة وسائط متعدّدة الأشكال
|--------------------------------------------------------------------------
| 🔴 نطاق هذا الجدول: **أصول تسويقية عامة فقط** — صور السلايدر وصور أصحاب
| الآراء. هذه محتوى دعائي يُقصد أن يراه الزائر غير المسجّل.
|
| 🔴 لا يُستعمل للمحتوى المدفوع أبداً. ملفات المكتبة وفيديوهات المحاضرات
| تبقى في library_files و lectures على **القرص الخاص**، ويُسلَّمان عبر
| controller يفحص الصلاحية والاشتراك. إرفاق محاضرة أو ملف مكتبة هنا يعني
| نقل محتوى مدفوع إلى قرص عام = تسريب مجاني يهدم نموذج العمل.
| الحارس البنيوي: لا Lecture ولا LibraryFile يُعرّف علاقة morph إلى هنا.
|
| ⚠️ mediable_id بلا مفتاح أجنبي — طبيعة الجداول متعدّدة الأشكال. يترتّب
| على ذلك أن حذف الأب حذفاً نهائياً **لا ينظّف** صفوف الوسائط تلقائياً؛
| تُنظَّف صراحةً في مهمة الحذف النهائي بعد 30 يوماً. (نفس تحفّظ Spatie
| على model_has_roles.)
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            // مسار نسبي داخل القرص العام — لا رابط مطلق: تغيير النطاق أو
            // إضافة CDN لاحقاً يجب ألّا يتطلّب تحديث كل صفّ.
            $table->string('path', 500);
            $table->string('type', 20)->default('image');

            $table->string('alt_text', 200)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);

            // morphs() يُنشئ الفهرس المركّب [mediable_type, mediable_id]
            // تلقائياً — بترتيب النوع أولاً، وهو الصحيح: الاستعلام دائماً
            // يعرف النوع، والبادئة تبقى صالحة للمطابقة الجزئية.
            $table->morphs('mediable');

            $table->timestamps();
            $table->softDeletes();

            // ⚠️ softDeletes() لا يُنشئ فهرساً — يُعلَن صراحةً دائماً.
            $table->index('deleted_at', 'media_deleted_at_index');

            // 🔴 لا فهرس ثالث على [mediable_type, mediable_id, display_order]:
            // بادئته هي فهرس morphs() نفسه، فوجودهما معاً يكلّف كل كتابة
            // مرتين ليوفّر filesort على حفنة صفوف لكل أب. الفهرس القائم يكفي.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
