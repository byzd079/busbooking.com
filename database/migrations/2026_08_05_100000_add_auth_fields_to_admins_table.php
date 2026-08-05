<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('admins', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            if (!Schema::hasColumn('admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('admins', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['name', 'remember_token', 'last_login_at', 'last_login_ip']);
        });
    }
};
