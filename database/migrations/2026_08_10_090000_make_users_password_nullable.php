<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make users.password NULLABLE.
     *
     * A NULL password marks an UNCLAIMED, auto-created account (a guest who
     * checked out without registering). Such an account can never satisfy
     * Auth::attempt() — Hash::check() rejects an empty/NULL hash — so it stays
     * locked until the owner sets a password via the claim / forgot-password
     * flow.
     *
     * Laravel 11 changes columns natively (no doctrine/dbal needed), and this
     * works on both SQLite (local) and Postgres (prod). If a driver ever cannot
     * run change(), we fall back to driver-aware raw SQL below.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'password')) {
            return;
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
            });
        } catch (\Throwable $e) {
            $this->rawSetPasswordNullable(true);
        }
    }

    /**
     * Reverse: restore NOT NULL. Any unclaimed (NULL-password) rows are given a
     * random locked hash first so the constraint can be re-applied without data
     * loss — the account remains unusable until a real password is set.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('users', 'password')) {
            return;
        }

        DB::table('users')->whereNull('password')->update([
            'password' => bcrypt(bin2hex(random_bytes(16))),
        ]);

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            $this->rawSetPasswordNullable(false);
        }
    }

    /**
     * Driver-aware raw fallback. SQLite cannot ALTER a column's nullability in
     * place, so on SQLite we rely on change() above and only reach here for
     * Postgres, where DROP/SET NOT NULL is a cheap metadata change.
     */
    private function rawSetPasswordNullable(bool $nullable): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $clause = $nullable ? 'DROP NOT NULL' : 'SET NOT NULL';
            DB::statement("ALTER TABLE users ALTER COLUMN password {$clause}");
            return;
        }

        // Re-raise for any driver where neither change() nor a known raw path
        // worked, so the failure is visible instead of silently skipped.
        throw new \RuntimeException(
            "Cannot toggle users.password nullability on driver [{$driver}]."
        );
    }
};
