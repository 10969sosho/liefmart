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
    public function up(): void
    {
        Schema::table('finance_offlines', function (Blueprint $table) {
            $table->integer('print_count')->default(0)->after('status');
            $table->boolean('reprint_requested')->default(false)->after('print_count');
            $table->boolean('reprint_approved')->default(false)->after('reprint_requested');
            $table->unsignedBigInteger('reprint_approved_by')->nullable()->after('reprint_approved');
            $table->timestamp('last_printed_at')->nullable()->after('reprint_approved_by');
            $table->foreign('reprint_approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('finance_offlines', function (Blueprint $table) {
            $table->dropForeign(['reprint_approved_by']);
            $table->dropColumn([
                'print_count',
                'reprint_requested',
                'reprint_approved',
                'reprint_approved_by',
                'last_printed_at'
            ]);
        });
    }
};
