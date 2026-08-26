<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| users — جدول الدخول فقط
|--------------------------------------------------------------------------
| لا يحمل اسماً ولا دوراً ولا فرعاً. كلها في جداول المجال
| (students · teachers · admins) المرتبطة بعلاقة 1:1 متعدّدة الأشكال.
|
| الهوية بالبريد الإلكتروني + دخول جوجل. لا هاتف ولا SMS.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // ── الهوية والاعتماد ──────────────────────────────────
            $table->string('email', 190)->unique();
            $table->timestamp('email_verified_at')->nullable();

            // قابل للفراغ عمداً: من يسجّل عبر جوجل لا يملك كلمة مرور.
            $table->string('password')->nullable();

            // ── دخول جوجل (OAuth) ─────────────────────────────────
            $table->string('google_id')->nullable()->unique();
            $table->string('google_avatar_url', 500)->nullable();

            // ── المؤشّر متعدّد الأشكال إلى سجلّ المجال ─────────────
            // إجباري: كل حساب يحلّ إلى سجلّ واحد بالضبط، بلا استثناء.
            $table->string('userable_type', 255);
            $table->unsignedBigInteger('userable_id');

            // ── حالة الحساب والتفضيلات ────────────────────────────
            $table->boolean('is_active')->default(true)->index();
            $table->string('locale', 5)->default('ar');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // يمنع حسابين من ادّعاء نفس سجلّ المجال (مشاركة حساب من الجذر).
            $table->unique(['userable_type', 'userable_id'], 'users_userable_unique');
        });

        // مفتاح ذاتي المرجع — يُضاف بعد الإنشاء لا داخله.
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
