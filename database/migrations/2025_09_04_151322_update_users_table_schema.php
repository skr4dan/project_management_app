<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rename name to first_name
            $table->renameColumn('name', 'first_name');

            // Add new columns
            $table->string('last_name')->nullable()->after('first_name');
            $table->foreignId('role_id')->nullable()->after('password')->constrained();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')->after('role_id');
            $table->string('avatar')->nullable()->after('status');
            $table->string('phone')->nullable()->after('avatar');

            // Remove email_verified_at
            $table->dropColumn('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverse the changes
            $table->renameColumn('first_name', 'name');
            $table->dropForeign(['role_id']);
            $table->dropColumn(['last_name', 'role_id', 'status', 'avatar', 'phone']);
            $table->timestamp('email_verified_at')->nullable();
        });
    }
};
