<?php

namespace App\Http\Controllers;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianDetail;
use App\Models\Satuan;
use App\Models\WarehouseStock;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReturPembelianController extends Controller
{
    /**
     * Display a listing of the retur pembelian.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $returPembelians = ReturPembelian::with(['penerimaan', 'user', 'details.penerimaanDetail'])
            ->orderBy('retur_pembelians.created_at', 'desc')
            ->paginate(10);

        return view('retur.pembelian.index', compact('returPembelians'));
    }

    /**
     * Show the form for creating a new retur pembelian.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Display a list of penerimaan (PO) to choose from with eager loaded relations
        $penerimaanList = Penerimaan::with(['mainCategory', 'lokasi'])
            ->where('status', 'Located')
            ->orderBy('tanggal_penerimaan', 'desc')
            ->get();

        return view('retur.pembelian.create', compact('penerimaanList'));
    }

    /**
     * Get a specific penerimaan with its details for the retur form.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getPenerimaan($id)
    {
        $penerimaan = Penerimaan::with([
            'details.product', 
            'details.satuan',
            'mainCategory',
            'taxCategory',
            'lokasi'
        ])->where('penerimaan.id', $id)->firstOrFail();
        
        // Get available stock quantities for each product from warehouse_stock
        foreach ($penerimaan->details as $detail) {
            // Get all warehouse stock records for this penerimaan detail with qty > 0
            $stocks = WarehouseStock::where('warehouse_stock.penerimaan_detail_id', $detail->id)
                ->where('warehouse_stock.qty', '>', 0)
                ->orderBy('warehouse_stock.created_at', 'asc')
                ->get();
                
            // Attach the stocks to the detail
            $detail->warehouse_stocks = $stocks;
            // Also calculate total available stock for backward compatibility
            $detail->available_stock = $stocks->sum('qty');
        }

        return response()->json($penerimaan);
    }

    /**
     * Store a newly created retur pembelian in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Log::info('ReturPembelianController@store - Start', ['request' => $request->all()]);
        
        // Validasi field-field utama terlebih dahulu
        $baseValidator = \Validator::make($request->all(), [
            'penerimaan_id' => 'required|exists:penerimaan,id',
            'tanggal_retur' => 'required|date',
            'catatan' => 'nullable|string',
            'details' => 'required|array',
        ]);
        
        if ($baseValidator->fails()) {
            \Log::error('ReturPembelianController@store - Base validation failed', ['errors' => $baseValidator->errors()->toArray()]);
            return back()->withErrors($baseValidator)->withInput();
        }
        
        // Filter hanya detail dengan qty > 0
        $validDetails = [];
        $anyValidItem = false;
        
        if (isset($request->details) && is_array($request->details)) {
            foreach ($request->details as $index => $detail) {
                // Hanya proses item dengan qty > 0
                if (isset($detail['qty']) && floatval($detail['qty']) > 0) {
                    $validDetails[$index] = $detail;
                    $anyValidItem = true;
                }
            }
        }
        
        // Jika tidak ada item valid sama sekali
        if (!$anyValidItem) {
            \Log::error('ReturPembelianController@store - No valid items with qty > 0');
            return back()->with('error', 'Anda harus memasukkan jumlah retur minimal 1 barang.')->withInput();
        }
        
        // Validasi detail-detail yang valid saja
        $detailRules = [];
        
        foreach ($validDetails as $index => $detail) {
            $detailRules["details.{$index}.penerimaan_detail_id"] = 'required|exists:penerimaan_detail,id';
            $detailRules["details.{$index}.product_id"] = 'required|exists:products,id';
            $detailRules["details.{$index}.qty"] = 'required|numeric|min:0.01';
            $detailRules["details.{$index}.satuan_id"] = 'required|exists:satuans,id';
            $detailRules["details.{$index}.alasan"] = 'required|string';
            $detailRules["details.{$index}.warehouse_stock_id"] = 'required|exists:warehouse_stock,id';
        }
        
        $detailValidator = \Validator::make($request->all(), $detailRules);
        
        if ($detailValidator->fails()) {
            \Log::error('ReturPembelianController@store - Detail validation failed', ['errors' => $detailValidator->errors()->toArray()]);
            return back()->withErrors($detailValidator)->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Log data untuk debugging
            \Log::info('Creating Retur Pembelian with data:', [
                'penerimaan_id' => $request->penerimaan_id,
                'user_id' => Auth::id() ?? null,
                'tanggal_retur' => $request->tanggal_retur,
                'valid_items_count' => count($validDetails)
            ]);
            
            // Verify penerimaan exists
            $penerimaan = Penerimaan::where('penerimaan.id', $request->penerimaan_id)->first();
            if (!$penerimaan) {
                \Log::error('ReturPembelianController@store - Penerimaan not found', ['penerimaan_id' => $request->penerimaan_id]);
                throw new \Exception('Penerimaan dengan ID tersebut tidak ditemukan.');
            }
            
            // Generate kode retur
            $kodeRetur = ReturPembelian::generateKodeRetur();
            \Log::info('Generated kode retur: ' . $kodeRetur);
            
            // Get all PO products
            $poProductDetails = PenerimaanDetail::where('penerimaan_detail.penerimaan_id', $request->penerimaan_id)->get();
            $totalPoProductsCount = $poProductDetails->count();
            
            // Count total PO quantity for each product
            $poTotalQtyPerProduct = [];
            foreach ($poProductDetails as $poDetail) {
                if (!isset($poTotalQtyPerProduct[$poDetail->product_id])) {
                    $poTotalQtyPerProduct[$poDetail->product_id] = 0;
                }
                $poTotalQtyPerProduct[$poDetail->product_id] += $poDetail->qty;
            }
            
            // Count return quantities for each product
            $returQtyPerProduct = [];
            $returProductCount = 0;
            $allProductsFullyReturned = true;
            
            foreach ($validDetails as $detail) {
                $productId = $detail['product_id'];
                if (!isset($returQtyPerProduct[$productId])) {
                    $returQtyPerProduct[$productId] = 0;
                    $returProductCount++;
                }
                $returQtyPerProduct[$productId] += floatval($detail['qty']);
            }
            
            // Check if any product is not fully returned
            foreach ($poTotalQtyPerProduct as $productId => $totalQty) {
                $returQty = $returQtyPerProduct[$productId] ?? 0;
                if ($returQty < $totalQty) {
                    $allProductsFullyReturned = false;
                    break;
                }
            }
            
            // Determine retur type based on the new criteria
            $tipeRetur = ($returProductCount == $totalPoProductsCount && $allProductsFullyReturned) 
                ? ReturPembelian::TIPE_FULL 
                : ReturPembelian::TIPE_SEBAGIAN;
                
            \Log::info("Determining retur type", [
                'po_products_count' => $totalPoProductsCount,
                'returned_products_count' => $returProductCount,
                'all_fully_returned' => $allProductsFullyReturned,
                'tipe_retur' => $tipeRetur
            ]);

            // Create the retur pembelian header (directly as completed status)
            $returPembelian = ReturPembelian::create([
                'kode_retur' => $kodeRetur,
                'penerimaan_id' => $request->penerimaan_id,
                'user_id' => Auth::id() ?? null,
                'tanggal_retur' => $request->tanggal_retur,
                'catatan' => $request->catatan,
                'status' => 'selesai',
                'tipe_retur' => $tipeRetur,
            ]);
            
            \Log::info('Created Retur Pembelian header with ID: ' . $returPembelian->id);
            
            $detailsCreated = 0;
            $stockReduced = 0;

            // Process each detail item (only valid ones)
            foreach ($validDetails as $index => $detail) {
                // Verify penerimaan detail exists
                $penerimaanDetail = PenerimaanDetail::where('penerimaan_detail.id', $detail['penerimaan_detail_id'])->first();
                if (!$penerimaanDetail) {
                    \Log::error('ReturPembelianController@store - PenerimaanDetail not found', [
                        'penerimaan_detail_id' => $detail['penerimaan_detail_id']
                    ]);
                    throw new \Exception('Detail penerimaan dengan ID ' . $detail['penerimaan_detail_id'] . ' tidak ditemukan.');
                }

                // Create detail record
                $returDetail = ReturPembelianDetail::create([
                    'retur_pembelian_id' => $returPembelian->id,
                    'penerimaan_detail_id' => $detail['penerimaan_detail_id'],
                    'product_id' => $detail['product_id'],
                    'qty' => $detail['qty'],
                    'satuan_id' => $detail['satuan_id'],
                    'alasan' => $detail['alasan'] ?? null,
                ]);
                
                $detailsCreated++;
                \Log::info('Created Retur Detail for product ID: ' . $detail['product_id'] . ' with qty: ' . $detail['qty']);

                try {
                    // Reduce stock from warehouse based on specific warehouse stock ID or using FIFO principle as fallback
                    $this->reduceStock($detail['product_id'], $detail['qty'], $detail['penerimaan_detail_id'], $detail['warehouse_stock_id'] ?? null, $returPembelian->id);
                    $stockReduced++;
                    \Log::info('Reduced stock for product ID: ' . $detail['product_id']);
                } catch (\Exception $e) {
                    \Log::error('ReturPembelianController@store - Error reducing stock', [
                        'product_id' => $detail['product_id'],
                        'qty' => $detail['qty'],
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            \Log::info('Retur Pembelian creation summary', [
                'retur_id' => $returPembelian->id,
                'details_created' => $detailsCreated,
                'stock_reduced' => $stockReduced
            ]);
            
            DB::commit();
            \Log::info('Retur Pembelian creation successfully committed');

            return redirect()->route('retur-pembelian.show', $returPembelian->id)
                ->with('success', 'Retur pembelian berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating Retur Pembelian: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified retur pembelian.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $returPembelian = ReturPembelian::with(['penerimaan', 'user', 'details.product', 'details.satuan', 'details.penerimaanDetail'])
            ->where('retur_pembelians.id', $id)->firstOrFail();

        return view('retur.pembelian.show', compact('returPembelian'));
    }

    /**
     * Remove the specified retur pembelian from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $returPembelian = ReturPembelian::with('details')->where('retur_pembelians.id', $id)->firstOrFail();

        try {
            DB::beginTransaction();

            // Add stock back to warehouse for all items
            foreach ($returPembelian->details as $detail) {
                $this->restoreStock($detail->product_id, $detail->qty, $detail->penerimaan_detail_id);
                \Log::info('Restored stock', [
                    'product_id' => $detail->product_id, 
                    'qty' => $detail->qty
                ]);
            }

            // Delete details and header
            $returPembelian->details()->delete();
            $returPembelian->delete();

            DB::commit();

            return redirect()->route('retur-pembelian.index')
                ->with('success', 'Retur pembelian berhasil dihapus dan stok telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting retur pembelian', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Reduce stock from warehouse based on specific warehouse stock ID or using FIFO principle as fallback
     *
     * @param  int  $productId
     * @param  float  $qty
     * @param  int  $penerimaanDetailId
     * @param  int|null  $warehouseStockId
     * @param  int|null  $returPembelianId
     * @return void
     */
    private function reduceStock($productId, $qty, $penerimaanDetailId, $warehouseStockId = null, $returPembelianId = null)
    {
        \Log::info('Reducing stock for product', [
            'product_id' => $productId,
            'qty_to_reduce' => $qty,
            'penerimaan_detail_id' => $penerimaanDetailId,
            'warehouse_stock_id' => $warehouseStockId
        ]);
        
        // Get the product to verify it exists
        $product = Product::where('products.id', $productId)->first();
        if (!$product) {
            \Log::error('reduceStock - Product not found', ['product_id' => $productId]);
            throw new \Exception("Produk dengan ID {$productId} tidak ditemukan.");
        }
        
        // If a specific warehouse stock ID is provided, use that one
        if ($warehouseStockId) {
            $stock = WarehouseStock::where('warehouse_stock.id', $warehouseStockId)
                ->where('warehouse_stock.product_id', $productId)
                ->where('warehouse_stock.penerimaan_detail_id', $penerimaanDetailId)
                ->where('warehouse_stock.qty', '>', 0)
                ->first();
                
            if (!$stock) {
                \Log::error('reduceStock - Specific warehouse stock not found or empty', [
                    'warehouse_stock_id' => $warehouseStockId,
                    'product_id' => $productId,
                    'penerimaan_detail_id' => $penerimaanDetailId
                ]);
                throw new \Exception("Stok spesifik dengan ID {$warehouseStockId} untuk produk {$product->name} tidak ditemukan atau kosong.");
            }
            
            if ($stock->qty < $qty) {
                \Log::error('reduceStock - Insufficient stock for specific warehouse stock', [
                    'warehouse_stock_id' => $warehouseStockId,
                    'product_id' => $productId,
                    'current_qty' => $stock->qty,
                    'requested_qty' => $qty
                ]);
                throw new \Exception("Stok produk {$product->name} tidak cukup (Tersedia: {$stock->qty}, Dibutuhkan: {$qty}).");
            }
            
            // Update the stock
            $stock->qty -= $qty;
            $stock->save();
            
            // Record stock out movement for retur pembelian
            $this->recordStockOut($stock, $qty, $returPembelianId ?? null);
            
            \Log::info('Stock reduction completed for specific warehouse stock', [
                'warehouse_stock_id' => $stock->id,
                'product_id' => $productId,
                'previous_qty' => $stock->qty + $qty,
                'reduced_qty' => $qty,
                'new_qty' => $stock->qty
            ]);
            
            return;
        }
        
        // If no specific ID provided, fall back to the original FIFO method
        // Get warehouse stock connected to the penerimaan_detail_id
        $warehouseStocks = WarehouseStock::where('warehouse_stock.product_id', $productId)
            ->where('warehouse_stock.penerimaan_detail_id', $penerimaanDetailId)
            ->where('warehouse_stock.qty', '>', 0)
            ->orderBy('warehouse_stock.expired_date', 'asc')  // First prioritize based on expiry date (earliest first)
            ->orderBy('warehouse_stock.created_at', 'asc')    // Then by receipt date (FIFO)
            ->get();
            
        if ($warehouseStocks->isEmpty()) {
            \Log::error('reduceStock - No warehouse stock found for this PO and product', [
                'product_id' => $productId,
                'penerimaan_detail_id' => $penerimaanDetailId
            ]);
            throw new \Exception("Stok produk {$product->name} dari PO ini tidak tersedia di gudang.");
        }
        
        // Check total stock availability for this PO detail
        $totalAvailableStock = $warehouseStocks->sum('qty');
        
        \Log::info('Total stock available for this PO and product', [
            'product_id' => $productId,
            'product_name' => $product->name,
            'total_stock' => $totalAvailableStock
        ]);
        
        if ($totalAvailableStock < $qty) {
            \Log::error('reduceStock - Insufficient total stock', [
                'product_id' => $productId, 
                'total_stock' => $totalAvailableStock, 
                'qty_requested' => $qty
            ]);
            throw new \Exception("Stok produk {$product->name} dari PO ini tidak cukup untuk retur (Tersedia: {$totalAvailableStock}, Dibutuhkan: {$qty}).");
        }

        // Reduce stock using FIFO principle
        $remainingQty = $qty;
        
        foreach ($warehouseStocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }
            
            $qtyToTake = min($remainingQty, $stock->qty);
            
            \Log::info("Reducing from stock ID: {$stock->id}", [
                'warehouse_stock_id' => $stock->id,
                'expired_date' => $stock->expired_date,
                'current_qty' => $stock->qty,
                'qty_to_take' => $qtyToTake
            ]);
            
            // Update the stock
            $stock->qty -= $qtyToTake;
            $stock->save();
            
            // Record stock out movement for retur pembelian
            $this->recordStockOut($stock, $qtyToTake, $returPembelianId ?? null);
            
            // Update remaining qty to reduce
            $remainingQty -= $qtyToTake;
        }
        
        \Log::info('Stock reduction completed using FIFO', [
            'product_id' => $productId,
            'total_reduced' => $qty,
            'remaining_qty' => $remainingQty
        ]);
        
        if ($remainingQty > 0) {
            // This should not happen due to our previous total check
            \Log::error('reduceStock - Could not reduce all requested qty', [
                'product_id' => $productId, 
                'remaining_qty' => $remainingQty
            ]);
            throw new \Exception("Tidak dapat mengurangi seluruh kuantitas yang diminta untuk produk {$product->name}.");
        }
    }

    /**
     * Restore stock that was reduced during retur
     *
     * @param  int  $productId
     * @param  float  $qty
     * @param  int  $penerimaanDetailId
     * @return void
     */
    private function restoreStock($productId, $qty, $penerimaanDetailId)
    {
        \Log::info('Restoring stock for product', [
            'product_id' => $productId,
            'qty_to_restore' => $qty,
            'penerimaan_detail_id' => $penerimaanDetailId
        ]);
        
        // Find the oldest warehouse stock entry for this product and penerimaan_detail_id
        // Or create a new one if none exists
        $warehouseStock = WarehouseStock::where('warehouse_stock.product_id', $productId)
            ->where('warehouse_stock.penerimaan_detail_id', $penerimaanDetailId)
            ->orderBy('warehouse_stock.created_at', 'asc')
            ->first();
            
        if (!$warehouseStock) {
            // Create new warehouse stock entry
            $warehouseStock = WarehouseStock::create([
                'product_id' => $productId,
                'penerimaan_detail_id' => $penerimaanDetailId,
                'lokasi_id' => 1, // Default location, you might need to adjust this
                'qty' => $qty,
                'expired_date' => null, // You might need to handle expiry date appropriately
            ]);
            
            \Log::info('Created new warehouse stock entry for restored stock', [
                'warehouse_stock_id' => $warehouseStock->id,
                'qty' => $qty
            ]);
        } else {
            // Update existing warehouse stock
            $warehouseStock->qty += $qty;
            $warehouseStock->save();
            
            \Log::info('Updated existing warehouse stock with restored quantity', [
                'warehouse_stock_id' => $warehouseStock->id,
                'previous_qty' => $warehouseStock->qty - $qty,
                'added_qty' => $qty,
                'new_qty' => $warehouseStock->qty
            ]);
        }
        
        // Remove related barang keluar records for this retur pembelian
        $this->removeStockOutRecords($productId, $penerimaanDetailId);
    }

    /**
     * Remove stock out records related to retur pembelian
     *
     * @param  int  $productId
     * @param  int  $penerimaanDetailId
     * @return void
     */
    private function removeStockOutRecords($productId, $penerimaanDetailId)
    {
        try {
            // Find and delete barang keluar records related to retur pembelian for this product
            $barangKeluarRecords = BarangKeluar::whereHas('warehouseStock', function($query) use ($productId, $penerimaanDetailId) {
                $query->where('product_id', $productId)
                      ->where('penerimaan_detail_id', $penerimaanDetailId);
            })->where('catatan', 'like', 'Retur Pembelian%')
              ->get();
            
            foreach ($barangKeluarRecords as $record) {
                $record->delete();
                \Log::info('Removed stock out record for retur pembelian', [
                    'barang_keluar_id' => $record->id,
                    'kode_barang_keluar' => $record->kode_barang_keluar
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error removing stock out records for retur pembelian', [
                'product_id' => $productId,
                'penerimaan_detail_id' => $penerimaanDetailId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Export retur pembelian data to Excel
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $returPembelians = ReturPembelian::with([
                'penerimaan', 
                'user', 
                'details.product', 
                'details.satuan',
                'details.penerimaanDetail'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $exportData = [];
        foreach ($returPembelians as $retur) {
            foreach ($retur->details as $detail) {
                $hargaHpp = $detail->penerimaanDetail ? $detail->penerimaanDetail->harga_hpp : 0;
                $totalNominal = $hargaHpp * $detail->qty;
                
                $exportData[] = [
                    'Kode Retur' => $retur->kode_retur,
                    'Nomor PO' => $retur->penerimaan->nomor_po,
                    'Tanggal Penerimaan' => $retur->penerimaan->tanggal_penerimaan ? $retur->penerimaan->tanggal_penerimaan->format('d/m/Y') : '-',
                    'Tanggal Retur' => $retur->tanggal_retur ? $retur->tanggal_retur->format('d/m/Y') : '-',
                    'Tipe Retur' => ucfirst($retur->tipe_retur),
                    'Nama Produk' => $detail->product->name ?? '-',
                    'Harga HPP' => round($hargaHpp, 2),
                    'Qty Retur' => round($detail->qty, 2),
                    'Satuan' => $detail->satuan->name ?? '-',
                    'Total Nominal' => round($totalNominal, 2),
                    'Alasan' => $detail->alasan ?? '-',
                    'User' => $retur->user->name ?? 'N/A',
                    'Dibuat Pada' => $retur->created_at ? $retur->created_at->format('d/m/Y H:i') : '-',
                ];
            }
        }

        return Excel::download(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithColumnFormatting, \Maatwebsite\Excel\Concerns\WithCustomValueBinder {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Kode Retur',
                    'Nomor PO',
                    'Tanggal Penerimaan',
                    'Tanggal Retur',
                    'Tipe Retur',
                    'Nama Produk',
                    'Harga HPP',
                    'Qty Retur',
                    'Satuan',
                    'Total Nominal',
                    'Alasan',
                    'User',
                    'Dibuat Pada'
                ];
            }

            public function columnFormats(): array
            {
                return [
                    'G' => '#,##0.00', // Harga HPP
                    'H' => '#,##0.00', // Qty Retur
                    'J' => '#,##0.00', // Total Nominal
                ];
            }

            public function bindValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, $value)
            {
                if (is_numeric($value)) {
                    $cell->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    return true;
                }
                $cell->setValue($value);
                return true;
            }
        }, 'retur_pembelian_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    /**
     * Record stock out movement for retur pembelian
     *
     * @param  WarehouseStock  $stock
     * @param  float  $qty
     * @param  int|null  $returPembelianId
     * @return void
     */
    private function recordStockOut($stock, $qty, $returPembelianId = null)
    {
        try {
            // Generate kode barang keluar
            $kodeBarangKeluar = BarangKeluar::generateKode();
            
            // Get retur pembelian info for notes
            $returPembelian = null;
            if ($returPembelianId) {
                $returPembelian = ReturPembelian::with('penerimaan')->find($returPembelianId);
            }
            
            $catatan = 'Retur Pembelian';
            if ($returPembelian && $returPembelian->penerimaan) {
                $catatan .= " - PO: {$returPembelian->penerimaan->nomor_po}";
            }
            
            // Create barang keluar record
            BarangKeluar::create([
                'kode_barang_keluar' => $kodeBarangKeluar,
                'warehouse_stock_id' => $stock->id,
                'qty' => $qty,
                'tanggal_keluar' => now()->toDateString(),
                'catatan' => $catatan,
            ]);
            
            \Log::info('Recorded stock out for retur pembelian', [
                'kode_barang_keluar' => $kodeBarangKeluar,
                'warehouse_stock_id' => $stock->id,
                'qty' => $qty,
                'retur_pembelian_id' => $returPembelianId
            ]);
        } catch (\Exception $e) {
            \Log::error('Error recording stock out for retur pembelian', [
                'warehouse_stock_id' => $stock->id,
                'qty' => $qty,
                'retur_pembelian_id' => $returPembelianId,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception here to avoid breaking the main flow
        }
    }
} 