<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceOffline;
use App\Models\BarangKeluar;
use App\Models\OfflineSale;
use App\Exports\FinanceOfflineInvoiceExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class FinanceOfflineController extends Controller
{
    /**
     * Display a listing of the barang from penjualan offline.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Query for barang_keluar items with their relationships
        $query = BarangKeluar::whereHas('offlineSaleItem')
            ->with([
                'warehouseStock',
                'warehouseStock.product',
                'warehouseStock.tax',
                'financeOffline',
                'offlineSaleItem',
                'offlineSaleItem.product',
                'offlineSaleItem.product.mainCategory',
                'offlineSaleItem.offlineSale',
                'offlineSaleItem.offlineSale.customerInfo',
                'offlineSaleItem.offlineSale.mainCategory'
            ]);

        // Apply date filters
        if ($request->filled('date_start')) {
            $query->whereHas('offlineSaleItem.offlineSale', function($q) use ($request) {
                $q->whereDate('sale_date', '>=', $request->date_start);
            });
        }

        if ($request->filled('date_end')) {
            $query->whereHas('offlineSaleItem.offlineSale', function($q) use ($request) {
                $q->whereDate('sale_date', '<=', $request->date_end);
            });
        }

        // Apply PO number filter
        if ($request->filled('no_po')) {
            $query->whereHas('offlineSaleItem.offlineSale', function($q) use ($request) {
                $q->where('No_PO', 'like', '%' . $request->no_po . '%');
            });
        }

        // Apply SJ number filter
        if ($request->filled('sj_number')) {
            $query->whereHas('offlineSaleItem.offlineSale', function($q) use ($request) {
                $q->where('surat_jalan_number', 'like', '%' . $request->sj_number . '%');
            });
        }

        // Apply customer filter
        if ($request->filled('customer')) {
            $query->whereHas('offlineSaleItem.offlineSale.customerInfo', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer . '%');
            });
        }

        // Apply product name filter - simplified to avoid complex nested queries
        if ($request->filled('product_name')) {
            $productSearch = '%' . $request->product_name . '%';
            $query->whereHas('offlineSaleItem.product', function($q) use ($productSearch) {
                $q->where('name', 'like', $productSearch);
            });
        }

        // Apply tax_id filter
        if ($request->filled('tax_id')) {
            $query->whereHas('warehouseStock', function($q) use ($request) {
                $q->where('tax_id', $request->tax_id);
            });
        }

        // Apply invoice status filter
        if ($request->filled('invoice_status')) {
            if ($request->invoice_status == 'with_invoice') {
                $query->whereNotNull('finance_offline_id');
            } elseif ($request->invoice_status == 'no_invoice') {
                $query->whereNull('finance_offline_id');
            }
        }
        
        // Apply main_category filter from session if exists
        if (session()->has('main_category_id')) {
            $mainCategoryId = session('main_category_id');
            $query->where(function($q) use ($mainCategoryId) {
                $q->whereHas('offlineSaleItem.offlineSale', function($subQ) use ($mainCategoryId) {
                    $subQ->where('main_category_id', $mainCategoryId);
                })
                ->orWhereHas('warehouseStock.product', function($subQ) use ($mainCategoryId) {
                    $subQ->where('main_category_id', $mainCategoryId);
                });
            });
        }

        // Execute the query with ordering
        $barangKeluarItems = $query->orderBy('created_at', 'desc')->get();

        // Group items by purchase order (offline_sale_id)
        $groupedItems = $barangKeluarItems->groupBy(function($item) {
            return $item->offlineSaleItem ? $item->offlineSaleItem->offline_sale_id : 'no_sale';
        });

        // Paginate the grouped items
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $groupedItemsCollection = collect($groupedItems);
        $currentPageItems = $groupedItemsCollection->forPage($currentPage, $perPage);
        
        $groupedItemsPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $groupedItemsCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('finance.offline.index', [
            'groupedItems' => $groupedItemsPaginator
        ]);
    }

    /**
     * Display a list of invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function listInvoices(Request $request)
    {
        $query = FinanceOffline::with(['barangKeluarItems', 'payments', 
                                        'barangKeluarItems.warehouseStock',
                                        'barangKeluarItems.warehouseStock.tax',
                                        'barangKeluarItems.warehouseStock.product.mainCategory',
                                        'barangKeluarItems.offlineSaleItem',
                                        'barangKeluarItems.offlineSaleItem.offlineSale',
                                        'barangKeluarItems.offlineSaleItem.offlineSale.customerInfo',
                                        'barangKeluarItems.offlineSaleItem.offlineSale.mainCategory']);

        // Filter by invoice number
        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('status', $request->payment_status);
        }

        // Filter by date range - tanggal_invoice
        if ($request->filled('date_start')) {
            $query->whereDate('tanggal_invoice', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('tanggal_invoice', '<=', $request->date_end);
        }

        // Filter by tax ID (requires checking the related warehouse stock)
        if ($request->filled('tax_id')) {
            $query->whereHas('barangKeluarItems.warehouseStock', function($q) use ($request) {
                $q->where('tax_id', $request->tax_id);
            });
        }
        
        // Apply main_category filter from session if exists
        if (session()->has('main_category_id')) {
            $mainCategoryId = session('main_category_id');
            $query->where(function($q) use ($mainCategoryId) {
                $q->where('main_category_id', $mainCategoryId)
                  ->orWhereHas('barangKeluarItems.offlineSaleItem.offlineSale', function($subQ) use ($mainCategoryId) {
                      $subQ->where('main_category_id', $mainCategoryId);
                  })
                  ->orWhereHas('barangKeluarItems.warehouseStock.product', function($subQ) use ($mainCategoryId) {
                      $subQ->where('main_category_id', $mainCategoryId);
                  });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString(); // Pass the query parameters to the paginator

        // Calculate summary data for all invoices (not just paginated)
        $allInvoices = $query->get();
        
        // Calculate proper payment status with partial payment consideration
        $totalPaidAmount = 0;
        $totalUnpaidAmount = 0;
        $paidCount = 0;
        $unpaidCount = 0;
        $partialCount = 0;
        
        foreach ($allInvoices as $invoice) {
            // Calculate grand total for this invoice (same logic as in view)
            $firstItem = $invoice->barangKeluarItems->first();
            $taxId = $firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id ? $firstItem->warehouseStock->tax_id : null;
            
            $dpp = \App\Helpers\NumberFormatter::calculateDPP($invoice->nominal);
            $grandTotal = $dpp;
            
            if ($taxId == 3) {
                $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
                $ppn = \App\Helpers\NumberFormatter::calculatePPN($dpp11_12);
                $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($dpp, $ppn);
            } else {
                $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($dpp);
            }
            
            $totalPaid = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->payments->sum('amount'));
            $remainingAmount = max(0, $grandTotal - $totalPaid);
            
            // Determine payment status
            if ($totalPaid >= $grandTotal) {
                // Fully paid
                $totalPaidAmount += $grandTotal;
                $paidCount++;
            } elseif ($totalPaid > 0) {
                // Partially paid
                $totalPaidAmount += $totalPaid;
                $totalUnpaidAmount += $remainingAmount;
                $partialCount++;
            } else {
                // Unpaid
                $totalUnpaidAmount += $grandTotal;
                $unpaidCount++;
            }
        }
        
        $summary = [
            'total_invoices' => $allInvoices->count(),
            'total_value' => $totalPaidAmount + $totalUnpaidAmount,
            'total_paid' => $totalPaidAmount,
            'total_unpaid' => $totalUnpaidAmount,
            'paid_count' => $paidCount,
            'unpaid_count' => $unpaidCount,
            'partial_count' => $partialCount,
            'avg_invoice_value' => $allInvoices->count() > 0 ? ($totalPaidAmount + $totalUnpaidAmount) / $allInvoices->count() : 0,
        ];

        return view('finance.offline.list_invoices', compact('invoices', 'summary'));
    }

    /**
     * Export invoices to Excel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportInvoices(Request $request)
    {
        // Use the same query logic as listInvoices but get all data
        $query = FinanceOffline::with(['barangKeluarItems', 'payments', 
                                        'barangKeluarItems.warehouseStock',
                                        'barangKeluarItems.warehouseStock.tax',
                                        'barangKeluarItems.warehouseStock.product.mainCategory',
                                        'barangKeluarItems.offlineSaleItem',
                                        'barangKeluarItems.offlineSaleItem.offlineSale',
                                        'barangKeluarItems.offlineSaleItem.offlineSale.customerInfo',
                                        'barangKeluarItems.offlineSaleItem.offlineSale.mainCategory']);

        // Apply the same filters as listInvoices
        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        if ($request->filled('payment_status')) {
            $query->where('status', $request->payment_status);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('tanggal_invoice', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('tanggal_invoice', '<=', $request->date_end);
        }

        if ($request->filled('tax_id')) {
            $query->whereHas('barangKeluarItems.warehouseStock', function($q) use ($request) {
                $q->where('tax_id', $request->tax_id);
            });
        }
        
        if (session()->has('main_category_id')) {
            $mainCategoryId = session('main_category_id');
            $query->where(function($q) use ($mainCategoryId) {
                $q->where('main_category_id', $mainCategoryId)
                  ->orWhereHas('barangKeluarItems.offlineSaleItem.offlineSale', function($subQ) use ($mainCategoryId) {
                      $subQ->where('main_category_id', $mainCategoryId);
                  })
                  ->orWhereHas('barangKeluarItems.warehouseStock.product', function($subQ) use ($mainCategoryId) {
                      $subQ->where('main_category_id', $mainCategoryId);
                  });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'Finance_Offline_Invoices_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new FinanceOfflineInvoiceExport($invoices), $fileName);
    }

    /**
     * Update the status of offline sales related to a paid invoice
     */
    private function updateRelatedOfflineSalesStatus($invoice)
    {
        // Get all offline sales related to this invoice
        $offlineSaleIds = $invoice->barangKeluarItems()
            ->whereHas('offlineSaleItem')
            ->with('offlineSaleItem')
            ->get()
            ->pluck('offlineSaleItem.offline_sale_id')
            ->unique();
        
        // Update status for each offline sale
        foreach ($offlineSaleIds as $offlineSaleId) {
            $offlineSale = OfflineSale::withoutGlobalScope('mainCategory')->find($offlineSaleId);
            if ($offlineSale) {
                $offlineSale->updateStatusBasedOnPayment();
            }
        }
    }
    
    /**
     * Mark an invoice as paid.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsPaid(Request $request, $id)
    {
        $invoice = FinanceOffline::findOrFail($id);
        
        // Get payment details from the request
        $paymentAmount = $request->input('payment_amount', $invoice->nominal);
        $paymentDate = $request->input('payment_date', now()->format('Y-m-d'));
        $paymentMethod = $request->input('payment_method', 'transfer');
        $paymentNotes = $request->input('payment_notes');
        
        // Create a new payment record
        $payment = new \App\Models\InvoicePayment([
            'finance_offline_id' => $invoice->id,
            'amount' => $paymentAmount,
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod,
            'notes' => $paymentNotes,
        ]);
        
        $payment->save();
        
        // Update invoice status if payment equals or exceeds the invoice amount
        $totalPaid = $invoice->payments()->sum('amount');
        
        if ($totalPaid >= $invoice->nominal) {
            $invoice->status = 'paid';
            $invoice->tanggal_bayar = $paymentDate;
            $invoice->save();
            
            // Update status of related offline sales
            $this->updateRelatedOfflineSalesStatus($invoice);
        }
        
        return redirect()->route('finance.offline.invoices')
            ->with('success', 'Pembayaran sebesar Rp ' . number_format($paymentAmount, 0, ',', '.') . ' berhasil dicatat.');
    }

    /**
     * Delete a payment record (superadmin only).
     *
     * @param  int  $paymentId
     * @return \Illuminate\Http\Response
     */
    public function deletePayment($paymentId)
    {
        // Check if user is superadmin
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menghapus pembayaran.');
        }

        try {
            DB::beginTransaction();

            $payment = \App\Models\InvoicePayment::findOrFail($paymentId);
            $invoice = $payment->invoice;
            $deletedAmount = $payment->amount;

            // Delete the payment record
            $payment->delete();

            // Recalculate invoice status
            $totalPaid = $invoice->payments()->sum('amount');
            
            if ($totalPaid >= $invoice->nominal) {
                $invoice->status = 'paid';
                // Keep the latest payment date if still paid
                $latestPayment = $invoice->payments()->latest('payment_date')->first();
                $invoice->tanggal_bayar = $latestPayment ? $latestPayment->payment_date : null;
            } else {
                $invoice->status = 'unpaid';
                $invoice->tanggal_bayar = null;
            }
            
            $invoice->save();

            // Update status of related offline sales
            $this->updateRelatedOfflineSalesStatus($invoice);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Pembayaran sebesar Rp ' . number_format($deletedAmount, 0, ',', '.') . ' berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus pembayaran: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate invoice for a barang_keluar item.
     *
     * @param  int  $saleId
     * @return \Illuminate\Http\Response
     */
    public function generateInvoice($saleId)
    {
        // Find the offline sale and its items
        $offlineSale = OfflineSale::with(['items.barangKeluar.warehouseStock.tax', 'customerInfo'])->findOrFail($saleId);
        
        // Group barangKeluar items by tax_id
        $barangKeluarGrouped = collect();
        
        foreach ($offlineSale->items as $item) {
            if ($item->barangKeluar->count() > 0) {
                foreach ($item->barangKeluar as $barangKeluar) {
                    if ($barangKeluar->financeOffline) {
                        continue; // Skip items that already have an invoice
                    }
                    
                    $taxId = $barangKeluar->warehouseStock && $barangKeluar->warehouseStock->tax_id ? 
                        $barangKeluar->warehouseStock->tax_id : 4; // Default to Non-PKP if not found
                    
                    if (!$barangKeluarGrouped->has($taxId)) {
                        $barangKeluarGrouped[$taxId] = collect();
                    }
                    
                    $barangKeluarGrouped[$taxId]->push($barangKeluar);
                }
            }
        }
        
        if ($barangKeluarGrouped->isEmpty()) {
            return redirect()->route('finance.offline.index')
                ->with('error', 'Tidak ada barang yang belum memiliki invoice.');
        }
        
        DB::beginTransaction();
        
        try {
            $invoiceMessages = [];
            
            // Process each tax group separately
            foreach ($barangKeluarGrouped as $taxId => $barangKeluarItems) {
                // Get customer info for tax purposes
                $customer = $offlineSale->customerInfo;
                
                // Generate invoice number based on tax ID
                $invoiceNumber = FinanceOffline::generateInvoiceNumber($taxId);
                
                // Calculate total nominal from all items in this group
                $nominal = $barangKeluarItems->sum(function($item) {
                    return $item->offlineSaleItem ? $item->offlineSaleItem->subtotal : 0;
                });
                
                // Set main_category_id from offlineSale or session
                $mainCategoryId = $offlineSale->main_category_id ?? session('main_category_id', null);
                
                // Create a single finance offline record for this tax group
                $financeOffline = new FinanceOffline([
                    'invoice_number' => $invoiceNumber,
                    'nominal' => $nominal,
                    'tanggal_invoice' => Carbon::now(),
                    'status' => 'unpaid',
                    'main_category_id' => $mainCategoryId
                ]);
                
                $financeOffline->save();
                
                // Associate all barangKeluar items with this invoice
                foreach ($barangKeluarItems as $barangKeluar) {
                    $barangKeluar->finance_offline_id = $financeOffline->id;
                    $barangKeluar->save();
                }
                
                $invoiceMessages[] = "Invoice {$invoiceNumber} berhasil dibuat untuk " . 
                    ($taxId == 3 ? "barang PKP" : "barang Non-PKP");
            }
            
            DB::commit();
            
            return redirect()->route('finance.offline.index')
                ->with('success', implode('<br>', $invoiceMessages));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('finance.offline.index')
                ->with('error', 'Gagal membuat invoice: ' . $e->getMessage());
        }
    }
    
    /**
     * Print the invoice.
     *
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\Response
     */
    public function printInvoice($invoiceNumber)
    {
        // The $invoiceNumber parameter will contain the full URL-encoded invoice number
        $invoice = FinanceOffline::where('invoice_number', $invoiceNumber)
            ->with([
                'barangKeluarItems', 
                'barangKeluarItems.warehouseStock',
                'barangKeluarItems.warehouseStock.product',
                'barangKeluarItems.warehouseStock.tax',
                'barangKeluarItems.offlineSaleItem', 
                'barangKeluarItems.offlineSaleItem.offlineSale',
                'barangKeluarItems.offlineSaleItem.offlineSale.customerInfo'
            ])
            ->firstOrFail();
        
        $user = auth()->user();
        
        // Check if the user can print this invoice
        if (!$invoice->canBePrinted($user)) {
            if ($invoice->print_count > 0 && !$invoice->reprint_requested) {
                // Request reprint if not already requested
                $invoice->requestReprint();
                return redirect()->route('finance.offline.invoices')
                    ->with('error', 'Anda telah mencapai batas cetak. Permohonan cetak ulang telah dikirim ke Super Admin untuk persetujuan.');
            } elseif ($invoice->reprint_requested && !$invoice->reprint_approved) {
                return redirect()->route('finance.offline.invoices')
                    ->with('error', 'Permohonan cetak ulang sedang menunggu persetujuan dari Super Admin.');
            }
            
            return redirect()->route('finance.offline.invoices')
                ->with('error', 'Anda tidak memiliki izin untuk mencetak invoice ini.');
        }
        
        // Track this print - This counts as a print before even viewing the invoice
        $invoice->trackPrint();
        
        return view('finance.offline.print_invoice', compact('invoice', 'user'));
    }

    /**
     * Print the invoice after return (new format showing items after return deduction).
     *
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\Response
     */
    public function printInvoiceAfterReturn($invoiceNumber)
    {
        $invoice = FinanceOffline::where('invoice_number', $invoiceNumber)
            ->with([
                'barangKeluarItems', 
                'barangKeluarItems.warehouseStock',
                'barangKeluarItems.warehouseStock.product',
                'barangKeluarItems.warehouseStock.tax',
                'barangKeluarItems.offlineSaleItem', 
                'barangKeluarItems.offlineSaleItem.offlineSale',
                'barangKeluarItems.offlineSaleItem.offlineSale.customerInfo'
            ])
            ->firstOrFail();
        
        $user = auth()->user();
        
        return view('finance.offline.print_invoice_after_return', compact('invoice', 'user'));
    }

    /**
     * Print the return invoice (showing only returned items).
     *
     * @param  string  $invoiceNumber
     * @return \Illuminate\Http\Response
     */
    public function printReturnInvoice($invoiceNumber)
    {
        $invoice = FinanceOffline::where('invoice_number', $invoiceNumber)
            ->with([
                'barangKeluarItems', 
                'barangKeluarItems.warehouseStock',
                'barangKeluarItems.warehouseStock.product',
                'barangKeluarItems.warehouseStock.tax',
                'barangKeluarItems.offlineSaleItem', 
                'barangKeluarItems.offlineSaleItem.offlineSale',
                'barangKeluarItems.offlineSaleItem.offlineSale.customerInfo'
            ])
            ->firstOrFail();
        
        $user = auth()->user();
        
        return view('finance.offline.print_return_invoice', compact('invoice', 'user'));
    }

    /**
     * Approve a reprint request
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approveReprint($id)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Hanya Super Admin yang dapat menyetujui permintaan cetak ulang.');
        }
        
        try {
            $invoice = FinanceOffline::findOrFail($id);
            
            if (!$invoice->reprint_requested) {
                return redirect()->route('finance.offline.invoices')
                    ->with('error', 'Tidak ada permintaan cetak ulang untuk invoice ini.');
            }
            
            if ($invoice->approveReprint($user)) {
                return redirect()->route('finance.offline.invoices')
                    ->with('success', 'Permintaan cetak ulang untuk invoice ' . $invoice->invoice_number . ' telah disetujui.');
            } else {
                return redirect()->route('finance.offline.invoices')
                    ->with('error', 'Gagal menyetujui permintaan cetak ulang, silakan coba lagi.');
            }
        } catch (\Exception $e) {
            \Log::error('Error approving reprint: ' . $e->getMessage());
            
            return redirect()->route('finance.offline.invoices')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
