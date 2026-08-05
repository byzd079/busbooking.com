<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One row per (comment, reporter) — same deduplication contract as
     * post_flags. See that migration for why the unique key is load-bearing.
     */
    public function up(): void
    {
        Schema::create('comment_flags', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comment_id')->constrained('post_comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['comment_id', 'user_id'], 'unique_comment_user_flag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_flags');
    }
};
