<?php

namespace App\Exports\Handlers\Offline;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\OfflineSalesByProductExport;
use App\Models\Customer;
use App\Models\OfflineSale;
use App\Models\Product;

class OfflineSalesByProductHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'offline_sales_by_product';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $selectedCustomer = $filters['customer_id'] ?? null;
        $selectedProduct = $filters['product_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'value_highest';

        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        $query = OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items.product', 'customerInfo']);

        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }

        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }

        $sales = $query->get();

        // Filter sale items by product if selected
        $allSaleItems = collect();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if (!$selectedProduct || $item->product_id == $selectedProduct) {
                    $allSaleItems->push($item);
                }
            }
        }

        // Group items by product
        $productSummary = $allSaleItems->groupBy('product_id')->map(function ($items, $productId) {
            $product = $items->first()->product;
            $productName = $product ? $product->name : 'Unknown';

            $totalQuantity = $items->sum('quantity');
            $totalValue = $items->sum('subtotal');

            return [
                'product_id' => $productId,
                'product_name' => $productName,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'avg_price' => $totalQuantity > 0 ? $totalValue / $totalQuantity : 0,
            ];
        });

        // Sort data
        switch ($sortBy) {
            case 'value_highest':
                $productSummary = $productSummary->sortByDesc('total_value');
                break;
            case 'value_lowest':
                $productSummary = $productSummary->sortBy('total_value');
                break;
            case 'quantity_highest':
                $productSummary = $productSummary->sortByDesc('total_quantity');
                break;
            case 'quantity_lowest':
                $productSummary = $productSummary->sortBy('total_quantity');
                break;
            case 'name_asc':
                $productSummary = $productSummary->sortBy('product_name');
                break;
            case 'name_desc':
                $productSummary = $productSummary->sortByDesc('product_name');
                break;
            default:
                $productSummary = $productSummary->sortByDesc('total_value');
        }

        // Calculate summary
        $summary = [
            'total_products' => $productSummary->count(),
            'total_value' => $productSummary->sum('total_value'),
            'total_quantity' => $productSummary->sum('total_quantity'),
        ];

        $customerName = $selectedCustomer ? $customers->where('id', $selectedCustomer)->first()->name ?? 'Unknown' : null;
        $productName = $selectedProduct ? $products->where('id', $selectedProduct)->first()->name ?? 'Unknown' : null;

        $filename = 'penjualan-offline-by-product-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new OfflineSalesByProductExport($productSummary, $summary, $startDate, $endDate, $customerName, $productName),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
