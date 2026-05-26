<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\MappingBarang;
use App\Models\Order;
use App\Models\ReturPenjualanDetail;
use App\Queries\Analytics\Sales\SalesDetailQuery;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesExportMappedExport implements FromIterator, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
        // Increase memory limit for this export process
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 300); // 5 minutes
    }

    public function iterator(): \Iterator
    {
        return $this->getRows();
    }

    private function getEffectiveMappings($order, $item)
    {
        if (!$item->platformProduct || !$item->platformProduct->mappingBarang) {
            return collect([]);
        }

        $orderCreatedAt = $order->created_at ?? $item->created_at;
        return MappingBarang::getMappingsForOrderCreatedAt($item->platformProduct->id, $orderCreatedAt);
    }

    public function getRows(): \Generator
    {
        DB::connection()->disableQueryLog();
        
        $perPage = 100; 
        $page = 1;
        $no = 1;
        
        do {
            $sqlQuery = SalesDetailQuery::build($this->filters, $perPage, $page);
            $orderResults = DB::select($sqlQuery);
            
            if (empty($orderResults)) {
                break;
            }
            
            $orderIds = collect($orderResults)->pluck('id')->toArray();
            
            if (!empty($orderIds)) {
                $ordersDict = Order::withoutGlobalScope('mainCategory')
                    ->whereIn('id', $orderIds)
                    ->with([
                        'orderItems' => function($query) {
                            $query->select('id', 'order_id', 'platform_product_id', 'quantity', 'price_after_discount', 'tracking_number');
                        },
                        'orderItems.platformProduct' => function($query) {
                            $query->select('id', 'platform_product_name', 'variant');
                        },
                        'orderItems.platformProduct.mappingBarang' => function($query) {
                            $query->select('id', 'platform_product_id', 'product_id', 'quantity', 'is_active', 'version', 'valid_from', 'created_at');
                        },
                        'orderItems.platformProduct.mappingBarang.product' => function($query) {
                            $query->select('id', 'name', 'sku');
                        },
                        'platform' => function($query) {
                            $query->select('id', 'name');
                        }
                    ])
                    ->get()
                    ->keyBy('id');

                $orderItemIds = $ordersDict->values()->pluck('orderItems')->flatten()->pluck('id')->filter()->values();
                $returQtyByOrderItemId = collect();
                if ($orderItemIds->isNotEmpty()) {
                    $returQtyByOrderItemId = ReturPenjualanDetail::query()
                        ->whereIn('order_item_id', $orderItemIds->all())
                        ->whereHas('returPenjualan', function($q) {
                            $q->whereIn('status', ['draft', 'selesai']);
                        })
                        ->groupBy('order_item_id')
                        ->selectRaw('order_item_id, SUM(qty) as qty')
                        ->pluck('qty', 'order_item_id');
                }

                foreach ($orderIds as $id) {
                    if (!isset($ordersDict[$id])) continue;
                    $order = $ordersDict[$id];
                    
                    $orderHari = $order->tanggal ? Carbon::parse($order->tanggal)->locale('id')->isoFormat('dddd') : '-';
                    
                    // Pre-calculate order totals
                    $totalOrderValue = 0;
                    $totalOrderVolume = 0;
                    foreach ($order->orderItems as $item) {
                        $totalOrderValue += ($item->price_after_discount * $item->quantity);
                        $totalOrderVolume += $item->quantity;
                    }

                    foreach ($order->orderItems as $item) {
                        $mappings = $this->getEffectiveMappings($order, $item);

                        $qtyReturIndividual = (float) ($returQtyByOrderItemId[$item->id] ?? 0);
                        $packageQuantity = $mappings->count() > 0 ? (float) $mappings->sum('quantity') : 1.0;
                        $qtyRetur = $packageQuantity > 0 ? ($qtyReturIndividual / $packageQuantity) : $qtyReturIndividual;
                        $originalQty = (float) ($item->quantity ?? 0) + $qtyRetur;
                        $totalItem = ((float) ($item->price_after_discount ?? 0)) * $originalQty;
                        
                        $internalNames = 'No Mapping';
                        $internalSkus = '-';
                        $internalQty = '-';
                        if ($mappings->count() > 0) {
                            $internalNames = $mappings->map(function($mapping) {
                                return $mapping->product ? $mapping->product->name : 'Product Not Found';
                            })->implode("\n");
                            $internalSkus = $mappings->map(function($mapping) {
                                return $mapping->product ? $mapping->product->sku : '-';
                            })->implode("\n");
                            $internalQty = $mappings->map(function($mapping) use ($originalQty) {
                                return number_format(((float) $mapping->quantity) * $originalQty, 2);
                            })->implode("\n");
                        }

                        yield [
                            'No' => $no++,
                            'Tanggal' => $order->tanggal ? Carbon::parse($order->tanggal)->format('d-m-Y') : '-',
                            'Hari' => $orderHari,
                            'No Order' => "'" . $order->order_number,
                            'Platform' => $order->platform ? $order->platform->name : '-',
                            'Nama Barang (Platform)' => $item->platformProduct ? $item->platformProduct->platform_product_name : '-',
                            'Varian' => $item->platformProduct ? $item->platformProduct->variant : '-',
                            'Qty' => $originalQty,
                            'QTY Retur' => $qtyRetur,
                            'Harga' => $item->price_after_discount,
                            'Total Item' => $totalItem,
                            'Nama Barang (Internal)' => $internalNames,
                            'SKU (Internal)' => $internalSkus,
                            'Qty (Internal)' => $internalQty,
                            'Qty Total' => $totalOrderVolume,
                            'Total Invoice' => $totalOrderValue,
                            'No Resi' => $item->tracking_number,
                        ];
                    }
                }
                
                // Clear memory
                $ordersDict = null;
                $orderIds = null;
                $orderResults = null;
                unset($ordersDict, $orderIds, $orderResults);
                gc_collect_cycles();
            }
            
            $page++;
            
        } while (true);
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Hari',
            'No Order',
            'Platform',
            'Nama Barang (Platform)',
            'Varian',
            'Qty',
            'QTY Retur',
            'Harga',
            'Total Item',
            'Nama Barang (Internal)',
            'SKU (Internal)',
            'Qty (Internal)',
            'Qty Total',
            'Total Invoice',
            'No Resi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
