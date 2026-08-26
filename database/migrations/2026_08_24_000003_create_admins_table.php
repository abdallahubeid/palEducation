<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| admins — سجلّ المجال للأدمن
|--------------------------------------------------------------------------
| بسيط عمداً. سبب وجوده: الاسم انتقل إلى جداول المجال، فأدمن بلا جدول
| = أدمن بلا اسم — وخمس شاشات تعرض «من نفّذ هذا الإجراء».
|
| وفائدته البنيوية: وجوده يُبقي userable_type/userable_id في users
| إجباريين، فكل حساب يحلّ إلى سجلّ واحد بلا استثناء ولا فرع فارغ في الكود.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();

            $table->string('full_name', 160);
            $table->string('avatar_path', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
