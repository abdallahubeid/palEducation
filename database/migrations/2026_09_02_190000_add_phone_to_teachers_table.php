<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| teachers.phone
|--------------------------------------------------------------------------
| 🔴 CONTACT data, never an identity. The platform's identity is email +
| Google OAuth — no phone, no SMS — exactly as students.phone already is.
| Nothing authenticates against this column and nothing may start to.
|
| ⚠️ Added because the teacher profile form asks for it, and a form field
| with no column behind it saves nothing while looking like it worked.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
