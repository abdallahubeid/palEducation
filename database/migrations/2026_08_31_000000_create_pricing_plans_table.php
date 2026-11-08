<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| pricing_plans — the tier cards in the pricing section
|--------------------------------------------------------------------------
| 🔴 MARKETING copy, not the billing model. These rows describe what a
| visitor is offered; what a student actually bought belongs to
| `subscriptions` in phase 8, which is unbuilt. Keeping them apart matters:
| editing a price here must never rewrite what someone already paid, and a
| plan withdrawn from the page must not disappear from past receipts.
|
| ⚠️ price is an INTEGER of whole shekels, not a decimal. The three tiers are
| whole numbers by design, and float money is the classic rounding bug. If
| fractional pricing ever lands, this becomes minor units (agorot) — never a
| float.
|
| `features` is a JSON array of bullet strings. It is a list with no
| identity, order or behaviour of its own — a child table would add a join
| and a second screen to manage three sentences.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->string('badge', 200)->nullable();

            $table->unsignedInteger('price')->default(0);
            $table->string('period', 60)->nullable();

            $table->json('features')->nullable();

            // The ribbon label. Nullable because at most one plan carries it;
            // a boolean would force a second column for the wording anyway.
            $table->string('highlight_badge', 80)->nullable();

            $table->string('btn_text', 80)->nullable();
            $table->string('btn_link', 500)->nullable();

            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'display_order'], 'pricing_plans_active_order_index');

            // 🔴 softDeletes() creates NO index, despite the name. Every
            // Eloquent read on a soft-deleting model adds `deleted_at IS
            // NULL`, so without this every query is a full scan — and the
            // trash screen reads the same column inverted.
            $table->index('deleted_at', 'pricing_plans_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};
