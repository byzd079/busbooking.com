<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * "Helpful" votes on a post. The unique key is what makes the toggle
     * idempotent; bus_posts.helpful_count is the denormalised tally.
     */
    public function up(): void
    {
        Schema::create('post_helpful_marks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')->constrained('bus_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['post_id', 'user_id'], 'unique_post_user_helpful');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_helpful_marks');
    }
};
