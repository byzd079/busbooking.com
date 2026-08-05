<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Replies on a bus post. One level of nesting is supported via
     * parent_comment_id so a reply can address another reply.
     */
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')->constrained('bus_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->unsignedBigInteger('parent_comment_id')->nullable();
            $table->foreign('parent_comment_id')
                ->references('id')->on('post_comments')
                ->onDelete('cascade');

            $table->text('comment_text');
            $table->boolean('is_verified_passenger')->default(false);
            $table->unsignedInteger('flag_count')->default(0);
            $table->boolean('is_hidden')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};
