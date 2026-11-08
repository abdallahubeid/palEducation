<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| teachers.branch_id — the teacher's branch on their public profile
|--------------------------------------------------------------------------
| ⚠️ RECORDED DEVIATION. Until now a teacher's branch was derived through
| `subjects.branch_id`, and I argued against storing it because a second
| source drifts the moment a subject assignment changes. That reasoning has
| not gone away — this column was requested explicitly so the admin form can
| offer a branch dropdown, and the request stands.
|
| The drift is contained rather than ignored:
|   - the column is NULLABLE, so "unset" is a real state, not a guess;
|   - Teacher::primaryBranch() prefers it and FALLS BACK to the subject
|     derivation, so a teacher with no explicit branch still shows the right
|     one;
|   - nothing writes it except an admin choosing it.
|
| What it is: the branch shown on the public roster card.
| What it is NOT: the content path. `subjects.branch_id` remains the only
| route from a branch to its lectures and library files, and invariant 5
| (personalised exclusion) still runs through subjects alone.
|
| nullOnDelete, not cascade: removing a branch must not delete the teachers
| who taught in it.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('full_name')
                ->constrained('branches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
