<?php

namespace App\Http\Controllers;

use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Product;
use App\Models\ReturOfflineSale;
use App\Models\ReturOfflineSaleDetail;
use App\Models\WarehouseStock;
use App\Models\Lokasi;
use App\Models\PenerimaanDetail;
use App\Services\ReturFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReturOfflineSaleController extends Controller
{
    /**
     * Display a listing of the retur offline sales.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $returOfflineSales = ReturOfflineSale::with(['offlineSale', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('retur.offline.index', compact('returOfflineSales'));
    }

    /**
     * Show the form for creating a new retur offline sale.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Display a list of offline sales to choose from with eager loaded relations
        $offlineSaleList = OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['customerInfo'])
            ->whereHas('items')
            ->orderBy('sale_date', 'desc')
            ->get();

        return view('retur.offline.create', compact('offlineSaleList'));
    }

    /**
     * Get a specific offline sale with its details for the retur form.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getOfflineSale($id)
    {
        $offlineSale = OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items.product', 'customerInfo'])
            ->findOrFail($id);

        return response()->json($offlineSale);
    }

    /**
     * Store a newly created retur offline sale in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Log::info('ReturOfflineSale store method called with data: ' . json_encode($request->all()));
        
        try {
            \Log::info('Starting validation');
            $validated = $request->validate([
                'offline_sale_id' => 'required|exists:offline_sales,id',
                'tanggal_retur' => 'required|date',
                'catatan' => 'nullable|string',
                'details' => 'required|array',
                'details.*.offline_sale_item_id' => 'required|exists:offline_sale_items,id',
                'details.*.product_id' => 'required|exists:products,id',
                'details.*.qty' => 'required|numeric|min:0',
                'details.*.kondisi' => 'required|string|in:BAGUS,RUSAK,HILANG',
                'details.*.alasan' => 'nullable|string',
            ]);
            \Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed: ' . json_encode($e->errors()));
            throw $e;
        }

        try {
            DB::beginTransaction();
            \Log::info('Starting transaction for retur offline sale');

            // Create the retur offline sale header
            $returOfflineSale = ReturOfflineSale::create([
                'kode_retur' => ReturOfflineSale::generateKodeRetur(),
                'offline_sale_id' => $request->offline_sale_id,
                'user_id' => Auth::id(),
                'tanggal_retur' => $request->tanggal_retur,
                'catatan' => $request->catatan,
                'status' => 'draft',
            ]);
            
            \Log::info('Created retur offline sale header with ID: ' . $returOfflineSale->id);

            // Process each detail item
            foreach ($request->details as $index => $detail) {
                \Log::info('Processing detail item with index: ' . $index . ': ' . json_encode($detail));
                
                // Skip if qty is zero or null
                if (empty($detail['qty']) || floatval($detail['qty']) <= 0) {
                    \Log::info('Skipping item with zero or negative qty');
                    continue;
                }

                // Get the offline sale item
                $offlineSaleItem = OfflineSaleItem::findOrFail($detail['offline_sale_item_id']);
                \Log::info('Found offline sale item: ' . $offlineSaleItem->id . ' with quantity: ' . $offlineSaleItem->quantity);
                
                // Validate return quantity doesn't exceed original quantity
                if (floatval($detail['qty']) > $offlineSaleItem->quantity) {
                    \Log::warning('Return quantity exceeds original quantity for item ID ' . $offlineSaleItem->id);
                    throw new \Exception("Jumlah retur untuk item ID {$offlineSaleItem->id} melebihi jumlah asli");
                }

                // Create detail record
                ReturOfflineSaleDetail::create([
                    'retur_offline_sale_id' => $returOfflineSale->id,
                    'offline_sale_item_id' => $detail['offline_sale_item_id'],
                    'product_id' => $detail['product_id'],
                    'qty' => floatval($detail['qty']),
                    'kondisi' => $detail['kondisi'],
                    'alasan' => $detail['alasan'] ?? null,
                ]);
                
                \Log::info('Created detail record for product ID: ' . $detail['product_id']);
            }

            DB::commit();
            \Log::info('Transaction committed successfully');

            return redirect()->route('retur-offline.show', $returOfflineSale->id)
                ->with('success', 'Retur penjualan offline berhasil dibuat dan menunggu proses.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in ReturOfflineSale store: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified retur offline sale.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $returOfflineSale = ReturOfflineSale::with(['offlineSale', 'user', 'details.product', 'details.offlineSaleItem'])
            ->findOrFail($id);

        return view('retur.offline.show', compact('returOfflineSale'));
    }

    /**
     * Show the form for editing the specified retur offline sale.
     * Only allowed if status is 'draft'
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $returOfflineSale = ReturOfflineSale::with(['offlineSale', 'details.product'])
            ->findOrFail($id);

        // Only draft status can be edited
        if ($returOfflineSale->status !== 'draft') {
            return redirect()->route('retur-offline.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat diedit.');
        }

        $offlineSaleList = OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['customerInfo'])
            ->whereHas('items')
            ->get();

        return view('retur.offline.edit', compact('returOfflineSale', 'offlineSaleList'));
    }

    /**
     * Update the specified retur offline sale in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $returOfflineSale = ReturOfflineSale::findOrFail($id);

        // Only draft status can be updated
        if ($returOfflineSale->status !== 'draft') {
            return redirect()->route('retur-offline.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat diupdate.');
        }

        $request->validate([
            'offline_sale_id' => 'required|exists:offline_sales,id',
            'tanggal_retur' => 'required|date',
            'catatan' => 'nullable|string',
            'details' => 'required|array',
            'details.*.offline_sale_item_id' => 'required|exists:offline_sale_items,id',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.qty' => 'required|numeric|min:0',
            'details.*.kondisi' => 'required|string|in:BAGUS,RUSAK,HILANG',
            'details.*.alasan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update the retur offline sale
            $returOfflineSale->update([
                'offline_sale_id' => $request->offline_sale_id,
                'tanggal_retur' => $request->tanggal_retur,
                'catatan' => $request->catatan,
            ]);

            // Delete existing details
            $returOfflineSale->details()->delete();

            // Create new details
            foreach ($request->details as $detail) {
                // Skip if qty is zero or null
                if (empty($detail['qty']) || floatval($detail['qty']) <= 0) {
                    continue;
                }

                ReturOfflineSaleDetail::create([
                    'retur_offline_sale_id' => $returOfflineSale->id,
                    'offline_sale_item_id' => $detail['offline_sale_item_id'],
                    'product_id' => $detail['product_id'],
                    'qty' => floatval($detail['qty']),
                    'kondisi' => $detail['kondisi'],
                    'alasan' => $detail['alasan'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('retur-offline.show', $returOfflineSale->id)
                ->with('success', 'Retur penjualan offline berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Process a draft retur offline sale, apply stock changes.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function process($id)
    {
        $returOfflineSale = ReturOfflineSale::with('details')->findOrFail($id);

        // Only draft status can be processed
        if ($returOfflineSale->status !== 'draft') {
            return redirect()->route('retur-offline.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat diproses.');
        }

        try {
            DB::beginTransaction();
            \Log::info('Processing retur offline sale ID: ' . $id);

            // Process each detail item
            foreach ($returOfflineSale->details as $detail) {
                // Get the offline sale item
                $offlineSaleItem = OfflineSaleItem::findOrFail($detail->offline_sale_item_id);
                \Log::info('Processing offline sale item ID: ' . $offlineSaleItem->id);
                
                // Validate return quantity doesn't exceed original quantity
                if (floatval($detail->qty) > $offlineSaleItem->quantity) {
                    throw new \Exception("Jumlah retur untuk item ID {$offlineSaleItem->id} melebihi jumlah asli");
                }
                
                // Calculate new quantity
                $newQuantity = $offlineSaleItem->quantity - floatval($detail->qty);
                
                // Recalculate subtotal based on new quantity
                $newSubtotal = $this->recalculateSubtotal($offlineSaleItem, $newQuantity);
                
                // Update the offline sale item quantity and subtotal
                $offlineSaleItem->update([
                    'quantity' => $newQuantity,
                    'subtotal' => $newSubtotal
                ]);
                
                // Handle stock based on condition
                if ($detail->kondisi === 'BAGUS') {
                    $this->addBackToStock($detail->product_id, floatval($detail->qty), false, $returOfflineSale->tanggal_retur->format('Y-m-d'), $returOfflineSale->id);
                } else if ($detail->kondisi === 'RUSAK') {
                    $this->addBackToStock($detail->product_id, floatval($detail->qty), true, $returOfflineSale->tanggal_retur->format('Y-m-d'), $returOfflineSale->id);
                } else {
                    \Log::info('Product marked as HILANG, not adding to stock: product ID ' . $detail->product_id);
                }
            }

            // Update status to 'selesai'
            $returOfflineSale->update([
                'status' => 'selesai',
                'user_id' => Auth::id(),
            ]);

            // Handle finance logic for offline return
            $financeService = new ReturFinanceService();
            $financeService->handleOfflineReturFinance($returOfflineSale);

            DB::commit();

            return redirect()->route('retur-offline.show', $returOfflineSale->id)
                ->with('success', 'Retur penjualan offline berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a draft retur offline sale.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $returOfflineSale = ReturOfflineSale::findOrFail($id);

        // Only draft status can be cancelled
        if ($returOfflineSale->status !== 'draft') {
            return redirect()->route('retur-offline.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat dibatalkan.');
        }

        try {
            // Update status to 'dibatalkan'
            $returOfflineSale->update([
                'status' => 'dibatalkan',
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('retur-offline.show', $returOfflineSale->id)
                ->with('success', 'Retur penjualan offline berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Reverse/Cancel a completed retur offline sale (batal retur)
     * This will fully restore the state as if the return never happened
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reverseReturn($id)
    {
        $returOfflineSale = ReturOfflineSale::with('details')->findOrFail($id);

        // Only completed returns can be reversed
        if ($returOfflineSale->status !== 'selesai') {
            return redirect()->route('retur-offline.show', $id)
                ->with('error', 'Hanya retur dengan status selesai yang dapat dibatalkan.');
        }

        try {
            DB::beginTransaction();
            \Log::info('Reversing completed retur offline sale ID: ' . $id);

            // Process each detail item to reverse changes
            foreach ($returOfflineSale->details as $detail) {
                // Get the offline sale item and restore its quantity
                $offlineSaleItem = OfflineSaleItem::findOrFail($detail->offline_sale_item_id);
                \Log::info('Restoring offline sale item ID: ' . $offlineSaleItem->id . ' qty from ' . $offlineSaleItem->quantity . ' to ' . ($offlineSaleItem->quantity + $detail->qty));
                
                // Restore the offline sale item quantity (add back the returned quantity)
                $offlineSaleItem->update([
                    'quantity' => $offlineSaleItem->quantity + floatval($detail->qty)
                ]);
                
                // Remove stock that was added during return (only for BAGUS and RUSAK conditions)
                if ($detail->kondisi === 'BAGUS') {
                    $this->removeReturnedStock($detail->product_id, floatval($detail->qty), false, $returOfflineSale->id);
                } else if ($detail->kondisi === 'RUSAK') {
                    $this->removeReturnedStock($detail->product_id, floatval($detail->qty), true, $returOfflineSale->id);
                }
                // For HILANG condition, no stock was added, so nothing to remove
            }

            // Update status to 'dibatalkan'
            $returOfflineSale->update([
                'status' => 'dibatalkan',
                'user_id' => Auth::id(),
            ]);

            DB::commit();
            \Log::info('Successfully reversed retur offline sale ID: ' . $id);

            return redirect()->route('retur-offline.show', $id)
                ->with('success', 'Retur penjualan offline berhasil dibatalkan. Semua perubahan telah dikembalikan ke kondisi semula.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error reversing retur offline sale: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to add items back to stock
     * 
     * @param int $productId
     * @param float $quantity
     * @param bool $isDamaged
     * @param string|null $returDate
     * @param int|null $returOfflineSaleId
     */
    private function addBackToStock($productId, $quantity, $isDamaged, $returDate = null, $returOfflineSaleId = null)
    {
        $product = Product::findOrFail($productId);
        \Log::info("Adding back to stock: product ID {$productId}, qty {$quantity}, damaged: " . ($isDamaged ? 'Yes' : 'No'));
        
        // Get the retur warehouse location
        $returLocation = Lokasi::where('kode', 'GUDANG_RETUR')->first();
        if (!$returLocation) {
            $returLocation = Lokasi::create([
                'kode' => 'GUDANG_RETUR',
                'nama' => 'Gudang Retur',
                'deskripsi' => 'Tempat penyimpanan barang hasil retur'
            ]);
            \Log::info("Created new retur location with ID: {$returLocation->id}");
        }
        
        // Find a suitable warehouse stock to reference for ED and tax_id
        // We'll look for the most recent stock with the same product and damage status
        $referenceStock = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', $isDamaged)
            ->where('qty', '>', 0)
            ->orderBy('expired_date', 'asc')  // Prioritize earliest expiry date
            ->orderBy('created_at', 'desc')   // Then most recent stock
            ->first();
        
        // If no reference stock found, try to find any stock for this product
        if (!$referenceStock) {
            $referenceStock = WarehouseStock::where('product_id', $productId)
                ->where('qty', '>', 0)
                ->orderBy('expired_date', 'asc')
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        // Set default values
        $penerimaanDetailId = null;
        $taxId = null;
        $expiredDate = null;
        $statusEd = 'aman';
        
        if ($referenceStock) {
            // Use reference stock's attributes
            $penerimaanDetailId = $referenceStock->penerimaan_detail_id;
            $taxId = $referenceStock->tax_id;
            $expiredDate = $referenceStock->expired_date;
            $statusEd = $referenceStock->status_ed;
            
            \Log::info("Using reference stock ID: {$referenceStock->id} for ED and tax_id", [
                'penerimaan_detail_id' => $penerimaanDetailId,
                'tax_id' => $taxId,
                'expired_date' => $expiredDate,
                'status_ed' => $statusEd
            ]);
        } else {
            // Fallback: find any penerimaan_detail for this product
            $penerimaanDetail = PenerimaanDetail::where('penerimaan_detail.product_id', $productId)
                ->orderBy('penerimaan_detail.id', 'desc')
                ->first();
                
            if ($penerimaanDetail) {
                $penerimaanDetailId = $penerimaanDetail->id;
                \Log::info("Found penerimaan_detail_id: {$penerimaanDetailId} for product");
            } else {
                \Log::warning("No penerimaan_detail found for product ID: {$productId}");
            }
        }
        
        // Get existing warehouse stock or create new one
        $warehouseStock = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', $isDamaged)
            ->where('lokasi_id', $returLocation->id)
            ->first();
        
        if (!$warehouseStock) {
            // Create a new warehouse stock record for the condition (damaged/good)
            $warehouseStock = WarehouseStock::create([
                'product_id' => $productId,
                'lokasi_id' => $returLocation->id, // Use retur location
                'penerimaan_detail_id' => $penerimaanDetailId,
                'tax_id' => $taxId,
                'qty' => $isDamaged ? 0 : $quantity,
                'qty_damaged' => $isDamaged ? $quantity : 0,
                'expired_date' => $expiredDate,
                'status_ed' => $statusEd,
                'is_damaged' => $isDamaged,
                'source_type' => 'retur_offline',
                'source_id' => $returOfflineSaleId,
                'source_date' => $returDate ? \Carbon\Carbon::parse($returDate) : now(),
                'catatan' => 'Retur penjualan offline pada ' . ($returDate ?? now()->format('Y-m-d')),
            ]);
            
            \Log::info("Created new warehouse stock record ID: {$warehouseStock->id}", [
                'penerimaan_detail_id' => $penerimaanDetailId,
                'tax_id' => $taxId,
                'expired_date' => $expiredDate,
                'status_ed' => $statusEd,
                'is_damaged' => $isDamaged
            ]);
        } else {
            // Update existing stock based on condition
            if ($isDamaged) {
                // For damaged items, update qty_damaged
                $warehouseStock->increment('qty_damaged', $quantity);
                \Log::info("Updated damaged stock, new qty_damaged: {$warehouseStock->qty_damaged}");
            } else {
                // For good items, update regular qty
                $warehouseStock->increment('qty', $quantity);
                \Log::info("Updated good stock, new qty: {$warehouseStock->qty}");
            }
        }
    }
    
    /**
     * Print the retur offline sale document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        $returOfflineSale = ReturOfflineSale::with([
            'offlineSale',
            'offlineSale.customerInfo',
            'user',
            'details.product',
            'details.offlineSaleItem'
        ])->findOrFail($id);

        // Only allow printing of completed returns
        if ($returOfflineSale->status !== 'selesai') {
            return redirect()->route('retur-offline.show', $id)
                ->with('error', 'Hanya retur dengan status selesai yang dapat dicetak.');
        }

        return view('retur.offline.print', compact('returOfflineSale'));
    }

    /**
     * Export retur offline sales to Excel
     */
    public function export()
    {
        $filename = 'retur_offline_detail_' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new \App\Exports\ReturOfflineDetailExport(), $filename);
    }

    /**
     * Remove stock that was added during return processing
     * 
     * @param  int  $productId
     * @param  float  $qty
     * @param  bool  $isDamaged
     * @param  int  $returOfflineSaleId
     * @return void
     */
    private function removeReturnedStock($productId, $qty, $isDamaged, $returOfflineSaleId)
    {
        \Log::info("removeReturnedStock called for product ID: {$productId}, qty: {$qty}, isDamaged: " . ($isDamaged ? 'yes' : 'no'));
        
        try {
            // Find warehouse stock entries that were created by this return
            $returnedStocks = WarehouseStock::where('product_id', $productId)
                ->where('is_damaged', $isDamaged)
                ->where('source_type', 'retur_offline')
                ->where('source_id', $returOfflineSaleId)
                ->where('qty', '>', 0)
                ->orderBy('created_at', 'desc') // Most recent first
                ->get();

            if ($returnedStocks->isEmpty()) {
                // If no specific return stock found, try to find general stock to remove
                \Log::warning("No specific return stock found, looking for general stock to remove");
                $returnedStocks = WarehouseStock::where('product_id', $productId)
                    ->where('is_damaged', $isDamaged)
                    ->where('qty', '>', 0)
                    ->orderBy('created_at', 'desc') // Most recent first (likely to be return stock)
                    ->get();
            }

            if ($returnedStocks->isEmpty()) {
                throw new \Exception("Tidak dapat menemukan stok untuk dikurangi pada produk ID: {$productId}");
            }

            $remainingQty = $qty;
            
            foreach ($returnedStocks as $stock) {
                if ($remainingQty <= 0) break;

                $qtyToRemove = min($remainingQty, $stock->qty);
                
                \Log::info("Removing from stock ID: {$stock->id}, removing: {$qtyToRemove}, current: {$stock->qty}");
                
                // For offline returns, check if we're dealing with damaged/good stock columns
                if ($isDamaged && isset($stock->qty_damaged)) {
                    // Handle damaged stock in qty_damaged column
                    $qtyToRemove = min($remainingQty, $stock->qty_damaged);
                    $stock->qty_damaged -= $qtyToRemove;
                } else {
                    // Handle regular stock in qty column
                    $stock->qty -= $qtyToRemove;
                }
                
                $stock->save();
                
                // Update remaining quantity
                $remainingQty -= $qtyToRemove;
                
                \Log::info("Stock ID: {$stock->id} updated, new qty: {$stock->qty}" . ($isDamaged ? ", new qty_damaged: {$stock->qty_damaged}" : ""));
            }

            if ($remainingQty > 0) {
                \Log::warning("Could not remove all requested quantity. Remaining: {$remainingQty}");
                throw new \Exception("Tidak dapat mengurangi seluruh kuantitas yang diminta untuk produk ID: {$productId}. Sisa: {$remainingQty}");
            }

            \Log::info("Successfully removed returned stock for product ID: {$productId}");
            
        } catch (\Exception $e) {
            \Log::error("Error in removeReturnedStock: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            throw $e; // Re-throw to be caught by the calling method
        }
    }

    /**
     * Recalculate subtotal for an offline sale item based on new quantity
     * 
     * @param OfflineSaleItem $offlineSaleItem
     * @param float $newQuantity
     * @return float
     */
    private function recalculateSubtotal($offlineSaleItem, $newQuantity)
    {
        // Get base price
        $basePrice = $offlineSaleItem->unit_price;
        
        // Calculate total before discounts
        $totalBeforeDiscount = $basePrice * $newQuantity;
        $currentTotal = $totalBeforeDiscount;
        
        // Apply all percentage discounts (1-5) in sequence
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = $offlineSaleItem->$percentField ?? 0;
            if($discountPercent > 0) {
                $discountAmount = $currentTotal * ($discountPercent / 100);
                $currentTotal -= $discountAmount;
                // Apply cascading rounding after each discount
                $currentTotal = \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
            }
        }
        
        // Apply all nominal discounts (1-5) in sequence
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = $offlineSaleItem->$amountField ?? 0;
            if($discountAmount > 0) {
                $currentTotal -= ($discountAmount * $newQuantity);
                // Apply cascading rounding after each discount
                $currentTotal = \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
            }
        }
        
        return \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
    }
} 