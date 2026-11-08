<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| testimonials.student_id — link a review to the account that wrote it
|--------------------------------------------------------------------------
| ⚠️ RECORDED DEVIATION. The `testimonials` table was built deliberately
| WITHOUT a foreign key to `students`, on the reasoning that a testimonial is
| a SNAPSHOT: tying it to a live account means a student deleting theirs
| tears a section out of the home page. That was requested changed, and the
| request stands.
|
| The snapshot is kept rather than replaced, which is what makes the link
| safe:
|   - the column is NULLABLE, so a review with no account is still valid;
|   - nullOnDelete, NOT cascade — deleting a student must not delete what
|     they said, it just unlinks it;
|   - `name` and `title` remain as the fallback the card falls back to, so a
|     removed student leaves the quote standing under the stored name instead
|     of an empty card.
|
| Net effect: student details are live while the account exists, and frozen
| the moment it does not.
|
| 🔴 The existing `rate` column is the rating. A second `rating` column would
| be two numbers for one score with nothing keeping them equal.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->foreignId('student_id')
                ->nullable()
                ->after('id')
                ->constrained('students')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
        });
    }
};
