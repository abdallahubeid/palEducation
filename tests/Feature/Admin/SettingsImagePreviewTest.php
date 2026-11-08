<?php

use App\Models\Admin;
use App\Models\Setting;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;

/*
| The image preview inside the "About us" settings panel.
|
| 🔴 The bug this guards: the preview was <img class="h-40 w-full"> inside a
| bare wrapper, so it stretched across the entire settings panel — on a wide
| screen a ~1000px band of photograph in the middle of a form, with portrait
| sources squashed into it.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

it('renders the about image inside a fixed-size preview card', function () {
    Setting::put('about.img', 'images/about/classroom.jpg');

    $html = actingAs($this->admin)->get(route('admin.settings.index'))->assertOk()->getContent();

    // Both axes pinned, so the box never depends on the source dimensions.
    expect($html)->toContain('size-48 shrink-0 overflow-hidden rounded-lg border border-hairline')
        // object-cover fills that box at the image's own ratio: it crops,
        // it does not distort.
        ->toContain('size-full object-cover')
        // The old full-bleed preview must not come back.
        ->not->toContain('h-40 w-full object-cover');
});

it('constrains the video preview the same way', function () {
    Setting::put('video_tour.video_path', 'uploads/settings/tour.mp4');

    $html = actingAs($this->admin)->get(route('admin.settings.index'))->assertOk()->getContent();

    expect($html)->toContain('max-w-md overflow-hidden rounded-lg border border-hairline');
});

it('shows no preview box at all when nothing is stored', function () {
    Setting::put('about.img', '');

    $html = actingAs($this->admin)->get(route('admin.settings.index'))->assertOk()->getContent();

    expect($html)->not->toContain('معاينة صورة قسم من نحن');
});
