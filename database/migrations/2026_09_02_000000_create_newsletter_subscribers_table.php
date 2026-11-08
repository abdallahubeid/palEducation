<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();

            /*
             * 🔴 Unique. The footer form is public and people submit twice —
             * without this the same address receives every campaign twice and
             * unsubscribing one row leaves the other still mailing them.
             *
             * ⚠️ The uniqueness is NOT filtered by deleted_at, so a soft
             * deleted address still blocks re-subscription. That is handled
             * in the model (withTrashed + restore), not by dropping the
             * index: two rows for one address is the worse failure.
             */
            $table->string('email', 190)->unique();

            $table->string('status', 20)->default('active');

            // When they joined, kept apart from created_at: an address
            // imported from elsewhere subscribed long before its row existed.
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The index list is the admin screen's two queries: filter by
            // status, order by date.
            $table->index(['status', 'subscribed_at']);

            // ⚠️ softDeletes() creates NO index — and every Eloquent read
            // adds `deleted_at is null` implicitly.
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
