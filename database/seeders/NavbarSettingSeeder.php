<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * مفاتيح روابط التواصل في الشريط العلوي.
 *
 * تُبذَر **فارغة** لا بروابط وهمية: رابط تواصل خاطئ في الشريط العلوي أسوأ
 * من غيابه — الزائر يضغطه فيصل إلى حساب لا يخصّ المنصة. الصفوف موجودة
 * ليجدها نموذج الإعدادات، والقيم يملؤها الأدمن.
 *
 * 🔴 هذه المفاتيح عامة بطبيعتها (تُعرَض لزائر غير مسجّل)، وهو بالضبط ما
 * يجعلها آمنة في جدول settings المبسّط الذي لا يملك عمود is_public بعد.
 * لا تُضِف هنا أي مفتاح لا يجوز أن يُقرأ علناً.
 */
class NavbarSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::NAVBAR_SOCIAL_KEYS as $key) {
            // firstOrCreate لا updateOrCreate: إعادة تشغيل البذور يجب ألّا
            // تمسح رابطاً أدخله الأدمن فعلاً.
            Setting::firstOrCreate(['key' => $key], ['value' => null]);
        }
    }
}
