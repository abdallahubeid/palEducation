<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();

            /*
             * ⚠️ A `users` reference and nullOnDelete, matching the contact
             * inbox: this records the ACTOR, and removing a staff account
             * must not delete the record of a newsletter that already
             * reached other people's inboxes.
             */
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('subject', 190);

            /*
             * 🔴 The body is COPIED here, not referenced. It is the archive of
             * what was actually sent — the record has to keep saying that
             * even if the same admin sends a different newsletter tomorrow.
             */
            $table->text('body');

            $table->unsignedInteger('recipients_count')->default(0);
            $table->string('status', 20)->default('processing');

            // Null until the queue finishes: an unfinished campaign has no
            // honest "sent at" to show.
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The history screen's only ordering, and its status filter.
            $table->index(['status', 'created_at']);

            // ⚠️ softDeletes() creates NO index, and every read adds
            // `deleted_at is null` implicitly.
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaigns');
    }
};
