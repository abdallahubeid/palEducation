<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Landing page section settings: about · why_us · branches · video_tour.
 *
 * SOURCE OF THE TEXT: no screenshots were attached to the request, so every
 * default was lifted from the live source of those sections in this
 * repository — lang/ar/home.php and resources/views/pages/home.blade.php.
 * That is what a visitor actually sees today, so the seeded values match
 * the current page byte for byte.
 *
 * 🔴 firstOrCreate, not updateOrCreate: seeders run again after every
 * deploy, and updateOrCreate would push factory copy over text the admin
 * has since edited. The default is written exactly once.
 *
 * ⚠️ Every key here is PUBLIC by nature — rendered for anonymous visitors.
 * That is precisely what makes them safe in the simplified `settings` table,
 * which still has no is_public column. Never add a key here that a visitor
 * must not read.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            Setting::ABOUT_DEFAULTS,
            Setting::WHY_US_DEFAULTS,
            Setting::BRANCHES_DEFAULTS,
            Setting::FEATURES_DEFAULTS,
            Setting::PRICING_DEFAULTS,
            Setting::TEACHERS_DEFAULTS,
            Setting::TESTIMONIALS_DEFAULTS,
            Setting::FAQ_DEFAULTS,
            Setting::CONTACT_DEFAULTS,
            Setting::FINAL_CTA_DEFAULTS,
            Setting::FOOTER_DEFAULTS,
            Setting::VIDEO_TOUR_DEFAULTS,
        ];

        foreach ($groups as $defaults) {
            foreach ($defaults as $key => $value) {
                Setting::firstOrCreate(['key' => $key], ['value' => $value]);
            }
        }
    }
}
