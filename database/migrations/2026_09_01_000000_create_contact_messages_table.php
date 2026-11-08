<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| contact_messages — what visitors send through /contact
|--------------------------------------------------------------------------
| 🔴 The sender is stored as PLAIN COLUMNS, not a link to `users`. Most
| people who write in have no account, and the ones who do may delete it —
| a message must outlive both. This is correspondence, not a user record.
|
| ⚠️ `admin_id` points at `users`, not `admins`: it records WHO replied, and
| that is an actor reference. nullOnDelete, because removing a staff account
| must not delete the reply they sent — the customer already received it.
|
| The read flag and the reply are separate concerns on purpose: a message can
| be read and left unanswered, which is exactly the state an inbox needs to
| show. `replied_at` doubles as the flag and the timestamp.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            $table->string('name', 160);
            $table->string('email', 190);
            $table->string('phone', 40)->nullable();
            $table->string('subject_type', 30);
            $table->text('message');

            $table->boolean('is_read')->default(false);

            $table->timestamp('replied_at')->nullable();
            $table->text('reply_message')->nullable();

            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // The inbox is filtered by these two on every visit.
            $table->index(['is_read', 'created_at'], 'contact_messages_read_created_index');
            $table->index('replied_at', 'contact_messages_replied_at_index');

            // 🔴 softDeletes() creates NO index despite the name, and every
            // Eloquent read on a soft-deleting model filters this column.
            $table->index('deleted_at', 'contact_messages_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
