<?php

use App\Enums\MediaType;
use App\Models\Admin;
use App\Models\Media;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/*
| The admin profile avatar — upload, replace, delete.
|
| 🔴 Three sources can supply one face: an uploaded media row, the domain
| record's seeded avatar_path, and the Google account's picture. The order is
| declared on the model and asserted here, because three sources with no
| stated precedence drift until nobody can say which one is showing.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');

    $this->admin = tap(Admin::create(['full_name' => 'سارة الأدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function avatarFile(string $name = 'face.jpg'): UploadedFile
{
    // ⚠️ ->create(), not ->image(): the GD extension is disabled in this
    // environment and fake image generation throws.
    return UploadedFile::fake()->create($name, 200, 'image/jpeg');
}

// ── Uploading ───────────────────────────────────────────────────

it('attaches an uploaded avatar as a media row', function () {
    actingAs($this->admin)
        ->post(route('admin.profile.avatar'), ['avatar' => avatarFile()])
        ->assertRedirect(route('admin.profile.edit'));

    $media = $this->admin->fresh()->image;

    expect($media)->not->toBeNull()
        // 🔴 A real polymorphic row pointing back at the ACCOUNT, not at the
        // admins record: the login is what the header renders.
        ->and($media->mediable_type)->toBe(User::class)
        ->and($media->mediable_id)->toBe($this->admin->id)
        ->and($media->type)->toBe(MediaType::Image)
        // The 'storage/' prefix is what makes asset() resolve through the
        // public symlink — the same convention sliders and news use.
        ->and($media->path)->toStartWith('storage/avatars/');

    Storage::disk('public')->assertExists(str_replace('storage/', '', $media->path));
});

it('points avatar_url at the uploaded file', function () {
    actingAs($this->admin)
        ->post(route('admin.profile.avatar'), ['avatar' => avatarFile()])
        ->assertRedirect();

    $user = $this->admin->fresh();

    expect($user->avatar_url)->toContain($user->image->path)
        ->and($user->hasUploadedAvatar())->toBeTrue();
});

it('replaces the avatar in place and deletes the old file', function () {
    actingAs($this->admin)->post(route('admin.profile.avatar'), ['avatar' => avatarFile('first.jpg')]);

    $firstPath = $this->admin->fresh()->image->path;

    actingAs($this->admin)->post(route('admin.profile.avatar'), ['avatar' => avatarFile('second.jpg')]);

    $user = $this->admin->fresh();

    /*
     * 🔴 ONE row, still. A plain create() per upload would stack a media row
     * every time until "first by display order" was whichever the query
     * happened to return.
     */
    expect($user->media()->count())->toBe(1)
        ->and($user->image->path)->not->toBe($firstPath);

    // The replaced file is removed rather than left orphaned on disk.
    Storage::disk('public')->assertMissing(str_replace('storage/', '', $firstPath));
    Storage::disk('public')->assertExists(str_replace('storage/', '', $user->image->path));
});

it('reflects the new avatar within the same request', function () {
    /*
     * ⚠️ syncAvatar unsets the loaded relation after writing. Without that,
     * anything reading avatar_url later in the SAME request would get the
     * path of the image that was just replaced.
     */
    $user = $this->admin;
    $user->load('image');

    $user->syncAvatar(avatarFile());

    expect($user->avatar_url)->toContain('storage/avatars/');
});

