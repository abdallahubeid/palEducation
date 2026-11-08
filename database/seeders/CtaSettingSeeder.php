<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * محتوى بانر الدعوة الأصفر في الصفحة الرئيسية.
 *
 * على عكس NavbarSettingSeeder الذي يبذر **فارغاً** عمداً، هذه المفاتيح
 * تُبذَر بقيمها الفعلية: البانر جزء من التخطيط لا زخرفة اختيارية، وصفّ
 * فارغ هنا يعني عنواناً فارغاً وزرّاً بلا نصّ على الصفحة الرئيسية أمام
 * كل زائر.
 *
 * 🔴 firstOrCreate لا updateOrCreate: إعادة تشغيل البذور يجب ألّا تمسح
 * نصّاً حرّره الأدمن. القيمة الافتراضية تُكتب مرة واحدة فقط.
 */
class CtaSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::CTA_DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
