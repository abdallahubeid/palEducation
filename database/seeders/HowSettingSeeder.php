<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * ترويسة قسم «آلية العمل» — الشارة والعنوان والنصّ التمهيدي.
 *
 * الخطوات نفسها في StepSeeder: هذه ثلاثة حقول ثابتة، وتلك قائمة
 * متغيّرة الطول لها جدولها.
 *
 * 🔴 firstOrCreate لا updateOrCreate: إعادة تشغيل البذور يجب ألّا تمسح
 * نصّاً حرّره الأدمن.
 */
class HowSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::HOW_DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
