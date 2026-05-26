<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
        // If platform filter is set, only export that platform
        if (!empty($this->filters['platform'])) {
            $platforms = [ucfirst(strtolower($this->filters['platform']))];
        } else {
            $platforms = ['Shopee Lamourad', 'Shopee Liefmarket', 'Tiktok Lamourad', 'Tiktok Liefmarket', 'Offline'];
        }
        
        foreach ($platforms as $platform) {
            try {
                $orders = $this->getUnpaidOrdersForPlatform($platform);
                
                if ($orders->count() > 0) {
                    $sheets[] = new UnpaidOrdersPlatformSheet($platform, $orders);
                }
            } catch (\Exception $e) {
                Log::error("Error creating sheet for platform {$platform}: " . $e->getMessage());
                Log::error($e->getTraceAsString());
                // Continue with other platforms even if one fails
            }
        }
        
        // If no sheets were created, create an empty one to avoid errors
        if (empty($sheets)) {
            $sheets[] = new UnpaidOrdersPlatformSheet('No Data', collect());
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
        $query = Order::withoutGlobalScope('mainCategory')->with(['orderItems.platformProduct.mappingBarang', 'orderItems.warehouseStock.tax', 'platform', 'mainCategory'])
            ->where('platform_id', $platform->id)
            ->whereDoesntHave('shopeeFinancialTransactions')
            ->whereDoesntHave('shopee2FinancialTransactions')
            ->whereDoesntHave('tiktokFinancialTransactions')
            ->whereDoesntHave('tiktok2FinancialTransactions');

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
        
        // Get normal unpaid orders with all necessary relations loaded
        try {
            $normalUnpaidOrders = $query->orderBy($sortBy, $sortOrder)->get();
            
            // Filter orders: hanya tampilkan retur sebagian, retur full tidak akan muncul
            $allOrders = $normalUnpaidOrders->filter(function($order) {
                try {
                    // Ensure orderItems relation is loaded
                    if (!$order->relationLoaded('orderItems')) {
                        $order->load('orderItems.platformProduct.mappingBarang');
                    }
                    
                    // Check if order has retur penjualan with status 'selesai'
                    $hasReturSelesai = $order->returPenjualan()
                        ->where('status', 'selesai')
                        ->exists();
                    
                    // If it has retur selesai, only include if it's partial return (not full return)
                    if ($hasReturSelesai) {
                        // Only include partial returns (not fully returned)
                        return !$order->isFullyReturned();
                    }
                    
                    // Otherwise, exclude fully returned orders
                    return !$order->isFullyReturned();
                } catch (\Exception $e) {
                    // If there's an error checking, include the order to be safe
                    Log::warning("Error checking if order {$order->id} is fully returned: " . $e->getMessage());
                    return true;
                }
            });
            
            // Sort collection
            $allOrders = $allOrders->sortBy([
                [$sortBy, $sortOrder]
            ]);
            
            return $allOrders;
        } catch (\Exception $e) {
            Log::error("Error in getUnpaidOrdersForPlatform for {$platformName}: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return collect();
        }
    }
} 