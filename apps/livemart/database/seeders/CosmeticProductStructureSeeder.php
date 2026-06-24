<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\MainCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CosmeticProductStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Get SKINCARE main category ID
        $skincareCategoryId = MainCategory::where('name', 'SKINCARE')->first()?->id ?? 2;

        // Get or create brands for SKINCARE
        $brands = $this->getOrCreateBrands($skincareCategoryId);

        // Create sub-brands for each brand
        foreach ($brands as $brand) {
            $this->createSubBrandsStructure($brand);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Cosmetic product structure seeded successfully!');
    }

    /**
     * Get existing or create new brands for SKINCARE
     */
    private function getOrCreateBrands(int $skincareCategoryId): array
    {
        $brandNames = ['Shopee SKINCARE', 'TikTok SKINCARE', 'Offline SKINCARE'];
        $brands = [];

        foreach ($brandNames as $brandName) {
            $brand = Brand::firstOrCreate(
                ['name' => $brandName, 'main_category_id' => $skincareCategoryId],
                [
                    'description' => "Brand untuk {$brandName}",
                    'is_active' => true,
                ]
            );

            $brands[] = $brand;
        }

        return $brands;
    }

    /**
     * Create sub-brands and the entire structure for a brand
     */
    private function createSubBrandsStructure(Brand $brand): void
    {
        $subBrandTypes = [
            'Skincare' => [
                'description' => 'Produk perawatan kulit',
                'categories' => [
                    'Facial Wash' => [
                        'description' => 'Pembersih wajah',
                        'types' => [
                            'Foam Cleanser' => [
                                'description' => 'Pembersih wajah berbusa',
                                'sizes' => [
                                    'Small (50ml)' => [
                                        'description' => 'Ukuran kecil 50ml',
                                        'variants' => [
                                            'Original' => 'Formula original',
                                            'Sensitive' => 'Untuk kulit sensitif',
                                            'Acne Care' => 'Untuk kulit berjerawat',
                                        ]
                                    ],
                                    'Medium (100ml)' => [
                                        'description' => 'Ukuran sedang 100ml',
                                        'variants' => [
                                            'Original' => 'Formula original',
                                            'Sensitive' => 'Untuk kulit sensitif',
                                            'Acne Care' => 'Untuk kulit berjerawat',
                                        ]
                                    ],
                                    'Large (200ml)' => [
                                        'description' => 'Ukuran besar 200ml',
                                        'variants' => [
                                            'Original' => 'Formula original',
                                            'Sensitive' => 'Untuk kulit sensitif',
                                            'Acne Care' => 'Untuk kulit berjerawat',
                                        ]
                                    ],
                                ]
                            ],
                            'Gel Cleanser' => [
                                'description' => 'Pembersih wajah berbentuk gel',
                                'sizes' => [
                                    'Small (50ml)' => [
                                        'description' => 'Ukuran kecil 50ml',
                                        'variants' => [
                                            'Original' => 'Formula original',
                                            'Sensitive' => 'Untuk kulit sensitif',
                                            'Acne Care' => 'Untuk kulit berjerawat',
                                        ]
                                    ],
                                    'Medium (100ml)' => [
                                        'description' => 'Ukuran sedang 100ml',
                                        'variants' => [
                                            'Original' => 'Formula original',
                                            'Sensitive' => 'Untuk kulit sensitif',
                                            'Acne Care' => 'Untuk kulit berjerawat',
                                        ]
                                    ],
                                ]
                            ],
                        ]
                    ],
                    'Toner' => [
                        'description' => 'Penyegar wajah',
                        'types' => [
                            'Hydrating Toner' => [
                                'description' => 'Toner untuk hidrasi',
                                'sizes' => [
                                    'Small (100ml)' => [
                                        'description' => 'Ukuran kecil 100ml',
                                        'variants' => [
                                            'Original' => 'Formula original',
                                            'Sensitive' => 'Untuk kulit sensitif',
                                        ]
                                    ],
                                    'Large (200ml)' => [
                                        'description' => 'Ukuran besar 200ml',
                                        'variants' => [
                                            'Original' => 'Formula original',
                                            'Sensitive' => 'Untuk kulit sensitif',
                                        ]
                                    ],
                                ]
                            ],
                            'Exfoliating Toner' => [
                                'description' => 'Toner untuk eksfoliasi',
                                'sizes' => [
                                    'Small (100ml)' => [
                                        'description' => 'Ukuran kecil 100ml',
                                        'variants' => [
                                            'AHA BHA' => 'Dengan asam AHA dan BHA',
                                            'PHA' => 'Dengan asam PHA',
                                        ]
                                    ],
                                    'Large (200ml)' => [
                                        'description' => 'Ukuran besar 200ml',
                                        'variants' => [
                                            'AHA BHA' => 'Dengan asam AHA dan BHA',
                                            'PHA' => 'Dengan asam PHA',
                                        ]
                                    ],
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            'Makeup' => [
                'description' => 'Produk makeup',
                'categories' => [
                    'Foundation' => [
                        'description' => 'Alas bedak',
                        'types' => [
                            'Liquid Foundation' => [
                                'description' => 'Foundation cair',
                                'sizes' => [
                                    'Small (15ml)' => [
                                        'description' => 'Ukuran kecil 15ml',
                                        'variants' => [
                                            'Fair' => 'Untuk kulit terang',
                                            'Medium' => 'Untuk kulit medium',
                                            'Tan' => 'Untuk kulit sawo matang',
                                        ]
                                    ],
                                    'Medium (30ml)' => [
                                        'description' => 'Ukuran sedang 30ml',
                                        'variants' => [
                                            'Fair' => 'Untuk kulit terang',
                                            'Medium' => 'Untuk kulit medium',
                                            'Tan' => 'Untuk kulit sawo matang',
                                        ]
                                    ],
                                ]
                            ],
                            'Cushion' => [
                                'description' => 'Foundation berbentuk cushion',
                                'sizes' => [
                                    'Standard (15g)' => [
                                        'description' => 'Ukuran standar 15g',
                                        'variants' => [
                                            'Fair' => 'Untuk kulit terang',
                                            'Medium' => 'Untuk kulit medium',
                                            'Tan' => 'Untuk kulit sawo matang',
                                        ]
                                    ],
                                ]
                            ],
                        ]
                    ],
                    'Lipstick' => [
                        'description' => 'Lipstik',
                        'types' => [
                            'Matte Lipstick' => [
                                'description' => 'Lipstik matte',
                                'sizes' => [
                                    'Standard (3.5g)' => [
                                        'description' => 'Ukuran standar 3.5g',
                                        'variants' => [
                                            'Red' => 'Warna merah',
                                            'Pink' => 'Warna pink',
                                            'Nude' => 'Warna nude',
                                            'Brown' => 'Warna coklat',
                                        ]
                                    ],
                                ]
                            ],
                            'Lip Cream' => [
                                'description' => 'Lipstik cair',
                                'sizes' => [
                                    'Standard (4ml)' => [
                                        'description' => 'Ukuran standar 4ml',
                                        'variants' => [
                                            'Red' => 'Warna merah',
                                            'Pink' => 'Warna pink',
                                            'Nude' => 'Warna nude',
                                            'Brown' => 'Warna coklat',
                                        ]
                                    ],
                                ]
                            ],
                        ]
                    ],
                ]
            ],
        ];

        foreach ($subBrandTypes as $subBrandName => $subBrandData) {
            // Create or get sub-brand
            $subBrand = SubBrand::firstOrCreate(
                ['name' => $subBrandName, 'brand_id' => $brand->id],
                [
                    'description' => $subBrandData['description'],
                    'is_active' => true,
                ]
            );

            // Create categories for each sub-brand
            foreach ($subBrandData['categories'] as $categoryName => $categoryData) {
                $category = ProductCategory::firstOrCreate(
                    ['name' => $categoryName, 'sub_brand_id' => $subBrand->id],
                    [
                        'description' => $categoryData['description'],
                        'is_active' => true,
                    ]
                );

                // Create types for each category
                foreach ($categoryData['types'] as $typeName => $typeData) {
                    $type = ProductType::firstOrCreate(
                        ['name' => $typeName, 'product_category_id' => $category->id],
                        [
                            'description' => $typeData['description'],
                            'is_active' => true,
                        ]
                    );

                    // Create sizes for each type
                    foreach ($typeData['sizes'] as $sizeName => $sizeData) {
                        $size = ProductSize::firstOrCreate(
                            ['name' => $sizeName, 'product_type_id' => $type->id],
                            [
                                'description' => $sizeData['description'],
                                'is_active' => true,
                            ]
                        );

                        // Create variants for each size
                        foreach ($sizeData['variants'] as $variantName => $variantDescription) {
                            ProductVariant::firstOrCreate(
                                ['name' => $variantName, 'product_size_id' => $size->id],
                                [
                                    'description' => $variantDescription,
                                    'is_active' => true,
                                ]
                            );
                        }
                    }
                }
            }
        }
    }
} 