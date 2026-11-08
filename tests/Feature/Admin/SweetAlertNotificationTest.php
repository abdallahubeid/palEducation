<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Slider;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;

/*
| SweetAlert2 notifications and confirmations.
|
| 🔴 SweetAlert is an UPGRADE over a working baseline, not a replacement for
| it. The server still renders every message as visible HTML, and the script
| reads those nodes and re-presents them as toasts. That layering is the
| whole point: notifications broke three separate times in this project and
| every failure was silent, so the text staying in the HTML is what makes a
| broken toast visible instead of invisible.
|
| These tests pin the seams between the two layers — the markup the script
| reads, and the attributes it acts on.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

// ── The asset is bundled, never fetched ─────────────────────────

it('bundles sweetalert2 through vite rather than a CDN', function () {
    // 🔴 The panel must work offline. A CDN tag would also be the fourth
    // way notifications have silently failed here.
    $packages = json_decode(file_get_contents(base_path('package.json')), true);

    expect($packages['dependencies'] ?? [])->toHaveKey('sweetalert2');

    $js = file_get_contents(resource_path('js/app.js'));

    expect($js)->toContain("import Swal from 'sweetalert2'")
        ->toContain("import 'sweetalert2/dist/sweetalert2.min.css'");

    $layout = file_get_contents(resource_path('views/components/layouts/admin.blade.php'));

    expect($layout)->toContain('@vite')
        ->not->toContain('cdn.jsdelivr')
        ->not->toContain('unpkg.com');
});

// ── Flash session states the toast reads ────────────────────────

it('keeps every flash tone readable in the markup the toast reads', function () {
    // The script keys on these tone classes, so they are a contract now.
    $slider = Slider::create(['title' => 'شريحة']);

    actingAs($this->admin)->from(route('admin.sliders.index'))
        ->patch(route('admin.sliders.toggle', $slider));

    $html = actingAs($this->admin)->get(route('admin.sliders.index'))->getContent();

    expect($html)->toContain('data-flash')
        ->toContain('data-flash-item')
        ->toContain('bg-tag/10')                       // info tone
        ->toContain('تم تغيير الحالة بنجاح.');          // raw, not JSON-encoded
});

it('exposes the undo form to the toast without turning it into a link', function () {
    // 🔴 The toast SUBMITS this form; it never links to it. A restore
    // mutates server state, and an <a> injected by JS carries no CSRF token.
    $slider = Slider::create(['title' => 'شريحة']);

    actingAs($this->admin)->from(route('admin.sliders.index'))
        ->delete(route('admin.sliders.destroy', $slider));

    $html = actingAs($this->admin)->get(route('admin.sliders.index'))->getContent();

    expect($html)->toContain('data-admin-undo')
        ->toContain('data-undo-label')
        ->toContain('name="_method" value="PATCH"')
        ->toContain('name="_token"');
});

// ── Confirmation dialogs ────────────────────────────────────────

it('drives every destructive form through data-confirm, not window.confirm', function () {
    // window.confirm cannot be styled and reads as a browser warning rather
    // than as part of the panel. One leftover would look like a bug.
    $views = collect(File::allFiles(resource_path('views/admin')))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'));

    $offenders = $views
        ->filter(fn ($file) => str_contains(File::get($file->getPathname()), 'onsubmit="return confirm('))
        ->map(fn ($file) => $file->getFilename())
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

it('asks before deleting a branch and before emptying the trash', function () {
    Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific',
        'icon' => 'beaker', 'accent_token' => 'accent', 'display_order' => 1,
    ]);

    actingAs($this->admin)->get(route('admin.branches.index'))
        ->assertOk()
        ->assertSee('data-confirm-title', escape: false)
        ->assertSee('data-confirm-action', escape: false);

    tap(Slider::create(['title' => 'شريحة']))->delete();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        // Permanent deletion is the one that cannot be undone, so it is the
        // one that must never fire on a stray click.
        ->assertSee('data-confirm-action', escape: false)
        ->assertSee('لا يمكن استعادة العنصر ولا مرفقاته بعد هذا الإجراء.');
});

it('does not gate the delete route behind the dialog', function () {
    // ⚠️ The confirmation is UX. The guard is the route permission, and a
    // direct POST with no dialog must still be authorised and still work.
    $branch = Branch::create([
        'name' => 'فرع', 'slug' => 'gone', 'icon' => 'book',
        'accent_token' => 'tag', 'display_order' => 9,
    ]);

    actingAs($this->admin)->delete(route('admin.branches.destroy', $branch));

    expect(Branch::whereKey($branch->id)->exists())->toBeFalse()
        ->and(Branch::withTrashed()->whereKey($branch->id)->exists())->toBeTrue();
});

// ── One dialog shape for the whole panel ────────────────────────

it('routes every alert through a single mixin, with no corner toasts left', function () {
    /*
     * 🔴 The unification this guards. Mixing corner toasts with centre
     * modals made identical outcomes look like different kinds of event:
     * a delete that whispered from the corner and a delete that stopped the
     * page read as two separate features rather than one.
     */
    $js = file_get_contents(resource_path('js/app.js'));

    expect($js)->toContain('const swalPanel = Swal.mixin(SWAL_BASE)')
        // Both the outcome notice and the confirmation use the same mixin.
        ->toContain('swalPanel')
        // No toast configuration survives anywhere.
        ->not->toContain('toast: true')
        ->not->toContain('swalToast');
});

it('styles the dialog from design tokens inside a layer', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // 🔴 Layered on purpose: SweetAlert2 ships UNLAYERED CSS, which beats
    // layered CSS regardless of specificity. Plain rules here would fight
    // the library and win unpredictably.
    expect($css)->toContain('@layer components')
        ->toContain('.swal-panel')
        ->toContain('.swal-panel__btn--confirm')
        ->toContain('.swal-panel__btn--cancel')
        // Every colour is a token — no hex in this project's code.
        ->toContain('var(--color-accent)')
        ->toContain('var(--color-ink)');

    // Arabic never carries letter-spacing: it breaks the joins between
    // characters and stops the word being readable.
    expect($css)->toContain('letter-spacing: normal');
});

it('drives all four flash tones into the same dialog', function () {
    $js = file_get_contents(resource_path('js/app.js'));

    foreach (['bg-error/10', 'bg-warn/10', 'bg-tag/10', 'bg-accent/10'] as $tone) {
        expect($js)->toContain($tone);
    }

    foreach (["icon: 'error'", "'error'", "'warning'", "'info'", "'success'"] as $icon) {
        expect($js)->toContain($icon);
    }
});
