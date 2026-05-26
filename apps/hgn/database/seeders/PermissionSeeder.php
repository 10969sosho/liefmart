<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Sales Permissions
            ['name' => 'sales.view', 'display_name' => 'Lihat Penjualan', 'description' => 'Dapat melihat data penjualan', 'category' => 'sales'],
            ['name' => 'sales.create', 'display_name' => 'Tambah Penjualan', 'description' => 'Dapat menambah data penjualan', 'category' => 'sales'],
            ['name' => 'sales.edit', 'display_name' => 'Edit Penjualan', 'description' => 'Dapat mengubah data penjualan', 'category' => 'sales'],
            ['name' => 'sales.delete', 'display_name' => 'Hapus Penjualan', 'description' => 'Dapat menghapus data penjualan', 'category' => 'sales'],
            ['name' => 'sales.offline', 'display_name' => 'Penjualan Offline', 'description' => 'Dapat mengakses penjualan offline', 'category' => 'sales'],
            ['name' => 'sales.online', 'display_name' => 'Penjualan Online', 'description' => 'Dapat mengakses penjualan online', 'category' => 'sales'],
            ['name' => 'sales.import', 'display_name' => 'Import Penjualan', 'description' => 'Dapat mengimport data penjualan', 'category' => 'sales'],
            ['name' => 'sales.export', 'display_name' => 'Export Penjualan', 'description' => 'Dapat mengexport data penjualan', 'category' => 'sales'],

            // Finance Permissions
            ['name' => 'finance.view', 'display_name' => 'Lihat Keuangan', 'description' => 'Dapat melihat data keuangan', 'category' => 'finance'],
            ['name' => 'finance.create', 'display_name' => 'Tambah Keuangan', 'description' => 'Dapat menambah data keuangan', 'category' => 'finance'],
            ['name' => 'finance.edit', 'display_name' => 'Edit Keuangan', 'description' => 'Dapat mengubah data keuangan', 'category' => 'finance'],
            ['name' => 'finance.delete', 'display_name' => 'Hapus Keuangan', 'description' => 'Dapat menghapus data keuangan', 'category' => 'finance'],
            ['name' => 'finance.offline', 'display_name' => 'Keuangan Offline', 'description' => 'Dapat mengakses keuangan offline', 'category' => 'finance'],
            ['name' => 'finance.shopee', 'display_name' => 'Keuangan Shopee', 'description' => 'Dapat mengakses keuangan Shopee', 'category' => 'finance'],
            ['name' => 'finance.tokopedia', 'display_name' => 'Keuangan Tokopedia', 'description' => 'Dapat mengakses keuangan Tokopedia', 'category' => 'finance'],
            ['name' => 'finance.tiktok', 'display_name' => 'Keuangan TikTok', 'description' => 'Dapat mengakses keuangan TikTok', 'category' => 'finance'],
            ['name' => 'finance.blibli', 'display_name' => 'Keuangan Blibli', 'description' => 'Dapat mengakses keuangan Blibli', 'category' => 'finance'],

            // Warehouse Permissions
            ['name' => 'warehouse.view', 'display_name' => 'Lihat Gudang', 'description' => 'Dapat melihat data gudang', 'category' => 'warehouse'],
            ['name' => 'warehouse.create', 'display_name' => 'Tambah Gudang', 'description' => 'Dapat menambah data gudang', 'category' => 'warehouse'],
            ['name' => 'warehouse.edit', 'display_name' => 'Edit Gudang', 'description' => 'Dapat mengubah data gudang', 'category' => 'warehouse'],

            // Analytics Permissions
            ['name' => 'analytics.view', 'display_name' => 'Lihat Analitik', 'description' => 'Dapat melihat analitik', 'category' => 'analytics'],
            ['name' => 'analytics.sales', 'display_name' => 'Analitik Penjualan', 'description' => 'Dapat melihat analitik penjualan', 'category' => 'analytics'],
            ['name' => 'analytics.finance', 'display_name' => 'Analitik Keuangan', 'description' => 'Dapat melihat analitik keuangan', 'category' => 'analytics'],

            // Master Data Permissions
            ['name' => 'master.view', 'display_name' => 'Lihat Master Data', 'description' => 'Dapat melihat master data', 'category' => 'master'],
            ['name' => 'master.create', 'display_name' => 'Tambah Master Data', 'description' => 'Dapat menambah master data', 'category' => 'master'],
            ['name' => 'master.edit', 'display_name' => 'Edit Master Data', 'description' => 'Dapat mengubah master data', 'category' => 'master'],
            ['name' => 'master.delete', 'display_name' => 'Hapus Master Data', 'description' => 'Dapat menghapus master data', 'category' => 'master'],

            // User Management Permissions
            ['name' => 'users.view', 'display_name' => 'Lihat User', 'description' => 'Dapat melihat daftar user', 'category' => 'user-management'],
            ['name' => 'users.create', 'display_name' => 'Tambah User', 'description' => 'Dapat menambah user baru', 'category' => 'user-management'],
            ['name' => 'users.edit', 'display_name' => 'Edit User', 'description' => 'Dapat mengubah data user', 'category' => 'user-management'],
            ['name' => 'users.delete', 'display_name' => 'Hapus User', 'description' => 'Dapat menghapus user', 'category' => 'user-management'],
            ['name' => 'roles.view', 'display_name' => 'Lihat Role', 'description' => 'Dapat melihat daftar role', 'category' => 'user-management'],
            ['name' => 'roles.create', 'display_name' => 'Tambah Role', 'description' => 'Dapat menambah role baru', 'category' => 'user-management'],
            ['name' => 'roles.edit', 'display_name' => 'Edit Role', 'description' => 'Dapat mengubah role', 'category' => 'user-management'],
            ['name' => 'roles.delete', 'display_name' => 'Hapus Role', 'description' => 'Dapat menghapus role', 'category' => 'user-management'],

            // Exports (dedicated)
            ['name' => 'exports.all', 'display_name' => 'Semua Export', 'description' => 'Dapat melakukan semua jenis export', 'category' => 'exports'],
            ['name' => 'exports.warehouse', 'display_name' => 'Export Gudang', 'description' => 'Dapat mengekspor data gudang/stock', 'category' => 'exports'],
            ['name' => 'exports.penerimaan', 'display_name' => 'Export Penerimaan', 'description' => 'Dapat mengekspor data penerimaan', 'category' => 'exports'],
            ['name' => 'exports.sales', 'display_name' => 'Export Penjualan', 'description' => 'Dapat mengekspor data penjualan', 'category' => 'exports'],
            ['name' => 'exports.analytics', 'display_name' => 'Export Analytics', 'description' => 'Dapat mengekspor laporan analytics', 'category' => 'exports'],
            ['name' => 'exports.finance', 'display_name' => 'Export Keuangan', 'description' => 'Dapat mengekspor data keuangan', 'category' => 'exports'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }

        $this->command->info('Permissions seeded successfully!');
    }
}