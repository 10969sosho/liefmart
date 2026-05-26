<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns first
        Schema::table('finance_offlines', function (Blueprint $table) {
            // Add outstanding column for tracking additional deductions
            $table->decimal('outstanding', 15, 2)->default(0)->after('nominal');
            
            // Add notes column for refund descriptions
            $table->text('notes')->nullable()->after('outstanding');
        });
        
        // Use raw SQL to modify enum column to avoid Doctrine DBAL enum issues
        DB::statement("ALTER TABLE finance_offlines MODIFY COLUMN status ENUM('unpaid', 'paid', 'refunded', 'partial_refund') DEFAULT 'unpaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status back to original values using raw SQL
        DB::statement("ALTER TABLE finance_offlines MODIFY COLUMN status ENUM('unpaid', 'paid') DEFAULT 'unpaid'");
        
        // Remove added columns
        Schema::table('finance_offlines', function (Blueprint $table) {
            $table->dropColumn(['outstanding', 'notes']);
        });
    }
};