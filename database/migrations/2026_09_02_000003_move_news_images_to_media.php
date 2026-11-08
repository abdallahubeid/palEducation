<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| news.image_path → media
|--------------------------------------------------------------------------
| 🔴 The rows are COPIED BEFORE the column is dropped. Dropping first and
| re-uploading later would silently lose every cover already published — a
| schema change must never be a content deletion.
|
| ⚠️ Paths move across VERBATIM. The old column held a root-relative path
| under public/ and Media::url() resolves with the same asset() call, so no
| file is moved and no URL changes.
|
| This is within the media table's stated scope: it is for PUBLIC MARKETING
| assets (sliders · testimonials), and a news cover is exactly that. The
| standing prohibition covers PAID content — no Lecture and no LibraryFile
| may ever declare a morph here.
*/
return new class extends Migration
{
    /** Kept in one place: the migration writes it and the rollback reads it. */
    private const MEDIABLE_TYPE = 'App\Models\News';

    public function up(): void
    {
        if (! Schema::hasColumn('news', 'image_path')) {
            return;
        }

        $now = now();

        DB::table('news')
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('id')
            // Chunked: an archive can be long, and building one insert of
            // every row in memory is the thing that fails on the day it
            // matters.
            ->chunkById(200, function ($rows) use ($now): void {
                DB::table('media')->insert(
                    collect($rows)->map(fn ($row): array => [
                        'path' => $row->image_path,
                        'type' => 'image',
                        // ⚠️ The old column carried no alt text — there was
                        // nowhere to type one. Left null rather than invented
                        // from the headline, which would be a caption, not a
                        // description of the picture.
                        'alt_text' => null,
                        'display_order' => 1,
                        'mediable_id' => $row->id,
                        'mediable_type' => self::MEDIABLE_TYPE,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('news', 'image_path')) {
            Schema::table('news', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('content');
            });
        }

        // The reverse copy, so a rollback is not a content deletion either.
        DB::table('media')
            ->where('mediable_type', self::MEDIABLE_TYPE)
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $media) {
                    DB::table('news')
                        ->where('id', $media->mediable_id)
                        ->update(['image_path' => $media->path]);
                }
            });

        DB::table('media')->where('mediable_type', self::MEDIABLE_TYPE)->delete();
    }
};
