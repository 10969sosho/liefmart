<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\WarehouseStock;
use App\Models\BarangKeluar;

class StockMutationExport implements WithMultipleSheets
{
    protected $products;
    protected $startDate;
    protected $endDate;
    protected $includeEmpty;

    public function __construct($products, $startDate = null, $endDate = null, $includeEmpty = false)
    {
        $this->products = $products;
        $this->startDate = $startDate ? Carbon::parse($startDate) : null;
        $this->endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        $this->includeEmpty = $includeEmpty;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Add a summary sheet
        $sheets[] = new StockMutationSummarySheet($this->products);
        
        // Add a sheet for each product, sorted by product name for consistency
        $sortedProducts = collect($this->products)->sortBy(function($product) {
            return $product['product']->name ?? $product->name ?? '';
        });
        
        foreach ($sortedProducts as $product) {
            $sheets[] = new StockMutationProductSheet(
                $product, 
                $this->startDate, 
                $this->endDate, 
                $this->includeEmpty
            );
        }
        
        return $sheets;
    }
}

/**
 * Summary sheet showing all products
 */
class StockMutationSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    protected $products;
    private static $index = 1;

    public function __construct($products)
    {
        $this->products = $products;
        // Reset index for each new sheet
        self::$index = 1;
    }

    public function title(): string
    {
        return 'Ringkasan Produk';
    }

    public function collection()
    {
        // Ensure we're working with a collection and sort it by product name
        $products = collect($this->products)->sortBy(function($product) {
            return $product['product']->name ?? $product->name ?? '';
        });
        
        // If no products, add a dummy record
        if ($products->isEmpty()) {
            return collect([
                [
                    'product' => (object)['name' => 'Tidak ada produk', 'sku' => '-'],
                    'total_qty' => 0,
                    'locations' => [],
                    'has_expired' => false,
                    'earliest_expiry' => null
                ]
            ]);
        }
        
        return $products;
    }

    public function headings(): array
    {
        return [
            'No',
            'SKU',
            'Nama Produk',
            'Kategori Utama',
            'Brand',
            'Total Qty',
            'Status ED',
            'Lokasi',
        ];
    }

    public function map($product): array
    {
        try {
            // Get total quantity
            $totalQty = 0;
            if (isset($product['total_qty'])) {
                $totalQty = $product['total_qty'];
            } elseif (isset($product->warehouseStocks)) {
                $totalQty = $product->warehouseStocks->sum('qty');
            }
            
            // Get ED status
            $edStatus = 'Tanpa ED';
            if (isset($product['has_expired']) && $product['has_expired']) {
                $edStatus = 'Kadaluarsa';
            } elseif (isset($product['earliest_expiry'])) {
                $daysToExpiry = now()->diffInDays($product['earliest_expiry'], false);
                if ($daysToExpiry < 0) {
                    $edStatus = 'Kadaluarsa';
                } elseif ($daysToExpiry < 90) {
                    $edStatus = '< 3 Bulan';
                } elseif ($daysToExpiry < 180) {
                    $edStatus = '< 6 Bulan';
                } elseif ($daysToExpiry < 365) {
                    $edStatus = '< 1 Tahun';
                } else {
                    $edStatus = 'Aman';
                }
            }
            
            // Get locations
            $locations = '';
            if (isset($product['locations']) && count($product['locations']) > 0) {
                // Convert to array if it's a Collection, or use Collection's map method
                if ($product['locations'] instanceof \Illuminate\Support\Collection) {
                    $locationNames = $product['locations']->map(function($loc) {
                        return $loc['lokasi']->nama ?? 'N/A';
                    })->toArray();
                } else {
                    $locationNames = array_map(function($loc) {
                        return $loc['lokasi']->nama ?? 'N/A';
                    }, $product['locations']);
                }
                $locations = implode(', ', $locationNames);
            }
            
            $productName = isset($product['product']) ? $product['product']->name : ($product->name ?? 'N/A');
            $sku = isset($product['product']) ? $product['product']->sku : ($product->sku ?? '-');
            $mainCategory = isset($product['product']) && isset($product['product']->mainCategory) ? 
                $product['product']->mainCategory->name : 
                (isset($product->mainCategory) ? $product->mainCategory->name : '-');
            $brand = isset($product['product']) && isset($product['product']->brand) ? 
                $product['product']->brand->name : 
                (isset($product->brand) ? $product->brand->name : '-');
            
            return [
                self::$index++,
                $sku,
                $productName,
                $mainCategory,
                $brand,
                number_format($totalQty, 2),
                $edStatus,
                $locations,
            ];
        } catch (\Exception $e) {
            // If there's any error, return a fallback row
            return [
                self::$index++,
                'Error',
                $e->getMessage(),
                '-',
                '-',
                '0.00',
                '-',
                '-',
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                // Insert 3 rows at the top for title
                $sheet->insertNewRowBefore(1, 3);
                
                // Add main title - merge cells across all columns
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', 'RINGKASAN STOK PRODUK');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Style the headers (now at row 4)
                $sheet->getStyle('A4:H4')->getFont()->setBold(true);
                $sheet->getStyle('A4:H4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4472C4');
                $sheet->getStyle('A4:H4')->getFont()->getColor()->setRGB('FFFFFF');
                
                // Add borders to the header
                $sheet->getStyle('A4:H4')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Add borders to all data cells
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A4:H'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Auto-filter for the header row
                $sheet->setAutoFilter('A4:H4');
                
                // Add zebra striping to data rows
                for ($row = 5; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':H'.$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F2F2F2');
                    }
                }
                
                // Auto-size all columns
                foreach (range('A', 'H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}

/**
 * Individual product sheet with mutations
 */
class StockMutationProductSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    protected $product;
    protected $mutations;
    protected $startDate;
    protected $endDate;
    protected $includeEmpty;
    private static $index = 1;

    public function __construct($product, $startDate = null, $endDate = null, $includeEmpty = false)
    {
        $this->product = $product;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->includeEmpty = $includeEmpty;
        
        // Load mutations for this product
        $this->loadMutations();
        
        // Reset index for each sheet
        self::$index = 1;
    }

    public function title(): string
    {
        $productName = $this->product['product']->name ?? $this->product->name ?? 'Produk';
        // Ensure sheet name is valid (max 31 chars, no special chars)
        return substr(preg_replace('/[\[\]\*\/\\\?:]/i', '', $productName), 0, 31);
    }

    private function loadMutations()
    {
        $productId = $this->product['product']->id ?? $this->product->id ?? null;
        
        if (!$productId) {
            $this->mutations = collect();
            return;
        }
        
        try {
            // Get stock in data with better error handling
            $stockInQuery = WarehouseStock::with([
                'lokasi', 
                'tax',
                'penerimaanDetail.penerimaan',
                'penerimaanDetail.satuan',
                'returPenjualan.order.platform',
                'returOfflineSale.offlineSale'
            ])
            ->where('product_id', $productId);
            
            // Apply date filters if provided - but be more flexible about date sources
            if ($this->startDate) {
                $stockInQuery->where(function($q) {
                    $q->whereHas('penerimaanDetail.penerimaan', function($subQ) {
                        $subQ->where('tanggal_penerimaan', '>=', $this->startDate);
                    })
                    ->orWhere(function($subQ) {
                        $subQ->whereNotNull('source_date')
                             ->where('source_date', '>=', $this->startDate);
                    })
                    ->orWhere(function($subQ) {
                        $subQ->whereNull('source_date')
                             ->whereDoesntHave('penerimaanDetail.penerimaan')
                             ->where('created_at', '>=', $this->startDate);
                    });
                });
            }
            
            if ($this->endDate) {
                $stockInQuery->where(function($q) {
                    $q->whereHas('penerimaanDetail.penerimaan', function($subQ) {
                        $subQ->where('tanggal_penerimaan', '<=', $this->endDate);
                    })
                    ->orWhere(function($subQ) {
                        $subQ->whereNotNull('source_date')
                             ->where('source_date', '<=', $this->endDate);
                    })
                    ->orWhere(function($subQ) {
                        $subQ->whereNull('source_date')
                             ->whereDoesntHave('penerimaanDetail.penerimaan')
                             ->where('created_at', '<=', $this->endDate);
                    });
                });
            }
            
            $stockIn = $stockInQuery->get();
            
            // The relationships are already loaded through the with() clause above
            // No need to manually load them again
            
            // Get stock out data with better error handling
            $stockOutQuery = BarangKeluar::with([
                'warehouseStock.lokasi',
                'warehouseStock.penerimaanDetail.satuan',
                'orderItem.order',
                'offlineSaleItem.offlineSale.customerInfo'
            ])
            ->whereHas('warehouseStock', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
            
            // Apply date filters if provided
            if ($this->startDate) {
                $stockOutQuery->where('tanggal_keluar', '>=', $this->startDate);
            }
            
            if ($this->endDate) {
                $stockOutQuery->where('tanggal_keluar', '<=', $this->endDate);
            }
            
            $stockOut = $stockOutQuery->get();
            
            // Combine into mutations array
            $mutations = [];
            
            // Process stock in with better error handling
            foreach ($stockIn as $item) {
                try {
                    $penerimaan = $item->penerimaanDetail ? $item->penerimaanDetail->penerimaan : null;
                    
                    // Use source_date if available, otherwise fall back to penerimaan date only
                    $date = Carbon::now();
                    
                    if ($item->source_date) {
                        $date = Carbon::parse($item->source_date);
                    } elseif ($penerimaan && $penerimaan->tanggal_penerimaan) {
                        $date = Carbon::parse($penerimaan->tanggal_penerimaan);
                    }
                    
                    // Set reference and notes based on source type
                    $reference = 'N/A';
                    $notes = 'Penerimaan Barang';
                    
                    if ($item->source_type === 'retur_penjualan') {
                        $reference = $item->returPenjualan->kode_retur ?? 'N/A';
                        // Get order number and platform for notes (keterangan) with "RETUR ONLINE PLATFORM - " prefix
                        $orderNumber = $item->returPenjualan->order->order_number ?? 'N/A';
                        $platformName = $item->returPenjualan->order->platform->name ?? $item->returPenjualan->order->platform ?? 'ONLINE';
                        $notes = $orderNumber !== 'N/A' ? "RETUR ONLINE {$platformName} - {$orderNumber}" : 'Retur Penjualan Online';
                    } elseif ($item->source_type === 'retur_offline') {
                        $reference = $item->returOfflineSale->kode_retur ?? 'N/A';
                        // Get surat jalan number as "invoice" for notes (keterangan) with "RETUR OFFLINE - " prefix
                        $sjNumber = $item->returOfflineSale->offlineSale->surat_jalan_number ?? 'N/A';
                        $notes = $sjNumber !== 'N/A' ? "RETUR OFFLINE - {$sjNumber}" : 'Retur Penjualan Offline';
                    } else {
                        $reference = $penerimaan && $penerimaan->nomor_po ? $penerimaan->nomor_po : ($item->id ? 'PO-'.$item->id : 'N/A');
                        $notes = 'Penerimaan Barang' . ($penerimaan && $penerimaan->nomor_po ? ' - PO: '.$penerimaan->nomor_po : '');
                    }
                    
                    // Set timestamp with priority for proper chronological sorting
                    if ($item->source_type === 'retur_penjualan' || $item->source_type === 'retur_offline') {
                        // Returns should come at the end of the day
                        $timestamp = $date->copy()->addHours(23); // Add 23 hours to ensure returns come at end of day
                        $sortPriority = 3; // Returns get priority 3
                    } else {
                        // Initial stock/penerimaan
                        $timestamp = $date->copy()->startOfDay()->addMinute(); // Start of day + 1 minute for initial stock
                        $sortPriority = 1; // Initial stock gets priority 1
                    }
                    
                    $mutations[] = [
                        'date' => $date,
                        'timestamp' => $timestamp,
                        'type' => 'in',
                        'reference' => $reference,
                        'expired_date' => $item->expired_date ? Carbon::parse($item->expired_date) : null,
                        'location' => 'Gudang A', // Always use Gudang A instead of Unlocated
                        'qty' => (float)($item->qty ?? 0),
                        'unit' => $item->penerimaanDetail && $item->penerimaanDetail->satuan && $item->penerimaanDetail->satuan->name ? 
                            $item->penerimaanDetail->satuan->name : 'Pcs',
                        'notes' => $notes,
                        'original' => $item,
                        'sortPriority' => $sortPriority,
                    ];
                } catch (\Exception $e) {
                    // Add fallback entry for problematic items
                    \Log::error('Error processing stock in item ID '.$item->id.': ' . $e->getMessage());
                    $mutations[] = [
                        'date' => $item->created_at ? Carbon::parse($item->created_at) : Carbon::now(),
                        'timestamp' => $item->created_at ?? Carbon::now(),
                        'type' => 'in',
                        'reference' => 'WS-'.$item->id,
                        'expired_date' => null,
                        'location' => 'N/A',
                        'qty' => (float)($item->qty ?? 0),
                        'unit' => 'Pcs',
                        'notes' => 'Penerimaan Barang (data tidak lengkap)',
                        'original' => $item,
                    ];
                }
            }
            
            // Process stock out with better error handling
            foreach ($stockOut as $item) {
                try {
                    $date = $item->tanggal_keluar ? Carbon::parse($item->tanggal_keluar) : Carbon::now();
                    $timestamp = $date;
                    
                    // Get destination info with better error handling
                    $destination = '-';
                    $notes = 'Barang Keluar';
                    
                    if ($item->orderItem && $item->orderItem->order) {
                        $order = $item->orderItem->order;
                        $platformName = $order->platform_name ?? 'Online';
                        $orderNumber = $order->order_number ?? $order->id ?? 'N/A';
                        $destination = "Online ({$platformName})";
                        $notes = $orderNumber; // Just order number for consistency with web view
                    } elseif ($item->offlineSaleItem && $item->offlineSaleItem->offlineSale) {
                        $sale = $item->offlineSaleItem->offlineSale;
                        $customerName = 'N/A';
                        
                        // Try multiple ways to get customer name
                        if ($sale->customerInfo && $sale->customerInfo->name) {
                            $customerName = $sale->customerInfo->name;
                        } elseif ($sale->customer_info && $sale->customer_info->name) {
                            $customerName = $sale->customer_info->name;
                        } elseif ($sale->customer_name) {
                            $customerName = $sale->customer_name;
                        }
                        
                        $invoiceNumber = $sale->surat_jalan_number ?? $sale->No_PO ?? $sale->id ?? 'N/A';
                        $destination = "Offline ({$customerName})";
                        $notes = $invoiceNumber; // Just invoice number for consistency with web view
                    } elseif ($item->catatan && trim($item->catatan) !== '') {
                        $notes = "Barang Keluar - {$item->catatan}";
                    }
                    
                    // Add original notes if available and not already included
                    if ($item->catatan && trim($item->catatan) !== '' && !str_contains($notes, $item->catatan)) {
                        $notes .= " - {$item->catatan}";
                    }
                    
                    $mutations[] = [
                        'date' => $date,
                        'timestamp' => $timestamp,
                        'type' => 'out',
                        'reference' => $item->kode_barang_keluar ?? 'BK-'.$item->id,
                        'expired_date' => $item->warehouseStock && $item->warehouseStock->expired_date ? 
                            Carbon::parse($item->warehouseStock->expired_date) : null,
                        'location' => 'Gudang A', // Always use Gudang A instead of Unlocated
                        'qty' => (float)($item->qty ?? 0),
                        'unit' => $item->warehouseStock && $item->warehouseStock->penerimaanDetail && 
                            $item->warehouseStock->penerimaanDetail->satuan && 
                            $item->warehouseStock->penerimaanDetail->satuan->name ? 
                            $item->warehouseStock->penerimaanDetail->satuan->name : 'Pcs',
                        'notes' => $notes,
                        'destination' => $destination,
                        'original' => $item,
                        'sortPriority' => 2, // Sales get priority 2
                    ];
                } catch (\Exception $e) {
                    // Add fallback entry for problematic items
                    \Log::error('Error processing stock out item ID '.$item->id.': ' . $e->getMessage());
                    $mutations[] = [
                        'date' => $item->created_at ? Carbon::parse($item->created_at) : Carbon::now(),
                        'timestamp' => $item->created_at ?? Carbon::now(),
                        'type' => 'out',
                        'reference' => 'BK-'.$item->id,
                        'expired_date' => null,
                        'location' => 'N/A',
                        'qty' => (float)($item->qty ?? 0),
                        'unit' => 'Pcs',
                        'notes' => 'Barang Keluar (data tidak lengkap)',
                        'destination' => '-',
                        'original' => $item,
                    ];
                }
            }
            
            // Sort by chronological order with priority handling (like frontend)
            usort($mutations, function($a, $b) {
                try {
                    // First sort by date (oldest first)
                    $dateA = $a['timestamp']->timestamp ?? $a['date']->timestamp ?? 0;
                    $dateB = $b['timestamp']->timestamp ?? $b['date']->timestamp ?? 0;
                    
                    if ($dateA !== $dateB) {
                        return $dateA - $dateB; // Oldest first for chronological order
                    }
                    
                    // If same timestamp, sort by priority (initial stock first, then sales, then returns)
                    $priorityA = $a['sortPriority'] ?? 1;
                    $priorityB = $b['sortPriority'] ?? 1;
                    
                    if ($priorityA !== $priorityB) {
                        return $priorityA - $priorityB;
                    }
                    
                    // If same priority, sort by type (in before out for same timestamp)
                    if ($a['type'] !== $b['type']) {
                        return $a['type'] === 'in' ? -1 : 1;
                    }
                    
                    return 0; // Same order if everything else is equal
                } catch (\Exception $e) {
                    // If comparison fails, maintain original order
                    return 0;
                }
            });
            
            $this->mutations = collect($mutations);
        } catch (\Exception $e) {
            // If there's any error in the overall process, log it but still provide a basic structure
            \Log::error('Error loading mutations for product '.$productId.': ' . $e->getMessage());
            $this->mutations = collect([
                [
                    'date' => Carbon::now(),
                    'timestamp' => Carbon::now(),
                    'type' => 'info',
                    'reference' => 'ERROR',
                    'expired_date' => null,
                    'location' => '',
                    'qty' => 0,
                    'unit' => 'N/A',
                    'notes' => 'Error memuat data mutasi: ' . $e->getMessage(),
                ]
            ]);
        }
    }

    public function collection()
    {
        try {
            if ($this->mutations->isEmpty() && !$this->includeEmpty) {
                // Add a dummy record so the sheet isn't completely empty
                return collect([
                    [
                        'date' => now(),
                        'type' => 'info',
                        'reference' => '',
                        'expired_date' => null,
                        'location' => '',
                        'qty' => 0,
                        'unit' => 'N/A',
                        'balance' => 0,
                        'notes' => 'Tidak ada data mutasi untuk produk ini',
                    ]
                ]);
            }
            
            // Get the current real balance from the product
            $productId = $this->product['product']->id ?? $this->product->id ?? null;
            $currentBalance = 0;
            
            if ($productId) {
                try {
                    // Get current stock total from warehouse_stock table
                    $currentBalance = \App\Models\WarehouseStock::where('product_id', $productId)
                        ->where('is_damaged', false)
                        ->sum('qty') ?? 0;
                } catch (\Exception $e) {
                    \Log::error('Error getting current balance for product '.$productId.': ' . $e->getMessage());
                    $currentBalance = 0;
                }
            }
            
            // Clone the mutations collection to preserve the original chronological order
            $mutationsCopy = collect($this->mutations);
            
            // Calculate running balances in chronological order (oldest first) - SAME AS MODAL
            $runningBalance = 0;
            $chronologicalWithBalance = [];
            
            // Calculate balance for each row from oldest to newest (display order) - SAME AS MODAL
            foreach ($mutationsCopy as $index => $item) {
                // Add to balance for stock in, subtract for stock out - SAME AS MODAL
                if ($item['type'] === 'in') {
                    $runningBalance += (float)($item['qty'] ?? 0);
                } else {
                    $runningBalance -= (float)($item['qty'] ?? 0);
                }
                
                // Set the balance for this item - SAME AS MODAL
                $item['balance'] = $runningBalance;
                $chronologicalWithBalance[] = $item;
            }
            
            // For Excel display, show oldest first (chronological order)
            // with correct running balances calculated chronologically
            $displayMutations = $chronologicalWithBalance;
            
            return collect($displayMutations);
        } catch (\Exception $e) {
            \Log::error('Error in collection method: ' . $e->getMessage());
            // Return a fallback collection with an error message
            return collect([
                [
                    'date' => now(),
                    'type' => 'error',
                    'reference' => 'ERROR',
                    'expired_date' => null,
                    'location' => '',
                    'qty' => 0,
                    'unit' => 'N/A',
                    'balance' => 0,
                    'notes' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ]
            ]);
        }
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Tipe',
            'Kode/No. PO',
            'Expired Date',
            'Lokasi',
            'Qty',
            'Satuan',
            'Total QTY',
            'Keterangan',
        ];
    }

    public function map($item): array
    {
        try {
            $typeLabel = $item['type'] === 'in' ? 'Masuk' : ($item['type'] === 'out' ? 'Keluar' : 'Info');
            
            $date = 'N/A';
            try {
                $date = $item['date'] && method_exists($item['date'], 'format') ? $item['date']->format('d/m/Y') : 'N/A';
            } catch (\Exception $e) {
                // Keep default 'N/A'
            }
            
            $expiredDate = 'Tanpa ED';
            try {
                $expiredDate = $item['expired_date'] && method_exists($item['expired_date'], 'format') ? 
                    $item['expired_date']->format('d/m/Y') : 'Tanpa ED';
            } catch (\Exception $e) {
                // Keep default 'Tanpa ED'
            }
            
            $qty = '';
            $balance = '';
            if ($item['type'] !== 'info' && $item['type'] !== 'error') {
                $qtyValue = (float)($item['qty'] ?? 0);
                if ($item['type'] === 'in') {
                    $qty = '+' . number_format($qtyValue, 2);
                } else {
                    $qty = '-' . number_format($qtyValue, 2);
                }
                $balance = number_format((float)($item['balance'] ?? 0), 2);
            }
            
            return [
                self::$index++,
                $date,
                $typeLabel,
                $item['reference'] ?? '',
                $expiredDate,
                $item['location'] ?? '',
                $qty,
                $item['unit'] ?? 'N/A',
                $balance,
                $item['notes'] ?? '',
            ];
        } catch (\Exception $e) {
            \Log::error('Error mapping item: ' . $e->getMessage());
            return [
                self::$index++,
                'ERROR',
                'Error',
                'ERROR',
                'ERROR',
                'ERROR',
                '0.00',
                'N/A',
                '0.00',
                'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                // Insert 5 rows at the top (more space for title)
                $sheet->insertNewRowBefore(1, 5);
                
                // Set title and product info
                $productName = $this->product['product']->name ?? $this->product->name ?? 'Produk';
                $sku = $this->product['product']->sku ?? $this->product->sku ?? '-';
                
                // Add main title - merge cells A1:J1
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'LAPORAN MUTASI STOK PRODUK');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Add product info
                $sheet->mergeCells('A3:B3');
                $sheet->setCellValue('A3', 'Nama Produk:');
                $sheet->mergeCells('C3:J3');
                $sheet->setCellValue('C3', $productName);
                
                $sheet->mergeCells('A4:B4');
                $sheet->setCellValue('A4', 'SKU:');
                $sheet->mergeCells('C4:J4');
                $sheet->setCellValue('C4', $sku);
                
                // Style product info
                $sheet->getStyle('A3:A4')->getFont()->setBold(true);
                $sheet->getStyle('A3:J4')->getFont()->setSize(12);
                
                // Style the headers (now at row 6)
                $sheet->getStyle('A6:J6')->getFont()->setBold(true);
                $sheet->getStyle('A6:J6')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4472C4');
                $sheet->getStyle('A6:J6')->getFont()->getColor()->setRGB('FFFFFF');
                
                // Add borders to the header
                $sheet->getStyle('A6:J6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Add borders to all data cells
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A6:J'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                // Auto-filter for the header row
                $sheet->setAutoFilter('A6:J6');
                
                // Style the data rows
                for ($row = 7; $row <= $lastRow; $row++) {
                    // Get the mutation type from column C
                    $type = $sheet->getCell('C' . $row)->getValue();
                    
                    // Add zebra striping
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':J'.$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F2F2F2');
                    }
                    
                    // Style based on type
                    if ($type === 'Masuk') {
                        // Green text for incoming
                        $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('28a745');
                        // Add + sign for incoming quantities
                        $qty = $sheet->getCell('G' . $row)->getValue();
                        if (!empty($qty)) {
                            $sheet->setCellValue('G' . $row, '+' . $qty);
                        }
                    } elseif ($type === 'Keluar') {
                        // Red text for outgoing
                        $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB('dc3545');
                    }
                }
                
                // Auto-size all columns
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
