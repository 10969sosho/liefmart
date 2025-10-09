<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UnpaidOrdersExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        // Get unpaid orders for each platform
        $platforms = ['Blibli', 'Shopee', 'TikTok', 'Tokopedia'];
        
        foreach ($platforms as $platform) {
            $platformFilter = array_merge($this->filters, ['platform' => $platform]);
            $orders = $this->getUnpaidOrdersForPlatform($platform);
            
            if ($orders->count() > 0) {
                $sheets[] = new UnpaidOrdersPlatformSheet($platform, $orders);
            }
        }
        
        return $sheets;
    }

    private function getUnpaidOrdersForPlatform($platformName)
    {
        // Get platform ID
        $platform = \App\Models\Platform::whereRaw('LOWER(name) = ?', [strtolower($platformName)])->first();
        if (!$platform) {
            return collect();
        }

        // Build base query for this platform
        $isBlibli = strtolower($platformName) === 'blibli';
        
        if ($isBlibli) {
            $query = Order::withoutGlobalScope('mainCategory')->with(['orderItems.platformProduct.mappingBarang', 'orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->where('platform_id', $platform->id)
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions');
        } else {
            $query = Order::with(['orderItems.platformProduct.mappingBarang', 'orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
                ->where('platform_id', $platform->id)
                ->whereDoesntHave('shopeeFinancialTransactions')
                ->whereDoesntHave('tokopediaFinancialTransactions')
                ->whereDoesntHave('tiktokFinancialTransactions')
                ->whereDoesntHave('blibliFinancialTransactions');
        }

        // Apply filters
        if (!empty($this->filters['from_date'])) {
            $query->whereDate('tanggal', '>=', $this->filters['from_date']);
        }
        if (!empty($this->filters['to_date'])) {
            $query->whereDate('tanggal', '<=', $this->filters['to_date']);
        }
        if (!empty($this->filters['order_number'])) {
            $query->where('order_number', 'like', '%' . $this->filters['order_number'] . '%');
        }
        if (!empty($this->filters['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $this->filters['customer_name'] . '%');
        }
        if (!empty($this->filters['min_value'])) {
            $query->whereHas('orderItems', function($q) {
                $q->selectRaw('order_id, SUM(price_after_discount * quantity) as total_value')
                  ->groupBy('order_id')
                  ->having('total_value', '>=', $this->filters['min_value']);
            });
        }
        if (!empty($this->filters['max_value'])) {
            $query->whereHas('orderItems', function($q) {
                $q->selectRaw('order_id, SUM(price_after_discount * quantity) as total_value')
                  ->groupBy('order_id')
                  ->having('total_value', '<=', $this->filters['max_value']);
            });
        }
        if (!empty($this->filters['min_age'])) {
            $query->where('tanggal', '<=', now()->subDays($this->filters['min_age']));
        }
        if (!empty($this->filters['max_age'])) {
            $query->where('tanggal', '>=', now()->subDays($this->filters['max_age']));
        }

        // Define sort parameters
        $sortBy = ($this->filters['sort_by'] ?? 'tanggal') === 'tanggal' ? 'tanggal' : 'order_number';
        $sortOrder = ($this->filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        
        // Get normal unpaid orders
        $normalUnpaidOrders = $query->orderBy($sortBy, $sortOrder)->get();
        
        // Filter out fully returned orders
        $allOrders = $normalUnpaidOrders->filter(function($order) {
            return !$order->isFullyReturned();
        });
        
        // Sort collection
        $allOrders = $allOrders->sortBy([
            [$sortBy, $sortOrder]
        ]);
        
        return $allOrders;
    }
} 