<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturPenjualan;
use App\Models\ReturPenjualanDetail;
use App\Models\WarehouseStock;
use App\Models\PenerimaanDetail;
use App\Services\ReturFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReturPenjualanController extends Controller
{
    /**
     * Calculate the correct price per individual product from order item
     * For paket products, this divides the package price by total package quantity
     * 
     * @param OrderItem $orderItem
     * @return float
     */
    private function calculateIndividualProductPrice($orderItem)
    {
        if (!$orderItem) {
            return 0;
        }
        
        $platformProduct = $orderItem->platformProduct;
        if (!$platformProduct || !$platformProduct->mappingBarang) {
            return $orderItem->price_after_discount;
        }
        
        // Calculate total quantity in the package
        $totalPackageQty = $platformProduct->mappingBarang->sum('quantity');
        
        // Calculate price per individual product
        return $totalPackageQty > 0 ? 
            $orderItem->price_after_discount / $totalPackageQty : 
            $orderItem->price_after_discount;
    }

    /**
     * Display a listing of the retur penjualan.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $returPenjualans = ReturPenjualan::with(['order', 'order.orderItems', 'user', 'details.product', 'details.orderItem.platformProduct.mappingBarang'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('retur.penjualan.index', compact('returPenjualans'));
    }

    /**
     * Show the form for creating a new retur penjualan.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Display a list of orders to choose from with eager loaded relations
        $orderList = Order::with(['platform'])
            ->whereHas('orderItems')
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('retur.penjualan.create', compact('orderList'));
    }

    /**
     * Get a specific order with its details for the retur form.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getOrder($id)
    {
        try {
            // Get the order with eager loading
            $order = Order::with([
                'orderItems' => function($query) {
                    $query->with([
                        'platformProduct' => function($q) {
                            $q->with(['mappingBarang' => function($mq) {
                                $mq->with('product');
                            }]);
                        },
                        'warehouseStock' => function($q) {
                            $q->with('product');
                        }
                    ]);
                },
                'platform'
            ])->findOrFail($id);
            
            // Log the order data for debugging
            \Log::info('Order for retur: Order ' . $id . ' has ' . $order->orderItems->count() . ' items');
            
            return response()->json($order);
        } catch (\Exception $e) {
            \Log::error('Error loading order for retur: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Gagal memuat data order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created retur penjualan in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Log::info('ReturPenjualan store method called with data: ' . json_encode($request->all()));
        
        try {
            \Log::info('Starting validation');
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'tanggal_retur' => 'required|date',
                'catatan' => 'nullable|string',
                'details' => 'required|array',
                'details.*.order_item_id' => 'required|exists:order_items,id',
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
            \Log::info('Starting transaction for retur penjualan');

            // Create the retur penjualan header
            $returPenjualan = ReturPenjualan::create([
                'kode_retur' => ReturPenjualan::generateKodeRetur(),
                'order_id' => $request->order_id,
                'user_id' => Auth::id() ?? null,
                'tanggal_retur' => $request->tanggal_retur,
                'catatan' => $request->catatan,
                'status' => 'selesai',
            ]);
            
            \Log::info('Created retur penjualan header with ID: ' . $returPenjualan->id);

            // Process each detail item
            foreach ($request->details as $index => $detail) {
                \Log::info('Processing detail item with index: ' . $index . ': ' . json_encode($detail));
                
                // Skip if qty is zero or null
                if (empty($detail['qty']) || floatval($detail['qty']) <= 0) {
                    \Log::info('Skipping item with zero or negative qty');
                    continue;
                }

                // Get the order item
                $orderItem = OrderItem::with(['platformProduct.mappingBarang.product'])->findOrFail($detail['order_item_id']);
                \Log::info('Found order item: ' . $orderItem->id . ' with quantity: ' . $orderItem->quantity);
                
                // Validate return quantity doesn't exceed original quantity
                if (floatval($detail['qty']) > $orderItem->quantity) {
                    \Log::warning('Return quantity exceeds original quantity for item ID ' . $orderItem->id);
                    throw new \Exception("Jumlah retur untuk item ID {$orderItem->id} melebihi jumlah asli");
                }

                // Get platform product and its mappings
                $platformProduct = $orderItem->platformProduct;
                if (!$platformProduct || !$platformProduct->mappingBarang || $platformProduct->mappingBarang->isEmpty()) {
                    throw new \Exception("Platform product tidak memiliki mapping untuk order item {$orderItem->id}");
                }

                // Update the order item quantity
                $orderItem->update([
                    'quantity' => $orderItem->quantity - floatval($detail['qty'])
                ]);
                \Log::info('Updated order item quantity to: ' . ($orderItem->quantity - floatval($detail['qty'])));

                // Expand platform product return into individual product returns
                foreach ($platformProduct->mappingBarang as $mapping) {
                    $individualQty = floatval($detail['qty']) * $mapping->quantity;
                    
                    if ($individualQty > 0) {
                        // Create detail record for each individual product
                        $returDetail = ReturPenjualanDetail::create([
                            'retur_penjualan_id' => $returPenjualan->id,
                            'order_item_id' => $detail['order_item_id'],
                            'product_id' => $mapping->product_id,
                            'qty' => $individualQty,
                            'kondisi' => $detail['kondisi'],
                            'alasan' => $detail['alasan'] ?? null,
                        ]);
                        \Log::info('Created retur detail with ID: ' . $returDetail->id . ' for product ID: ' . $mapping->product_id . ' with qty: ' . $individualQty);

                        // Handle stock based on condition
                        if ($detail['kondisi'] === 'BAGUS') {
                            \Log::info('Adding back to stock: product ID ' . $mapping->product_id . ', qty: ' . $individualQty);
                            $this->addBackToStock($mapping->product_id, $individualQty, false, $request->tanggal_retur, $returPenjualan->id);
                        } else if ($detail['kondisi'] === 'RUSAK') {
                            \Log::info('Adding to damaged stock: product ID ' . $mapping->product_id . ', qty: ' . $individualQty);
                            $this->addBackToStock($mapping->product_id, $individualQty, true, $request->tanggal_retur, $returPenjualan->id);
                        } else {
                            \Log::info('Product marked as HILANG, not adding to stock: product ID ' . $mapping->product_id);
                        }
                    }
                }
            }

            DB::commit();
            \Log::info('Transaction committed successfully');

            return redirect()->route('retur-penjualan.show', $returPenjualan->id)
                ->with('success', 'Retur penjualan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in retur penjualan store: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified retur penjualan.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $returPenjualan = ReturPenjualan::with(['order', 'user', 'details.product', 'details.orderItem.platformProduct.mappingBarang'])
            ->findOrFail($id);

        return view('retur.penjualan.show', compact('returPenjualan'));
    }

    /**
     * Show the form for editing the specified retur penjualan.
     * Only allowed if status is 'draft'
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $returPenjualan = ReturPenjualan::with(['order', 'details.product'])
            ->findOrFail($id);

        // Only draft status can be edited
        if ($returPenjualan->status !== 'draft') {
            return redirect()->route('retur-penjualan.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat diedit.');
        }

        $orderList = Order::with(['platform'])
            ->whereHas('orderItems')
            ->get();

        return view('retur.penjualan.edit', compact('returPenjualan', 'orderList'));
    }

    /**
     * Update the specified retur penjualan in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $returPenjualan = ReturPenjualan::findOrFail($id);

        // Only draft status can be updated
        if ($returPenjualan->status !== 'draft') {
            return redirect()->route('retur-penjualan.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat diupdate.');
        }

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'tanggal_retur' => 'required|date',
            'catatan' => 'nullable|string',
            'details' => 'required|array',
            'details.*.order_item_id' => 'required|exists:order_items,id',
            'details.*.qty' => 'required|numeric|min:0',
            'details.*.kondisi' => 'required|string|in:BAGUS,RUSAK,HILANG',
            'details.*.alasan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update the retur penjualan
            $returPenjualan->update([
                'order_id' => $request->order_id,
                'tanggal_retur' => $request->tanggal_retur,
                'catatan' => $request->catatan,
            ]);

            // Delete existing details
            $returPenjualan->details()->delete();

            // Create new details
            foreach ($request->details as $detail) {
                // Skip if qty is zero or null
                if (empty($detail['qty']) || floatval($detail['qty']) <= 0) {
                    continue;
                }

                // Get the order item
                $orderItem = OrderItem::with(['platformProduct.mappingBarang.product'])->findOrFail($detail['order_item_id']);
                
                // Get platform product and its mappings
                $platformProduct = $orderItem->platformProduct;
                if (!$platformProduct || !$platformProduct->mappingBarang || $platformProduct->mappingBarang->isEmpty()) {
                    throw new \Exception("Platform product tidak memiliki mapping untuk order item {$orderItem->id}");
                }

                // Expand platform product return into individual product returns
                foreach ($platformProduct->mappingBarang as $mapping) {
                    $individualQty = floatval($detail['qty']) * $mapping->quantity;
                    
                    if ($individualQty > 0) {
                        ReturPenjualanDetail::create([
                            'retur_penjualan_id' => $returPenjualan->id,
                            'order_item_id' => $detail['order_item_id'],
                            'product_id' => $mapping->product_id,
                            'qty' => $individualQty,
                            'kondisi' => $detail['kondisi'],
                            'alasan' => $detail['alasan'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('retur-penjualan.show', $returPenjualan->id)
                ->with('success', 'Retur penjualan berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Process a draft retur penjualan, apply stock changes.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function process($id)
    {
        $returPenjualan = ReturPenjualan::with('details')->findOrFail($id);

        // Only draft status can be processed
        if ($returPenjualan->status !== 'draft') {
            return redirect()->route('retur-penjualan.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat diproses.');
        }

        try {
            DB::beginTransaction();
            \Log::info('Processing retur penjualan ID: ' . $id);

            // Process each detail item
            foreach ($returPenjualan->details as $detail) {
                // Get the order item
                $orderItem = OrderItem::findOrFail($detail->order_item_id);
                \Log::info('Processing order item ID: ' . $orderItem->id);
                
                // Validate return quantity doesn't exceed original quantity
                if (floatval($detail->qty) > $orderItem->quantity) {
                    throw new \Exception("Jumlah retur untuk item ID {$orderItem->id} melebihi jumlah asli");
                }
                
                // Update the order item quantity
                $orderItem->update([
                    'quantity' => $orderItem->quantity - floatval($detail->qty)
                ]);
                
                // Handle stock based on condition
                if ($detail->kondisi === 'BAGUS') {
                    $this->addBackToStock($detail->product_id, floatval($detail->qty), false, $returPenjualan->tanggal_retur->format('Y-m-d'), $returPenjualan->id);
                } else if ($detail->kondisi === 'RUSAK') {
                    $this->addBackToStock($detail->product_id, floatval($detail->qty), true, $returPenjualan->tanggal_retur->format('Y-m-d'), $returPenjualan->id);
                } else {
                    \Log::info('Product marked as HILANG, not adding to stock: product ID ' . $detail->product_id);
                }
            }

            // Update status to 'selesai'
            $returPenjualan->update([
                'status' => 'selesai',
                'user_id' => Auth::id() ?? null,
            ]);

            // Handle finance logic for return
            $financeService = new ReturFinanceService();
            $financeService->handleOnlineReturFinance($returPenjualan);

            DB::commit();
            \Log::info('Successfully processed retur penjualan ID: ' . $id);

            return redirect()->route('retur-penjualan.show', $id)
                ->with('success', 'Retur penjualan berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing retur penjualan: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a retur penjualan
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $returPenjualan = ReturPenjualan::findOrFail($id);

        // Only draft status can be cancelled
        if ($returPenjualan->status !== 'draft') {
            return redirect()->route('retur-penjualan.show', $id)
                ->with('error', 'Hanya retur dengan status draft yang dapat dibatalkan.');
        }

        $returPenjualan->update([
            'status' => 'dibatalkan',
        ]);

        return redirect()->route('retur-penjualan.show', $id)
            ->with('success', 'Retur penjualan berhasil dibatalkan.');
    }

    /**
     * Reverse/Cancel a completed retur penjualan (batal retur)
     * This will fully restore the state as if the return never happened
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reverseReturn($id)
    {
        $returPenjualan = ReturPenjualan::with('details')->findOrFail($id);

        // Only completed returns can be reversed
        if ($returPenjualan->status !== 'selesai') {
            return redirect()->route('retur-penjualan.show', $id)
                ->with('error', 'Hanya retur dengan status selesai yang dapat dibatalkan.');
        }

        try {
            DB::beginTransaction();
            \Log::info('Reversing completed retur penjualan ID: ' . $id);

            // Process each detail item to reverse changes
            foreach ($returPenjualan->details as $detail) {
                // Get the order item and restore its quantity
                $orderItem = OrderItem::findOrFail($detail->order_item_id);
                \Log::info('Restoring order item ID: ' . $orderItem->id . ' qty from ' . $orderItem->quantity . ' to ' . ($orderItem->quantity + $detail->qty));
                
                // Restore the order item quantity (add back the returned quantity)
                $orderItem->update([
                    'quantity' => $orderItem->quantity + floatval($detail->qty)
                ]);
                
                // Remove stock that was added during return (only for BAGUS and RUSAK conditions)
                if ($detail->kondisi === 'BAGUS') {
                    $this->removeReturnedStock($detail->product_id, floatval($detail->qty), false, $returPenjualan->id);
                } else if ($detail->kondisi === 'RUSAK') {
                    $this->removeReturnedStock($detail->product_id, floatval($detail->qty), true, $returPenjualan->id);
                }
                // For HILANG condition, no stock was added, so nothing to remove
            }

            // Update status to 'dibatalkan'
            $returPenjualan->update([
                'status' => 'dibatalkan',
                'user_id' => Auth::id() ?? null,
            ]);

            DB::commit();
            \Log::info('Successfully reversed retur penjualan ID: ' . $id);

            return redirect()->route('retur-penjualan.show', $id)
                ->with('success', 'Retur penjualan berhasil dibatalkan. Semua perubahan telah dikembalikan ke kondisi semula.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error reversing retur penjualan: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Add stock back to warehouse
     * 
     * @param  int  $productId
     * @param  float  $qty
     * @param  bool  $isDamaged
     * @param  string|null  $returDate
     * @param  int|null  $returPenjualanId
     * @return void
     */
    private function addBackToStock($productId, $qty, $isDamaged = false, $returDate = null, $returPenjualanId = null)
    {
        \Log::info("addBackToStock called for product ID: {$productId}, qty: {$qty}, isDamaged: " . ($isDamaged ? 'yes' : 'no'));
        
        try {
            // Ensure qty is a float
            $qty = floatval($qty);
            
            // Check if the product exists
            $product = Product::where('products.id', $productId)->first();
            if (!$product) {
                throw new \Exception("Product with ID {$productId} not found");
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
                    
                if (!$penerimaanDetail) {
                    \Log::warning("No penerimaan_detail found for product ID: {$productId}, using a fallback");
                    
                    // If no penerimaan_detail exists for this product, find any penerimaan_detail
                    // This is a fallback solution to prevent database errors
                    $anyPenerimaanDetail = PenerimaanDetail::orderBy('id', 'desc')->first();
                    
                    if (!$anyPenerimaanDetail) {
                        throw new \Exception("No penerimaan_detail records found in the system. Cannot process return.");
                    }
                    
                    $penerimaanDetailId = $anyPenerimaanDetail->id;
                    \Log::info("Using fallback penerimaan_detail_id: {$penerimaanDetailId}");
                } else {
                    $penerimaanDetailId = $penerimaanDetail->id;
                    \Log::info("Found penerimaan_detail_id: {$penerimaanDetailId} for product");
                }
            }
            
            // Get the retur warehouse location (same as offline)
            $returLocation = \App\Models\Lokasi::where('kode', 'GUDANG_RETUR')->first();
            if (!$returLocation) {
                $returLocation = \App\Models\Lokasi::create([
                    'kode' => 'GUDANG_RETUR',
                    'nama' => 'Gudang Retur',
                    'deskripsi' => 'Tempat penyimpanan barang hasil retur'
                ]);
                \Log::info("Created new retur location with ID: {$returLocation->id}");
            }

            // Create a new warehouse stock entry for returned items
            $warehouseStock = WarehouseStock::create([
                'product_id' => $productId,
                'lokasi_id' => $returLocation->id, // Use retur location
                'penerimaan_detail_id' => $penerimaanDetailId,
                'tax_id' => $taxId,
                'qty' => $qty,
                'expired_date' => $expiredDate,
                'status_ed' => $statusEd,
                'is_damaged' => $isDamaged,
                'catatan' => 'Retur penjualan pada ' . ($returDate ?? now()->format('Y-m-d')),
                'source_type' => 'retur_penjualan',
                'source_id' => $returPenjualanId,
                'source_date' => $returDate ? \Carbon\Carbon::parse($returDate) : now(),
            ]);
            
            \Log::info("Created new warehouse stock ID: {$warehouseStock->id} with qty: {$qty}", [
                'penerimaan_detail_id' => $penerimaanDetailId,
                'tax_id' => $taxId,
                'expired_date' => $expiredDate,
                'status_ed' => $statusEd,
                'is_damaged' => $isDamaged
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in addBackToStock: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            throw $e; // Re-throw to be caught by the calling method
        }
    }

    /**
     * Remove stock that was added during return processing
     * 
     * @param  int  $productId
     * @param  float  $qty
     * @param  bool  $isDamaged
     * @param  int  $returPenjualanId
     * @return void
     */
    private function removeReturnedStock($productId, $qty, $isDamaged, $returPenjualanId)
    {
        \Log::info("removeReturnedStock called for product ID: {$productId}, qty: {$qty}, isDamaged: " . ($isDamaged ? 'yes' : 'no'));
        
        try {
            // Find warehouse stock entries that were created by this return
            $returnedStocks = WarehouseStock::where('product_id', $productId)
                ->where('is_damaged', $isDamaged)
                ->where('source_type', 'retur_penjualan')
                ->where('source_id', $returPenjualanId)
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
                
                // Reduce the stock quantity
                $stock->qty -= $qtyToRemove;
                $stock->save();
                
                // Update remaining quantity
                $remainingQty -= $qtyToRemove;
                
                \Log::info("Stock ID: {$stock->id} updated, new qty: {$stock->qty}");
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

    public function export()
    {
        $returPenjualans = \App\Models\ReturPenjualan::with([
                'order.platform', 
                'user', 
                'details.product', 
                'details.orderItem.platformProduct.mappingBarang'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $exportData = [];
        foreach ($returPenjualans as $retur) {
            $resi = $retur->order->resi ?? ($retur->order->no_resi ?? '-');
            foreach ($retur->details as $detail) {
                // Calculate correct price per individual product for paket
                if (!$detail->orderItem) {
                    $harga = 0;
                } else {
                    $platformProduct = $detail->orderItem->platformProduct;
                    if (!$platformProduct || !$platformProduct->mappingBarang || $platformProduct->mappingBarang->isEmpty()) {
                        // Non-paket product: use original price
                        $harga = $detail->orderItem->price_after_discount;
                    } else {
                        // Paket product: calculate total quantity in the package
                        $totalPackageQty = $platformProduct->mappingBarang->sum('quantity');
                        
                        // Calculate price per individual product
                        $harga = $totalPackageQty > 0 ? 
                            $detail->orderItem->price_after_discount / $totalPackageQty : 
                            $detail->orderItem->price_after_discount;
                        
                        // Add debug log for verification
                        \Log::info("Export paket calculation for order {$retur->order->order_number}: Package price {$detail->orderItem->price_after_discount}, Package qty {$totalPackageQty}, Individual price {$harga}");
                    }
                }
                
                // Get platform product name (variant) and actual product name separately
                $platformProductName = $detail->orderItem && $detail->orderItem->platformProduct ? 
                    $detail->orderItem->platformProduct->platform_product_name : '-';
                $productName = $detail->product ? $detail->product->name : '-';
                
                $exportData[] = [
                    'Kode Retur' => $retur->kode_retur,
                    'Nomor Order' => (string)$retur->order->order_number, // ✅ Convert to string to prevent scientific notation
                    'No. Resi' => (string)$resi, // ✅ Convert to string as well
                    'Platform' => $retur->order->platform->name ?? '-',
                    'Tanggal Retur' => $retur->tanggal_retur ? $retur->tanggal_retur->format('d/m/Y') : '-',
                    'Status' => Str::ucfirst($retur->status),
                    'User' => $retur->user->name,
                    'Nama Produk' => $platformProductName,
                    'Varian Produk' => $productName,
                    'Harga Produk' => round($harga, 2), // Round to 2 decimal places
                    'Qty' => $detail->qty,
                    'Total Harga' => round($harga * $detail->qty, 2), // Round total as well
                    'Kondisi' => $detail->kondisi,
                    'Alasan' => $detail->alasan ?? '-',
                ];
            }
        }

        return Excel::download(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithColumnFormatting, \Maatwebsite\Excel\Concerns\WithCustomValueBinder {
            protected $data;
            public function __construct($data) { $this->data = $data; }
            public function array(): array { return $this->data; }
            public function headings(): array {
                return [
                    'Kode Retur', 'Nomor Order', 'No. Resi', 'Platform', 'Tanggal Retur', 'Status', 'User',
                    'Nama Produk', 'Varian Produk', 'Harga Produk', 'Qty', 'Total Harga', 'Kondisi', 'Alasan'
                ];
            }
            public function columnFormats(): array {
                return [
                    'B' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // Nomor Order as text
                    'C' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // No. Resi as text
                ];
            }
            
            public function bindValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, $value)
            {
                $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
                
                // Force Nomor Order (column B = index 2) and No. Resi (column C = index 3) as text
                if (($columnIndex === 2 || $columnIndex === 3) && is_string($value) && !empty($value) && $value !== '-') {
                    $cell->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    return true;
                }
                
                // Default binding for other cells
                return (new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder())->bindValue($cell, $value);
            }
        }, 'retur_penjualan.xlsx');
    }

    /**
     * Print the retur penjualan invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        $returPenjualan = ReturPenjualan::with([
            'order',
            'order.platform',
            'order.orderItems',
            'user',
            'details.product',
            'details.orderItem',
            'details.orderItem.platformProduct'
        ])->findOrFail($id);

        // Only allow printing of completed returns
        if ($returPenjualan->status !== 'selesai') {
            return redirect()->route('retur-penjualan.show', $id)
                ->with('error', 'Hanya retur dengan status selesai yang dapat dicetak.');
        }

        return view('retur.penjualan.print', compact('returPenjualan'));
    }
} 