it('rejects a file that is not an accepted image', function () {
    actingAs($this->admin)
        ->post(route('admin.profile.avatar'), [
            'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    /*
     * 🔴 The server decides, not the cropper. The editor is a convenience;
     * a request can arrive without ever passing through it, so the type and
     * size limits are enforced here.
     */
    expect($this->admin->fresh()->hasUploadedAvatar())->toBeFalse();
});

it('rejects an image over the size limit', function () {
    actingAs($this->admin)
        ->post(route('admin.profile.avatar'), [
            'avatar' => UploadedFile::fake()->create('huge.jpg', 5000, 'image/jpeg'),
        ])
        ->assertSessionHasErrors('avatar');

    expect(Media::count())->toBe(0);
});

it('refuses an avatar upload from a guest', function () {
    post(route('admin.profile.avatar'), ['avatar' => avatarFile()])
        ->assertRedirect(route('auth.login'));

    expect(Media::count())->toBe(0);
});

it('can only change the signed-in account own avatar', function () {
    /*
     * 🔴 The guard is structural: the route carries NO id, and the controller
     * works on $request->user() alone. There is nothing to tamper with to
     * reach a colleague's avatar.
     */
    $other = tap(Admin::create(['full_name' => 'أدمن آخر'])->user()->create([
        'email' => 'other@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($this->admin)->post(route('admin.profile.avatar'), ['avatar' => avatarFile()]);

    expect($this->admin->fresh()->hasUploadedAvatar())->toBeTrue()
        ->and($other->fresh()->hasUploadedAvatar())->toBeFalse();
});

// ── Deleting ────────────────────────────────────────────────────

it('deletes the avatar row and file, and falls back', function () {
    actingAs($this->admin)->post(route('admin.profile.avatar'), ['avatar' => avatarFile()]);

    $path = $this->admin->fresh()->image->path;

    actingAs($this->admin)
        ->delete(route('admin.profile.avatar.destroy'))
        ->assertRedirect(route('admin.profile.edit'));

    $user = $this->admin->fresh();

    /*
     * 🔴 forceDelete, not delete. A soft-deleted media row would keep
     * occupying the relation and block the fallback — the account would show
     * no picture at all rather than reverting to its default.
     */
    expect(Media::withTrashed()->count())->toBe(0)
        ->and($user->hasUploadedAvatar())->toBeFalse()
        ->and($user->avatar_url)->toBeNull();

    Storage::disk('public')->assertMissing(str_replace('storage/', '', $path));
});

it('does nothing when there is no avatar to delete', function () {
    // Idempotent: the button is hidden without an upload, but a repeated
    // submit must not error.
    actingAs($this->admin)
        ->delete(route('admin.profile.avatar.destroy'))
        ->assertRedirect();

    expect($this->admin->fresh()->avatar_url)->toBeNull();
});

// ── Fallback precedence ─────────────────────────────────────────

it('prefers the upload over the seeded avatar and over Google', function () {
    $teacher = Teacher::create([
        'full_name' => 'أ. سامر',
        'avatar_path' => 'images/teachers/team-1.jpg',
        'is_publicly_listed' => true,
    ]);

    $user = tap($teacher->user()->create([
        'email' => 'teacher@test.local',
        'password' => bcrypt('secret'),
        'email_verified_at' => now(),
        'google_avatar_url' => 'https://example.test/google.jpg',
    ]))->syncRoleToShape();

    // ٣) Google is last, and only when nothing else is set.
    expect($user->avatar_url)->toContain('images/teachers/team-1.jpg');

    $user->syncAvatar(avatarFile());

    // ١) The upload beats both — it is the account holder's own act.
    expect($user->fresh()->avatar_url)->toContain('storage/avatars/');

    $user->fresh()->clearAvatar();

    // Removing the upload falls back to the seeded asset, not to nothing.
    expect($user->fresh()->avatar_url)->toContain('images/teachers/team-1.jpg');
});

it('falls back to Google when the domain record has no picture', function () {
    $user = User::query()->find($this->admin->id);
    $user->update(['google_avatar_url' => 'https://example.test/google.jpg']);

    expect($user->fresh()->avatar_url)->toBe('https://example.test/google.jpg');
});

it('never unlinks a repository asset when replacing an avatar', function () {
    /*
     * 🔴 Seeded avatars point at REPOSITORY files under public/images.
     * Deleting one would remove it from the working tree of every
     * environment and it would never come back — hence the 'storage/'
     * prefix guard.
     */
    $this->admin->userable->update(['avatar_path' => 'images/teachers/team-1.jpg']);

    actingAs($this->admin)->post(route('admin.profile.avatar'), ['avatar' => avatarFile()]);

    expect(File::exists(public_path('images/teachers/team-1.jpg')))->toBeTrue();
});

// ── The screen ──────────────────────────────────────────────────

it('shows the editor, and the delete button only for an upload', function () {
    $html = actingAs($this->admin)->get(route('admin.profile.edit'))->assertOk()->getContent();

    expect($html)->toContain('avatarEditor')
        ->toContain('رفع صورة')
        // ⚠️ No delete button yet: a Google picture is not the account's to
        // remove, and a button that cannot work reads as broken.
        ->not->toContain('حذف الصورة');

    actingAs($this->admin)->post(route('admin.profile.avatar'), ['avatar' => avatarFile()]);

    $html = actingAs($this->admin)->get(route('admin.profile.edit'))->assertOk()->getContent();

    expect($html)->toContain('حذف الصورة')
        ->toContain('تغيير الصورة')
        ->toContain('معاينة الصورة');
});
