<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| testimonials.avatar_source — which portrait the card should use
|--------------------------------------------------------------------------
| Until now the portrait was picked by precedence: an attached Media row won,
| otherwise the student's own avatar. That works but it is IMPLICIT — an
| admin uploading a custom image had no way to go back to the account photo
| except by deleting the upload, and nothing on screen said which one was
| being shown.
|
| This column makes the choice explicit and reversible: swap the toggle back
| to `student` and the custom file stays on disk, unused, ready to return to.
|
| 🔴 A string, not a database enum. MySQL enums need a schema migration to
| add a value, and this list will plausibly grow (an uploaded-by-the-student
| photo, a generated initial). `Testimonial::AVATAR_SOURCES` is the authority
| and the controller validates against it.
|
| Defaults to `student`: every existing row was rendering the account photo,
| so that is the value that preserves current behaviour rather than silently
| repointing three seeded cards at nothing.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('avatar_source', 20)
                ->default('student')
                ->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('avatar_source');
        });
    }
};
