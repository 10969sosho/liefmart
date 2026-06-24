<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Platform;
use App\Models\MainCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Feature Test: Import Flows (Shopee, Shopee2, TikTok, TikTok2)
 *
 * Menguji halaman upload, preview, dan process import.
 */
class ImportFlowTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperadminRoleSeeder::class);

        $skincare = MainCategory::where('name', 'SKINCARE')->first();
        session(['main_category_id' => $skincare->id]);
        $this->loginAsSuperadmin();
    }

    /** @test */
    public function shopee_import_excel_page_accessible()
    {
        $response = $this->get(route('sales.shopee.import-excel'));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee2_import_excel_page_accessible()
    {
        $response = $this->get(route('sales.shopee2.import-excel'));
        $response->assertStatus(200);
    }

    /** @test */
    public function tiktok_import_excel_page_accessible()
    {
        $response = $this->get(route('sales.tiktok.import-excel'));
        $response->assertStatus(200);
    }

    /** @test */
    public function tiktok2_import_excel_page_accessible()
    {
        $response = $this->get(route('sales.tiktok2.import-excel'));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_import_without_file_fails()
    {
        $response = $this->post(route('sales.shopee.preview-import'), []);
        $response->assertSessionHasErrors('excel_file');
    }

    /** @test */
    public function shopee2_import_without_file_fails()
    {
        $response = $this->post(route('sales.shopee2.preview-import'), []);
        $response->assertSessionHasErrors('excel_file');
    }

    /** @test */
    public function tiktok_import_without_file_fails()
    {
        $response = $this->post(route('sales.tiktok.preview-import'), []);
        $response->assertSessionHasErrors('excel_file');
    }

    /** @test */
    public function tiktok2_import_without_file_fails()
    {
        $response = $this->post(route('sales.tiktok2.preview-import'), []);
        $response->assertSessionHasErrors('excel_file');
    }

    /** @test */
    public function shopee_preview_import_page_accessible()
    {
        $response = $this->get(route('sales.shopee.show-preview'));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee2_preview_import_page_accessible()
    {
        $response = $this->get(route('sales.shopee2.show-preview'));
        $response->assertStatus(200);
    }

    /** @test */
    public function tiktok_preview_import_page_accessible()
    {
        $response = $this->get(route('sales.tiktok.show-preview'));
        $response->assertStatus(200);
    }

    /** @test */
    public function tiktok2_preview_import_page_accessible()
    {
        $response = $this->get(route('sales.tiktok2.show-preview'));
        $response->assertStatus(200);
    }

    /** @test */
    public function shopee_import_with_invalid_file_type_fails()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->post(route('sales.shopee.preview-import'), [
            'excel_file' => $file,
        ]);
        $response->assertSessionHasErrors('excel_file');
    }

    /** @test */
    public function guest_blocked_from_import_pages()
    {
        auth()->logout();
        $this->get(route('sales.shopee.import-excel'))->assertRedirect(route('login'));
        $this->get(route('sales.shopee2.import-excel'))->assertRedirect(route('login'));
    }
}
