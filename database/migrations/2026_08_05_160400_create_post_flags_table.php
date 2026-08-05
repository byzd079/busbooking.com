<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One row per (post, reporter). The unique key is the whole point: without
     * it a single user can POST /bus-post/{id}/flag repeatedly and drive
     * flag_count past the auto-hide threshold on their own, which would let any
     * one account hide any post. bus_posts.flag_count is the denormalised tally
     * and must only be incremented when an insert here actually succeeds.
     */
    public function up(): void
    {
        Schema::create('post_flags', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')->constrained('bus_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['post_id', 'user_id'], 'unique_post_user_flag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_flags');
    }
};
