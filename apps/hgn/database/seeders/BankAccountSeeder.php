<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Shared\Models\BankAccount;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        BankAccount::create([
            'bank_name' => 'BCA',
            'account_name' => 'HARVEST GLOBAL NIAGA',
            'account_number' => '123456789',
            'is_active' => true,
        ]);
    }
}
