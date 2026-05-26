<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, drop the foreign key constraint
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });
        
        // Remove the old role column since we're using role_id now
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        
        // Make sure all users have role_id (assign to superadmin role)
        DB::statement('UPDATE users SET role_id = 1 WHERE role_id IS NULL');
        
        // Now we can make role_id NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
        });
        
        // Re-add the foreign key constraint
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
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
            // Restore the old role column
            $table->string('role')->default('admin')->after('password');
            
            // Make role_id nullable again
            $table->foreignId('role_id')->nullable()->change();
        });
    }
};