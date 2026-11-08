<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| media — قرص التخزين ومقاييس الملف
|--------------------------------------------------------------------------
| 🔴 `disk` هو ما يجعل ضمّ ملفات المكتبة إلى هذا الجدول آمناً.
|
| كان الجدول للأصول التسويقية العامة حصراً، والحارس بنيوياً: لا LibraryFile
| يُعرّف morph إليه إطلاقاً. القرار الآن توحيد بيانات الملفات هنا — وهذا
| ينزع ذلك الحارس، فيجب أن يحلّ محلّه حارس أقوى من مجرّد عُرف:
|
|   ١) كل صفّ يعلن قرصه صراحةً. الافتراضي public للأصول القائمة.
|   ٢) Media::url() **يرفض** بناء رابط عامّ لصفّ على قرص خاص ويُرجع null.
|   ٣) ملف المكتبة يُسلَّم عبر controller يفحص الصلاحية، لا برابط.
|
| ⚠️ بلا هذا العمود يصير `asset($path)` على ملف مكتبة رابطاً عاماً صالحاً —
| أي تسريب محتوى مدفوع، وهو ما تمنعه قاعدة المشروع منذ اليوم الأول.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // ⚠️ افتراضي public: الصفوف الثمانية عشر القائمة كلها أصول عامة،
            // وتغيير معناها بأثر رجعي كان سيكسر كل صورة على الموقع.
            $table->string('disk', 20)->default('public')->after('path');

            // مقاييس الملف — تنتقل من library_files إلى هنا.
            $table->string('original_filename', 255)->nullable()->after('type');
            $table->string('mime_type', 120)->nullable()->after('original_filename');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['disk', 'original_filename', 'mime_type', 'size_bytes']);
        });
    }
};
