<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_initial_price_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->unsignedBigInteger('parent_version_id')->nullable();
            $table->string('change_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->decimal('initial_price', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'valid_from']);
        });

        Schema::table('product_initial_price_versions', function (Blueprint $table) {
            $table->foreign('parent_version_id')->references('id')->on('product_initial_price_versions')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        if (Schema::hasTable('products')) {
            DB::table('products')
                ->select('id', 'initial_price', 'discount_percentage')
                ->orderBy('id')
                ->chunkById(500, function ($products) {
                    $now = now();
                    $rows = [];

                    foreach ($products as $product) {
                        $rows[] = [
                            'product_id' => $product->id,
                            'version' => 1,
                            'is_active' => true,
                            'valid_from' => '1970-01-01 00:00:00',
                            'valid_until' => null,
                            'parent_version_id' => null,
                            'change_reason' => 'bootstrap',
                            'created_by' => null,
                            'initial_price' => $product->initial_price ?? 0,
                            'discount_percentage' => $product->discount_percentage ?? 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows) {
                        DB::table('product_initial_price_versions')->insert($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_initial_price_versions');
    }
};

