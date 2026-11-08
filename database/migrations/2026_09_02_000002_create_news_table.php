<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();

            /*
             * ⚠️ A `users` reference and nullOnDelete, matching every other
             * actor column here: removing a staff account must not delete
             * the articles they wrote. The public page falls back to the
             * platform's own name when the author is gone.
             */
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title', 190);
            $table->string('slug', 200)->unique();
            $table->text('summary')->nullable();
            $table->longText('content');

            /*
             * 🔴 A path on the PUBLIC disk, not a `media` row. The media table
             * is reserved for sliders and testimonials, and news artwork is a
             * single cover image with no gallery, no ordering and no alt-text
             * record — a morph would buy nothing and add a join to every list.
             */
            $table->string('image_path')->nullable();

            $table->boolean('is_published')->default(true);

            /*
             * ⚠️ Nullable in the schema, always set by the model. A NOT NULL
             * default of CURRENT_TIMESTAMP cannot be expressed portably here,
             * and an article whose date is silently "now" on every edit is
             * worse than one the model dates explicitly on create.
             */
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The public query is exactly this pair, in this order.
            $table->index(['is_published', 'published_at']);

            // softDeletes() creates NO index, and every read adds
            // `deleted_at is null` implicitly.
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
