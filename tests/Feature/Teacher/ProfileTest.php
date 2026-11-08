<?php

use App\Enums\MediaType;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Media;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| The teacher's own profile.
|
| 🔴 The guard here is structural, not a permission: no route carries an
| account id and every action works on $request->user() alone, so there is
| nothing to tamper with to reach a colleague's profile.
|
| ⚠️ The name lives on teachers.full_name, not on users — an update writes to
| two tables, and forgetting the domain row is how a rename silently does
| nothing at all.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->teacherRecord = Teacher::create([
        'full_name' => 'أ. سامر خليل', 'is_publicly_listed' => true,
    ]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'samer@test.local', 'password' => Hash::make('secret-pass'),
        'email_verified_at' => now(), 'locale' => 'ar',
    ]))->syncRoleToShape();

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function teacherAvatarFile(string $name = 'face.jpg'): UploadedFile
{
    // ⚠️ ->create(), not ->image(): the GD extension is disabled here and
    // fake image generation throws.
    return UploadedFile::fake()->create($name, 200, 'image/jpeg');
}

// ── The page ────────────────────────────────────────────────────

it('opens for the signed-in teacher', function () {
    actingAs($this->teacher)
        ->get(route('teacher.profile.edit'))
        ->assertOk()
        ->assertSee('الملف الشخصي')
        ->assertSee('أ. سامر خليل')
        ->assertSee('samer@test.local');
});

it('shows the avatar editor and the topbar dropdown', function () {
    $html = actingAs($this->teacher)->get(route('teacher.profile.edit'))->assertOk()->getContent();

    expect($html)->toContain('avatarEditor')
        // The cropper stage is forced LTR — coordinate maths runs from the
        // left in every locale.
        ->toContain('dir="ltr"')
        ->toContain(route('teacher.profile.avatar'))
        // The topbar dropdown links to the profile and offers logout.
        ->toContain(route('auth.logout'))
        ->toContain('تسجيل الخروج');
});

// ── Personal details ────────────────────────────────────────────

it('updates details across both tables', function () {
    actingAs($this->teacher)
        ->put(route('teacher.profile.update'), [
            'full_name' => 'أ. سامر عبد الله',
            'email' => 'samer.new@test.local',
            'phone' => '+970 599 111 222',
            'bio' => 'معلّم رياضيات بخبرة عشر سنوات.',
            'locale' => 'ar',
        ])
        ->assertRedirect(route('teacher.profile.edit'));

    $this->teacher->refresh();

    /*
     * 🔴 Both rows. The name, phone and bio live on teachers; the email and
     * locale live on users. An update that wrote only one would look like it
     * saved and lose half the form.
     */
    expect($this->teacher->userable->full_name)->toBe('أ. سامر عبد الله')
        ->and($this->teacher->userable->phone)->toBe('+970 599 111 222')
        ->and($this->teacher->userable->bio)->toBe('معلّم رياضيات بخبرة عشر سنوات.')
        ->and($this->teacher->email)->toBe('samer.new@test.local');
});

it('stores the phone rather than silently dropping it', function () {
    /*
     * 🔴 teachers.phone was added with this feature. A column that exists, is
     * validated and is passed to update() but is missing from $fillable is
     * dropped by Eloquent in SILENCE — the trap that already cost this project
     * a fortnight of lost student phone numbers.
     */
    actingAs($this->teacher)->put(route('teacher.profile.update'), [
        'full_name' => 'أ. سامر خليل', 'email' => 'samer@test.local',
        'phone' => '0599123456', 'locale' => 'ar',
    ])->assertRedirect();

    expect($this->teacherRecord->fresh()->phone)->toBe('0599123456');
});

it('refuses an email another account already holds', function () {
    actingAs($this->teacher)
        ->put(route('teacher.profile.update'), [
            'full_name' => 'أ. سامر خليل', 'email' => 'student@test.local', 'locale' => 'ar',
        ])
        ->assertSessionHasErrors('email');

    expect($this->teacher->fresh()->email)->toBe('samer@test.local');
});

it('allows keeping the current email', function () {
    // ⚠️ The uniqueness rule ignores this account: without that, saving the
    // form unchanged fails as a duplicate of itself.
    actingAs($this->teacher)
        ->put(route('teacher.profile.update'), [
            'full_name' => 'اسم جديد', 'email' => 'samer@test.local', 'locale' => 'ar',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

// ── Password ────────────────────────────────────────────────────

it('changes the password with the current one', function () {
    actingAs($this->teacher)
        ->put(route('teacher.profile.password'), [
            'current_password' => 'secret-pass',
            'password' => 'Brand!New!Pass9',
            'password_confirmation' => 'Brand!New!Pass9',
        ])
        ->assertRedirect(route('teacher.profile.edit'));

    expect(Hash::check('Brand!New!Pass9', $this->teacher->fresh()->password))->toBeTrue();
});

it('refuses a password change without the current password', function () {
    actingAs($this->teacher)
        ->put(route('teacher.profile.password'), [
            'current_password' => 'wrong-one',
            'password' => 'Brand!New!Pass9',
            'password_confirmation' => 'Brand!New!Pass9',
        ])
        ->assertSessionHasErrors('current_password');

    /*
     * 🔴 This check is what stops a borrowed, unlocked session from becoming
     * a permanent account takeover.
     */
    expect(Hash::check('secret-pass', $this->teacher->fresh()->password))->toBeTrue();
});

// ── Avatar ──────────────────────────────────────────────────────

it('attaches an uploaded avatar as a media row', function () {
    actingAs($this->teacher)
        ->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile()])
        ->assertRedirect(route('teacher.profile.edit'));

    $media = $this->teacher->fresh()->image;

    expect($media)->not->toBeNull()
        // 🔴 Attached to the ACCOUNT, not the teachers record: the login is
        // what the topbar and the public card both render.
        ->and($media->mediable_type)->toBe(User::class)
        ->and($media->mediable_id)->toBe($this->teacher->id)
        ->and($media->type)->toBe(MediaType::Image)
        ->and($media->path)->toStartWith('storage/avatars/');

    Storage::disk('public')->assertExists(str_replace('storage/', '', $media->path));
});

it('replaces the avatar in place and deletes the old file', function () {
    actingAs($this->teacher)->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile('one.jpg')]);

    $firstPath = $this->teacher->fresh()->image->path;

    actingAs($this->teacher)->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile('two.jpg')]);

    $user = $this->teacher->fresh();

    // ⚠️ ONE row still — a plain create per upload would stack rows until
    // "first by display order" was whichever the query happened to return.
    expect($user->media()->count())->toBe(1)
        ->and($user->image->path)->not->toBe($firstPath);

    Storage::disk('public')->assertMissing(str_replace('storage/', '', $firstPath));
});

