<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Community photo posts attached to a bus.
     *
     * Image bytes live in the database rather than on disk: Render's free tier
     * has an ephemeral filesystem, so anything written to storage/ disappears on
     * redeploy and on idle spin-down. binary() maps to BYTEA on Postgres and BLOB
     * on SQLite, so the same migration runs in dev and in production.
     */
    public function up(): void
    {
        Schema::create('bus_posts', function (Blueprint $table) {
            $table->id();

            // buslists is the master bus record; seat_ratings.bus_id points here too.
            $table->foreignId('bus_id')->constrained('buslists')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // orders has no user_id column (ownership is matched on email), so this
            // stays a plain nullable reference with no FK constraint.
            $table->unsignedBigInteger('order_id')->nullable();

            $table->string('post_type', 20)->default('other');
            $table->binary('image_data');
            $table->string('mime_type', 30)->default('image/webp');
            $table->unsignedInteger('image_size')->default(0);
            $table->unsignedSmallInteger('image_width')->default(0);
            $table->unsignedSmallInteger('image_height')->default(0);

            $table->text('caption')->nullable();

            // Set at insert time when the poster had a paid order for this bus.
            $table->boolean('is_verified_passenger')->default(false);

            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('flag_count')->default(0);
            $table->boolean('is_hidden')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['bus_id', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_posts');
    }
};
