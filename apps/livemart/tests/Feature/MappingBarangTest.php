<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PlatformProduct;
use App\Models\MappingBarang;
use App\Models\Product;
use App\Models\Platform;
use App\Models\MainCategory;
use App\Models\User;

/**
 * Feature Test: Mapping Barang & Barang Platform
 *
 * Mapping menghubungkan platform_product → internal product.
 */
class MappingBarangTest extends TestCase
{

    private User $user;
    private MainCategory $skincare;
    private Platform $platform;
    private Product $product;
    private PlatformProduct $platformProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $this->skincare = MainCategory::where('name', 'SKINCARE')->first();
        $this->platform = Platform::first();
        $this->product = Product::factory()->create(['name' => 'Mapping Product', 'main_category_id' => $this->skincare->id]);
        $this->platformProduct = PlatformProduct::create([
            'platform_id' => $this->platform->id,
            'platform_product_name' => 'Platform Test Product',
            'variant' => 'Variant 1',
        ]);

        session(['main_category_id' => $this->skincare->id]);
        $this->user = $this->loginAsSuperadmin();
    }

    /** @test */
    public function mapping_index_accessible()
    {
        $response = $this->get(route('master.mapping.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function mapping_create_accessible()
    {
        $response = $this->get(route('master.mapping.create'));
        $response->assertStatus(200);
    }

    /** @test】
    public function mapping_store_creates_mapping()
    {
        $response = $this->post(route('master.mapping.store'), [
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('mapping_barangs', [
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
        ]);
    }

    /** @test */
    public function mapping_details_endpoint()
    {
        $mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 2, 'version' => 1, 'is_active' => true, 'valid_from' => now(),
        ]);

        $response = $this->get(route('master.mapping.details', $this->platformProduct->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function mapping_check_unmapped_accessible()
    {
        $response = $this->get(route('master.mapping.check', $this->platform->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function mapping_export_excel()
    {
        $response = $this->get(route('master.mapping.export.excel'));
        $response->assertStatus(200);
    }

    /** @test */
    public function mapping_edit_accessible()
    {
        $mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1, 'version' => 1, 'is_active' => true, 'valid_from' => now(),
        ]);

        $response = $this->get(route('master.mapping.edit', $mapping->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function mapping_update_changes()
    {
        $mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1, 'version' => 1, 'is_active' => true, 'valid_from' => now(),
        ]);

        $response = $this->put(route('master.mapping.update', $mapping->id), [
            'quantity' => 5,
        ]);
        $response->assertRedirect();
    }

    /** @test */
    public function mapping_delete_removes()
    {
        $mapping = MappingBarang::create([
            'platform_product_id' => $this->platformProduct->id,
            'product_id' => $this->product->id,
            'quantity' => 1, 'version' => 1, 'is_active' => true, 'valid_from' => now(),
        ]);

        $response = $this->delete(route('master.mapping.destroy', $mapping->id));
        $response->assertRedirect();
    }

    /** @test */
    public function mapping_version_history_accessible()
    {
        $response = $this->get(route('master.mapping.version-history', $this->platformProduct->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function barang_platform_index_accessible()
    {
        $response = $this->get(route('barang-platform.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function barang_platform_by_platform_endpoint()
    {
        $response = $this->get(route('barang-platform.by-platform', $this->platform->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function barang_platform_create_form_accessible()
    {
        $response = $this->get(route('barang-platform.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function guest_blocked()
    {
        auth()->logout();
        $this->get(route('master.mapping.index'))->assertRedirect(route('login'));
    }
}
