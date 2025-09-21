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
        Schema::table('arus_kas_tokopedia_imports', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->nullable()->after('nominal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('arus_kas_tokopedia_imports', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
