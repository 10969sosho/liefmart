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
        Schema::table('mapping_barangs', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('quantity');
            $table->boolean('is_active')->default(true)->after('version');
            $table->timestamp('valid_from')->nullable()->after('is_active');
            $table->timestamp('valid_until')->nullable()->after('valid_from');
            $table->unsignedBigInteger('parent_mapping_id')->nullable()->after('valid_until');
            $table->text('change_reason')->nullable()->after('parent_mapping_id');
            
            // Add indexes
            $table->index(['platform_product_id', 'version']);
            $table->index(['platform_product_id', 'is_active']);
            $table->index('parent_mapping_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mapping_barangs', function (Blueprint $table) {
            $table->dropIndex(['platform_product_id', 'version']);
            $table->dropIndex(['platform_product_id', 'is_active']);
            $table->dropIndex('parent_mapping_id');
            
            $table->dropColumn([
                'version',
                'is_active',
                'valid_from',
                'valid_until',
                'parent_mapping_id',
                'change_reason'
            ]);
        });
    }
};