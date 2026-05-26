<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_initial_price_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->decimal('initial_price', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until')->nullable();
            $table->unsignedBigInteger('parent_version_id')->nullable();
            $table->string('change_reason')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'version']);
            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'valid_from']);
            $table->index(['product_id', 'valid_until']);
        });

        Schema::table('product_initial_price_versions', function (Blueprint $table) {
            $table->foreign('parent_version_id')
                ->references('id')
                ->on('product_initial_price_versions')
                ->nullOnDelete();
        });

        $now = now();

        DB::table('products')
            ->select(['id', 'initial_price', 'discount_percentage', 'created_at'])
            ->orderBy('id')
            ->chunkById(500, function ($products) use ($now) {
                $rows = [];

                foreach ($products as $product) {
                    $rows[] = [
                        'product_id' => $product->id,
                        'version' => 1,
                        'initial_price' => $product->initial_price ?? 0,
                        'discount_percentage' => $product->discount_percentage ?? 0,
                        'is_active' => true,
                        'valid_from' => $product->created_at ?? $now,
                        'valid_until' => null,
                        'parent_version_id' => null,
                        'change_reason' => 'Backfill V1',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($rows)) {
                    DB::table('product_initial_price_versions')->insert($rows);
                }
            });
    }

    public function down()
    {
        Schema::dropIfExists('product_initial_price_versions');
    }
};
