<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Operator-conduct scores, submitted once per trip alongside the existing
     * per-seat rating in seat_ratings. Range checks are enforced in the request
     * validator rather than as DB CHECK constraints, which SQLite (dev) and
     * Postgres (prod) express differently.
     */
    public function up(): void
    {
        Schema::create('bus_behavior_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bus_id')->constrained('buslists')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // No FK: orders rows are matched to a user by email, not by user_id.
            $table->unsignedBigInteger('order_id')->nullable();

            $table->date('trip_date');

            $table->unsignedTinyInteger('route_adherence_score');
            $table->unsignedTinyInteger('punctuality_score');
            $table->unsignedTinyInteger('cleanliness_score');
            $table->unsignedTinyInteger('driver_behavior_score');
            $table->unsignedTinyInteger('overall_score');

            $table->text('comment')->nullable();
            $table->boolean('is_verified_passenger')->default(false);

            $table->timestamps();

            // One conduct score per user per bus per trip date. Mirrors the key
            // seat_ratings is deduplicated on, so a resubmit updates in place.
            $table->unique(['user_id', 'bus_id', 'trip_date'], 'unique_user_bus_trip_score');

            $table->index(['bus_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_behavior_scores');
    }
};
