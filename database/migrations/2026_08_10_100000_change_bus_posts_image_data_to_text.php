<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Store bus-gallery photos as base64 TEXT instead of raw BYTEA.
     *
     * The original column was binary() -> BYTEA on Postgres. Storing raw image
     * bytes there is a trap: PDO binds the value as a text parameter, so the
     * non-UTF-8 bytes of a WebP throw SQLSTATE[22021] "invalid byte sequence for
     * encoding UTF8" on INSERT, and a BYTEA that does get in reads back as a
     * stream rather than a string. Neither shows up on SQLite (BLOB affinity is
     * permissive), which is why local dev and the test suite never caught it and
     * production returned a 500 on every upload.
     *
     * base64 text is plain ASCII: it inserts as an ordinary string and reads back
     * as one on both drivers. The model base64-(de)codes at its boundary so the
     * rest of the app still deals in raw bytes.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            // SQLite (dev + tests): BLOB affinity already stores base64 text as-is,
            // so no type change is needed or possible without doctrine/dbal.
            return;
        }

        // Guarded so a re-run is a no-op: only convert while the column is still
        // BYTEA. encode(...,'base64') turns any existing bytes into the new format
        // (the table is empty in production since uploads never succeeded, so this
        // runs on zero rows there anyway).
        $column = DB::selectOne(
            "SELECT data_type FROM information_schema.columns
             WHERE table_name = 'bus_posts' AND column_name = 'image_data'"
        );

        if ($column && $column->data_type === 'bytea') {
            DB::statement(
                "ALTER TABLE bus_posts
                 ALTER COLUMN image_data TYPE text USING encode(image_data, 'base64')"
            );
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $column = DB::selectOne(
            "SELECT data_type FROM information_schema.columns
             WHERE table_name = 'bus_posts' AND column_name = 'image_data'"
        );

        if ($column && $column->data_type === 'text') {
            DB::statement(
                "ALTER TABLE bus_posts
                 ALTER COLUMN image_data TYPE bytea USING decode(image_data, 'base64')"
            );
        }
    }
};
