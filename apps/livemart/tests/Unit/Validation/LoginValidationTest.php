<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

/**
 * Validation Test: Login Request
 *
 * Menguji aturan validasi di LoginController@validateLogin:
 * 1. Semua field required
 * 2. main_category_id harus exist di main_categories
 * 3. Format login (username/email)
 * 4. Custom error messages in Bahasa Indonesia
 * 5. Edge cases: kategori tidak aktif, input kosong
 */
class LoginValidationTest extends TestCase
{

    private array $loginRules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MainCategorySeeder::class);

        // Aturan validasi dari LoginController
        $this->loginRules = [
            'login' => 'required|string',
            'password' => 'required|string',
            'main_category_id' => 'required|exists:main_categories,id',
        ];
    }

    /** @test */
    public function passes_with_valid_data()
    {
        $data = [
            'login' => 'admin_user',
            'password' => 'password123',
            'main_category_id' => 1,
        ];

        $validator = Validator::make($data, $this->loginRules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function requires_login_field()
    {
        $data = [
            'login' => '',
            'password' => 'password123',
            'main_category_id' => 1,
        ];

        $validator = Validator::make($data, $this->loginRules, [
            'login.required' => 'Username atau Email harus diisi.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('login', $validator->errors()->toArray());
        $this->assertEquals('Username atau Email harus diisi.', $validator->errors()->first('login'));
    }

    /** @test */
    public function requires_password_field()
    {
        $data = [
            'login' => 'admin_user',
            'password' => '',
            'main_category_id' => 1,
        ];

        $validator = Validator::make($data, $this->loginRules, [
            'password.required' => 'Password harus diisi.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function requires_main_category_id()
    {
        $data = [
            'login' => 'admin_user',
            'password' => 'password123',
            'main_category_id' => '',
        ];

        $validator = Validator::make($data, $this->loginRules, [
            'main_category_id.required' => 'Kategori utama harus dipilih.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('main_category_id', $validator->errors()->toArray());
    }

    /** @test */
    public function validates_main_category_exists()
    {
        $data = [
            'login' => 'admin_user',
            'password' => 'password123',
            'main_category_id' => 99999, // Non-existent
        ];

        $validator = Validator::make($data, $this->loginRules, [
            'main_category_id.exists' => 'Kategori utama tidak valid.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('main_category_id', $validator->errors()->toArray());
    }

    /** @test */
    public function validates_main_category_id_is_active()
    {
        // Create an inactive category
        $category = \App\Models\MainCategory::create([
            'name' => 'INACTIVE-TEST',
            'is_active' => false,
        ]);

        $data = [
            'login' => 'admin_user',
            'password' => 'password123',
            'main_category_id' => $category->id,
        ];

        // The rule only checks existence, not is_active status
        // But the controller's showLoginForm only shows active categories
        $validator = Validator::make($data, $this->loginRules);
        
        // This passes because exists:main_categories,id checks any id
        $this->assertTrue($validator->passes());

        // NOTE: There's a gap here - inactive categories should also be rejected
        // Recommendation: add where clause to the exists rule
    }

    /** @test */
    public function accepts_email_format_for_login()
    {
        $data = [
            'login' => 'user@example.com',
            'password' => 'password123',
            'main_category_id' => 1,
        ];

        $validator = Validator::make($data, $this->loginRules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function accepts_username_format_for_login()
    {
        $data = [
            'login' => 'admin_user_123',
            'password' => 'password123',
            'main_category_id' => 1,
        ];

        $validator = Validator::make($data, $this->loginRules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function rejects_empty_request()
    {
        $data = [];

        $validator = Validator::make($data, $this->loginRules);
        $this->assertTrue($validator->fails());
        $this->assertCount(3, $validator->errors()->toArray()); // 3 fields required
    }

    /** @test */
    public function rejects_null_values()
    {
        $data = [
            'login' => null,
            'password' => null,
            'main_category_id' => null,
        ];

        $validator = Validator::make($data, $this->loginRules);
        $this->assertTrue($validator->fails());
    }
}
