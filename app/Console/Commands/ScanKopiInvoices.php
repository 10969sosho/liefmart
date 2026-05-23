<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TiktokFinancialTransaction;
use App\Models\Tiktok2FinancialTransaction;

class ScanKopiInvoices extends Command
{
    protected $signature = 'tiktok:scan-kopi';
    protected $description = 'List all KOPI PKP and KOPI NON PKP invoices from Tiktok transactions';

    public function handle()
    {
        $this->info('Scanning for KOPI PKP and KOPI NON PKP Invoices...');

        $this->scanPlatform(TiktokFinancialTransaction::class, 'TikTok 1');
        $this->scanPlatform(Tiktok2FinancialTransaction::class, 'TikTok 2');

        return 0;
    }

    private function scanPlatform($modelClass, $platformName)
    {
        $this->info("\n--- Results for {$platformName} ---");

        // KOPI PKP: Usually contains HPNSDA-OLK/01
        $kopiPkp = $modelClass::where('no_invoice', 'like', '%HPNSDA-OLK/01%')
            ->orderBy('tanggal_order')
            ->get(['no_order', 'no_invoice', 'tanggal_order']);

        $this->info("\nKOPI PKP (HPNSDA-OLK/01) - Found: " . $kopiPkp->count());
        if ($kopiPkp->count() > 0) {
            foreach ($kopiPkp as $t) {
                $this->line("  {$t->no_invoice} | Order: {$t->no_order} | Date: {$t->tanggal_order->format('Y-m-d')}");
            }
        } else {
            $this->line("  (No invoices found)");
        }

        // KOPI NON PKP: Usually contains HPNSDA-OLK/02
        $kopiNonPkp = $modelClass::where('no_invoice', 'like', '%HPNSDA-OLK/02%')
            ->orderBy('tanggal_order')
            ->get(['no_order', 'no_invoice', 'tanggal_order']);

        $this->info("\nKOPI NON PKP (HPNSDA-OLK/02) - Found: " . $kopiNonPkp->count());
        if ($kopiNonPkp->count() > 0) {
            foreach ($kopiNonPkp as $t) {
                $this->line("  {$t->no_invoice} | Order: {$t->no_order} | Date: {$t->tanggal_order->format('Y-m-d')}");
            }
        } else {
            $this->line("  (No invoices found)");
        }
    }
}
