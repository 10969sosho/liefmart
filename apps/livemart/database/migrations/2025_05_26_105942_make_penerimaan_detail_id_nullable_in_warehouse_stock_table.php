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
        // Using raw SQL instead of Schema::table with change() to avoid Doctrine compatibility issues
        DB::statement('ALTER TABLE `warehouse_stock` MODIFY `penerimaan_detail_id` BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Using raw SQL to revert the change
        DB::statement('ALTER TABLE `warehouse_stock` MODIFY `penerimaan_detail_id` BIGINT UNSIGNED NOT NULL');
    }
};
