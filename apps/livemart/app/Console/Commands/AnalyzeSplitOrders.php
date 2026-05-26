<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TiktokFinancialTransaction;
use App\Models\Order;

class AnalyzeSplitOrders extends Command
{
    protected $signature = 'tiktok:analyze-split';
    protected $description = 'Deep analyze the 22 split invoice orders';

    public function handle()
    {
        // List of problematic orders identified previously
        $problemOrders = [
            '580190767566718313', '580235625308849204', '580249683875104630', '580252130121516910',
            '580488910974256130', '580510048616089014', '580540637438510352', '580579162861831746',
            '580617772393596426', '580646143641749405', '580647941514953775', '580718429199239014',
            '580916351256200473', '580956605787047062', '581034942212834975', '581100499038471959',
            '581132994612594532', '581240412971828817', '581271772368307437', '581294420233323877',
            '581389034032891241', '581612376994187061'
        ];

        $this->info("Analyzing " . count($problemOrders) . " potentially problematic orders...");
        $this->info("Logic: If Order has MIXED Tax IDs (e.g. PKP & NON PKP), Split is CORRECT.");
        $this->info("       If Order has SINGLE Tax ID (e.g. Only PKP), Split is INCORRECT.\n");

        $trueErrors = [];
        $correctlySplit = [];

        foreach ($problemOrders as $noOrder) {
            // 1. Get Transactions Count
            $transactionCount = TiktokFinancialTransaction::where('no_order', $noOrder)->count();
            
            // 2. Get Order Items & Warehouse Stock info to see TAX IDs
            $order = Order::with(['orderItems.warehouseStock'])->where('order_number', $noOrder)->first();
            
            if (!$order) {
                $this->error("Order {$noOrder} not found!");
                continue;
            }

            $taxIds = [];
            foreach ($order->orderItems as $item) {
                if ($item->warehouseStock) {
                    $taxIds[] = $item->warehouseStock->tax_id;
                }
            }
            $uniqueTaxIds = array_unique($taxIds);
            sort($uniqueTaxIds);

            $isMixed = count($uniqueTaxIds) > 1;
            $taxIdString = implode(', ', $uniqueTaxIds);
            
            // Mapping check (3=PKP, 4=NON PKP)
            $hasPKP = in_array(3, $uniqueTaxIds);
            $hasNONPKP = in_array(4, $uniqueTaxIds);
            
            if ($isMixed) {
                $correctlySplit[] = [
                    'order' => $noOrder,
                    'tax_ids' => $taxIdString,
                    'note' => ($hasPKP && $hasNONPKP) ? 'Contains PKP & NON PKP (Correct)' : 'Mixed Tax IDs'
                ];
            } else {
                $trueErrors[] = [
                    'order' => $noOrder,
                    'tax_ids' => $taxIdString,
                    'note' => 'Single Tax ID but Split Invoice!'
                ];
            }
        }

        $this->info("\n=== CORRECTLY SPLIT ORDERS (Mixed PKP & NON PKP) ===");
        $this->table(['Order Number', 'Tax IDs', 'Note'], $correctlySplit);

        $this->info("\n=== INCORRECTLY SPLIT ORDERS (True Errors) ===");
        $this->table(['Order Number', 'Tax IDs', 'Note'], $trueErrors);
        
        return 0;
    }
}
