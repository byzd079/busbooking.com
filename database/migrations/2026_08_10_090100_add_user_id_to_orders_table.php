<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add orders.user_id so every order links to the account that made it
     * (guests are auto-provisioned an account at checkout). Additive and
     * portable: the column is nullable + indexed on every driver, and the
     * foreign key is only added where the driver can safely alter a table to
     * add one (Postgres). SQLite cannot add an FK to an existing table, so we
     * keep just the index there — the app enforces the relationship anyway.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            });
        }

        // Backfill existing orders by matching orders.email to users.email.
        // A correlated subquery runs on both SQLite and Postgres. emails are
        // unique in users, so at most one match; LIMIT 1 is belt-and-braces.
        DB::statement(
            'UPDATE orders SET user_id = ('
            . 'SELECT u.id FROM users u WHERE u.email = orders.email LIMIT 1'
            . ') WHERE user_id IS NULL AND email IS NOT NULL '
            . 'AND EXISTS (SELECT 1 FROM users u WHERE u.email = orders.email)'
        );

        // FK only where the driver supports adding it to an existing table.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Fully reversible: drop the FK (where it exists), the index, then the
     * column — each guarded so a partial state still rolls back cleanly.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('orders', 'user_id')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