it('rejects a file that is not an accepted image', function () {
    actingAs($this->teacher)
        ->post(route('teacher.profile.avatar'), [
            'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    // 🔴 The server decides, not the cropper: a request can arrive without
    // ever passing through it.
    expect($this->teacher->fresh()->hasUploadedAvatar())->toBeFalse();
});

it('deletes the avatar and falls back', function () {
    actingAs($this->teacher)->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile()]);

    $path = $this->teacher->fresh()->image->path;

    actingAs($this->teacher)
        ->delete(route('teacher.profile.avatar.destroy'))
        ->assertRedirect(route('teacher.profile.edit'));

    $user = $this->teacher->fresh();

    /*
     * 🔴 forceDelete, not delete. A soft-deleted media row would keep
     * occupying the relation and block the fallback — the account would show
     * no picture rather than reverting to its default.
     */
    expect(Media::withTrashed()->count())->toBe(0)
        ->and($user->hasUploadedAvatar())->toBeFalse()
        ->and($user->avatar_url)->toBeNull();

    Storage::disk('public')->assertMissing(str_replace('storage/', '', $path));
});

it('resolves the avatar in the documented order', function () {
    $this->teacherRecord->update(['avatar_path' => 'images/teachers/team-1.jpg']);
    $this->teacher->update(['google_avatar_url' => 'https://example.test/google.jpg']);

    // ٢) The seeded asset beats Google.
    expect($this->teacher->fresh()->avatar_url)->toContain('images/teachers/team-1.jpg');

    actingAs($this->teacher)->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile()]);

    // ١) The upload beats both — it is the account holder's own act.
    expect($this->teacher->fresh()->avatar_url)->toContain('storage/avatars/');

    actingAs($this->teacher)->delete(route('teacher.profile.avatar.destroy'));

    // Removing it falls back to the seeded asset, not to nothing.
    expect($this->teacher->fresh()->avatar_url)->toContain('images/teachers/team-1.jpg');
});

it('never unlinks a repository asset when replacing an avatar', function () {
    // 🔴 Seeded portraits are repository files; deleting one removes it from
    // every environment's working tree and it never comes back.
    $this->teacherRecord->update(['avatar_path' => 'images/teachers/team-1.jpg']);

    actingAs($this->teacher)->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile()]);

    expect(File::exists(public_path('images/teachers/team-1.jpg')))->toBeTrue();
});

// ── Authorisation ───────────────────────────────────────────────

it('keeps every teacher profile route away from guests', function () {
    get(route('teacher.profile.edit'))->assertRedirect(route('auth.login'));

    $this->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile()])
        ->assertRedirect(route('auth.login'));

    expect(Media::count())->toBe(0);
});

it('keeps students and admins out of the teacher profile', function () {
    /*
     * ⚠️ An admin is refused too. This is not an administrative screen — it
     * is one specific person's own profile, and role:teacher says exactly
     * that. Admins manage accounts through the users screen instead.
     */
    foreach ([$this->student, $this->admin] as $outsider) {
        actingAs($outsider)->get(route('teacher.profile.edit'))->assertForbidden();

        actingAs($outsider)
            ->put(route('teacher.profile.update'), [
                'full_name' => 'اسم', 'email' => 'x@test.local', 'locale' => 'ar',
            ])
            ->assertForbidden();

        actingAs($outsider)
            ->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile()])
            ->assertForbidden();
    }

    expect($this->teacher->fresh()->userable->full_name)->toBe('أ. سامر خليل')
        ->and(Media::count())->toBe(0);
});

it('can only change the signed-in teacher own profile', function () {
    $otherRecord = Teacher::create(['full_name' => 'أ. رنا', 'is_publicly_listed' => true]);

    $other = tap($otherRecord->user()->create([
        'email' => 'rana@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($this->teacher)->post(route('teacher.profile.avatar'), ['avatar' => teacherAvatarFile()]);

    // 🔴 No id in any route — there is nothing to tamper with.
    expect($this->teacher->fresh()->hasUploadedAvatar())->toBeTrue()
        ->and($other->fresh()->hasUploadedAvatar())->toBeFalse();
});
