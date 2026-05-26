<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null')->after('role');
            $table->boolean('is_active')->default(true)->after('role_id');
            
            // Make email nullable for username-based users
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role_id', 'is_active']);
            $table->string('email')->nullable(false)->change();
        });
    }
};