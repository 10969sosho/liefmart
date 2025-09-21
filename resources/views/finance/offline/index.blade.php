@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-primary">
                        <i class="fas fa-file-invoice-dollar me-2"></i>{{ __('Daftar Barang Penjualan Offline') }}
                    </h5>
                    <div>
                        <a href="{{ route('finance.offline.invoices') }}" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-list-alt me-1"></i> List Invoice
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Session Messages -->
                    @if(session('success') || session('error'))
                        <div class="alert alert-{{ session('success') ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ session('success') ? 'check-circle' : 'exclamation-circle' }} me-2"></i>
                                <strong>{{ session('success') ? 'Sukses!' : 'Error!' }}</strong>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p class="mb-0 mt-2">{!! session('success') ?? session('error') !!}</p>
                        </div>
                    @endif

                    <!-- Filter Section -->
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-filter me-2"></i> Filter Data
                            </h6>
                        </div>
                            <div class="card-body py-3">
                            <form action="{{ route('finance.offline.index') }}" method="GET">
                                <div class="row g-3">
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <label for="date_start" class="form-label small fw-bold">Tanggal Mulai</label>
                                        <input type="date" class="form-control form-control-sm" id="date_start" name="date_start" value="{{ request('date_start') }}">
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <label for="date_end" class="form-label small fw-bold">Tanggal Akhir</label>
                                        <input type="date" class="form-control form-control-sm" id="date_end" name="date_end" value="{{ request('date_end') }}">
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <label for="no_po" class="form-label small fw-bold">No. PO</label>
                                        <input type="text" class="form-control form-control-sm" id="no_po" name="no_po" placeholder="Cari PO..." value="{{ request('no_po') }}">
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <label for="sj_number" class="form-label small fw-bold">No. Surat Jalan</label>
                                        <input type="text" class="form-control form-control-sm" id="sj_number" name="sj_number" placeholder="Cari SJ..." value="{{ request('sj_number') }}">
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <label for="customer" class="form-label small fw-bold">Customer</label>
                                        <input type="text" class="form-control form-control-sm" id="customer" name="customer" placeholder="Cari customer..." value="{{ request('customer') }}">
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-sm-6">
                                        <label for="tax_id" class="form-label small fw-bold">Kategori Pajak</label>
                                        <select class="form-select form-select-sm" id="tax_id" name="tax_id">
                                            <option value="">Semua</option>
                                            <option value="3" {{ request('tax_id') == '3' ? 'selected' : '' }}>PKP</option>
                                            <option value="4" {{ request('tax_id') == '4' ? 'selected' : '' }}>Non-PKP</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label for="product_name" class="form-label small fw-bold">Nama Produk</label>
                                        <input type="text" class="form-control form-control-sm" id="product_name" name="product_name" placeholder="Cari produk..." value="{{ request('product_name') }}">
                                        <small class="form-text text-muted">Cari berdasarkan nama produk</small>
                                    </div>
                                    <div class="col-lg-2 col-md-3 col-sm-6">
                                        <label for="invoice_status" class="form-label small fw-bold">Status Invoice</label>
                                        <select class="form-select form-select-sm" id="invoice_status" name="invoice_status">
                                            <option value="">Semua</option>
                                            <option value="with_invoice" {{ request('invoice_status') == 'with_invoice' ? 'selected' : '' }}>Sudah Ada Invoice</option>
                                            <option value="no_invoice" {{ request('invoice_status') == 'no_invoice' ? 'selected' : '' }}>Belum Ada Invoice</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-1 col-md-3 col-sm-6 d-flex align-items-end">
                                        <div class="d-grid w-100 gap-2">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-search me-1"></i> Filter
                                            </button>
                                            <a href="{{ route('finance.offline.index') }}" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-times me-1"></i> Reset
                                            </a>
                                        </div>
                                        </div>
                                    </div>
                                </form>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-list me-2"></i> Daftar Barang Penjualan
                            </h6>
                            <span class="badge bg-primary">{{ $groupedItems->total() }} data</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40" class="text-center">#</th>
                                            <th width="120">No. PO</th>
                                            <th width="150">Customer</th>
                                            <th width="100">Tanggal</th>
                                            <th width="100">No. SJ</th>
                                            <th width="70">Tax ID</th>
                                            <th width="120">Kategori</th>
                                            <th>Produk</th>
                                            <th width="70" class="text-center">Qty</th>
                                            <th width="120" class="text-end">Sub Total (Rp)</th>
                                            <th width="130">Invoice</th>
                                            <th width="80" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $counter = 0; @endphp
                                        @forelse($groupedItems as $offlineSaleId => $items)
                                            @php
                                                $firstItem = $items->first();
                                                $offlineSale = $firstItem->offlineSaleItem && $firstItem->offlineSaleItem->offlineSale ? 
                                                    $firstItem->offlineSaleItem->offlineSale : null;
                                                
                                                // Get unique invoice numbers for this PO
                                                $invoiceNumbers = $items->map(function($item) {
                                                    return $item->financeOffline ? $item->financeOffline->invoice_number : null;
                                                })->filter()->unique()->values()->all();
                                                
                                                // Check if all items have invoices
                                                $allHaveInvoices = $items->every(function($item) {
                                                    return $item->financeOffline !== null;
                                                });
                                                
                                                $poNumber = $offlineSale ? $offlineSale->No_PO : 'No PO';
                                                $customer = $offlineSale && $offlineSale->customerInfo ? $offlineSale->customerInfo->name : 'No Customer';
                                                $saleDate = $offlineSale && $offlineSale->sale_date ? $offlineSale->sale_date->format('d/m/Y') : '-';
                                                $sjNumber = $offlineSale ? $offlineSale->surat_jalan_number : '-';
                                                
                                                // Get main category information
                                                $mainCategoryName = session('main_category_name', 'Kategori tidak diketahui');
                                                if ($offlineSale && $offlineSale->mainCategory) {
                                                    $mainCategoryName = $offlineSale->mainCategory->name;
                                                }
                                            @endphp
                                            
                                            @foreach($items as $index => $item)
                                                @php 
                                                    $counter++;
                                                    $rowClass = $index === 0 ? 'border-top border-primary' : '';
                                                    
                                                    // Tax ID badge
                                                    $taxId = '-';
                                                    $taxBadgeClass = 'bg-secondary';
                                                    $taxLabel = '-';
                                                    
                                                    if($item->warehouseStock && $item->warehouseStock->tax_id) {
                                                        $taxId = $item->warehouseStock->tax_id;
                                                        if($taxId == 3) {
                                                            $taxBadgeClass = 'bg-primary';
                                                            $taxLabel = 'PKP';
                                                        } elseif($taxId == 4) {
                                                            $taxBadgeClass = 'bg-info';
                                                            $taxLabel = 'Non-PKP';
                                                        }
                                                    }
                                                    
                                                    // Product data
                                                    $productName = '-';
                                                    $productCode = '';
                                                    if($item->warehouseStock && $item->warehouseStock->product) {
                                                        $productName = $item->warehouseStock->product->name;
                                                        $productCode = $item->warehouseStock->product->code ? ' ('.$item->warehouseStock->product->code.')' : '';
                                                    }
                                                    
                                                    // Price data
                                                    // Hitung Sub Total dengan semua diskon
                                                    $subTotal = 0;
                                                    if($item->offlineSaleItem) {
                                                        // Ambil harga dasar dan qty
                                                        $basePrice = $item->offlineSaleItem->unit_price;
                                                        $qty = $item->qty;
                                                        
                                                        // Hitung total sebelum diskon
                                                        $totalBeforeDiscount = $basePrice * $qty;
                                                        $currentTotal = $totalBeforeDiscount;
                                                        
                                                        // Hitung semua diskon persen (1-5)
                                                        for($i = 1; $i <= 5; $i++) {
                                                            $percentField = "discount_percent_" . $i;
                                                            $discountPercent = $item->offlineSaleItem->$percentField ?? 0;
                                                            if($discountPercent > 0) {
                                                                $discountAmount = $currentTotal * ($discountPercent / 100);
                                                                $currentTotal -= $discountAmount;
                                                                // Apply cascading rounding after each discount
                                                                $currentTotal = \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
                                                            }
                                                        }
                                                        
                                                        // Hitung semua diskon nominal (1-5)
                                                        for($i = 1; $i <= 5; $i++) {
                                                            $amountField = "discount_amount_" . $i;
                                                            $discountAmount = $item->offlineSaleItem->$amountField ?? 0;
                                                            if($discountAmount > 0) {
                                                                $currentTotal -= ($discountAmount * $qty);
                                                                // Apply cascading rounding after each discount
                                                                $currentTotal = \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
                                                            }
                                                        }
                                                        
                                                        $subTotal = \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
                                                    }
                                                    
                                                    // Jika sudah ada di financeOffline, tetap gunakan perhitungan yang sama
                                                    if($item->financeOffline) {
                                                        // Gunakan subtotal yang sudah dihitung di offlineSaleItem
                                                        $subTotal = $item->offlineSaleItem->subtotal ?? 0;
                                                    }
                                                @endphp
                                                
                                                <tr class="{{ $rowClass }}">
                                                    <td class="text-center">{{ $counter }}</td>
                                                    @if($index === 0)
                                                        <td rowspan="{{ count($items) }}">{{ $poNumber }}</td>
                                                        <td rowspan="{{ count($items) }}">{{ $customer }}</td>
                                                        <td rowspan="{{ count($items) }}">{{ $saleDate }}</td>
                                                        <td rowspan="{{ count($items) }}" class="text-wrap" style="min-width: 120px;">{{ $sjNumber }}</td>
                                                    @endif
                                                    <td class="text-center">
                                                        <span class="badge {{ $taxBadgeClass }}">{{ $taxLabel }}</span>
                                                    </td>
                                                    @if($index === 0)
                                                        <td rowspan="{{ count($items) }}">{{ $mainCategoryName }}</td>
                                                    @endif
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="product-name" title="{{ $productName }}">{{ $productName }}</span>
                                                            @if($productCode)
                                                                <small class="text-muted">{{ $productCode }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-center">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                                    <td class="text-end">{{ number_format(round($subTotal), 0, ',', '.') }}</td>
                                                    <td>
                                                        @if($item->financeOffline)
                                                            <a href="{{ route('finance.offline.print-invoice', $item->financeOffline->invoice_number) }}" 
                                                               class="badge bg-success text-decoration-none" 
                                                               target="_blank">
                                                                {{ $item->financeOffline->invoice_number }}
                                                            </a>
                                                        @else
                                                            <span class="badge bg-secondary">Belum Ada</span>
                                                        @endif
                                                    </td>
                                                    @if($index === 0)
                                                        <td rowspan="{{ count($items) }}" class="text-center align-middle">
                                                            @if(!$allHaveInvoices)
                                                                <a href="{{ route('finance.offline.generate-invoice', $offlineSaleId) }}" 
                                                                   class="btn btn-sm btn-primary" 
                                                                   title="Generate Invoice">
                                                                    <i class="fas fa-file-invoice"></i>
                                                                </a>
                                                                                                        @elseif(count($invoiceNumbers) > 0)
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-success dropdown-toggle" type="button" aria-expanded="false">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @foreach($invoiceNumbers as $invoiceNumber)
                                                            <li>
                                                                <a href="{{ route('finance.offline.print-invoice', $invoiceNumber) }}" 
                                                                   class="dropdown-item" 
                                                                   target="_blank">
                                                                    <i class="fas fa-file-invoice text-success me-2"></i>
                                                                    {{ $invoiceNumber }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                        @if($offlineSale && $offlineSale->hasReturns())
                                                            <li><hr class="dropdown-divider"></li>
                                                            @foreach($invoiceNumbers as $invoiceNumber)
                                                                <li>
                                                                    <a href="{{ route('finance.offline.print-return-invoice', $invoiceNumber) }}" 
                                                                       class="dropdown-item" 
                                                                       target="_blank">
                                                                        <i class="fas fa-undo text-warning me-2"></i>
                                                                        PRINT INV Retur - {{ $invoiceNumber }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        @endif
                                                    </ul>
                                                </div>
                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center py-4">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                        <h6 class="fw-normal mb-1">Data tidak ditemukan</h6>
                                                        <p class="text-muted small">Tidak ada barang penjualan offline yang sesuai dengan filter</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($groupedItems->hasPages())
                                <div class="card-footer bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">
                                            Menampilkan data dari {{ $counter }} item
                                        </div>
                                        {{ $groupedItems->withQueryString()->links() }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-responsive {
        overflow-x: auto !important;
    }
    .table {
        font-size: 13px;
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    
    .table th {
        font-weight: 600;
        padding: 10px 8px;
        border: 1px solid #e3e6f0;
        background-color: #f8f9fa;
        vertical-align: middle;
    }
    
    .table td {
        padding: 8px;
        border: 1px solid #e3e6f0;
        vertical-align: middle;
    }
    
    .dropdown-menu {
        font-size: 13px;
        min-width: 200px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 9999;
    }
    
    /* Prevent dropdown from being cut off */
    .table td:last-child {
        position: relative;
        overflow: visible;
    }
    
    .table td .dropdown {
        position: static;
    }
    
    .table td .dropdown-menu {
        position: absolute;
        right: 0;
        top: 100%;
        transform: none !important;
        will-change: auto !important;
    }
    
    .dropdown-item {
        padding: 0.5rem 1rem;
        white-space: nowrap;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .border-top.border-primary {
        border-top-width: 2px !important;
    }
    
    .badge {
        font-weight: 500;
        font-size: 85%;
    }
    
    .product-name {
        word-wrap: break-word;
        word-break: break-word;
        max-width: 100%;
        display: block;
    }
    
    /* Media queries for responsive design */
    @media (max-width: 992px) {
        .table {
            font-size: 12px;
        }
        
        .table th, 
        .table td {
            padding: 8px 5px;
        }
        
        .product-name {
            max-width: 250px;
        }
    }
    
    @media (max-width: 768px) {
        .table {
            font-size: 11px;
        }
        
        .table th, 
        .table td {
            padding: 6px 4px;
        }
        
        .product-name {
            max-width: 150px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips if they exist
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        if (tooltipTriggerList.length > 0) {
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        // Simple dropdown handling without complex positioning
        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close all other dropdowns first
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    if (menu !== toggle.nextElementSibling) {
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                const menu = toggle.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    menu.classList.toggle('show');
                }
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                menu.classList.remove('show');
            });
        });
        
        // Only initialize filter collapse if the element exists
        const filterCollapse = document.getElementById('filterCollapse');
        if (filterCollapse) {
            const bsCollapse = new bootstrap.Collapse(filterCollapse, {
                toggle: localStorage.getItem('financeOfflineFilterShown') !== 'false'
            });
            
            filterCollapse.addEventListener('hidden.bs.collapse', function () {
                localStorage.setItem('financeOfflineFilterShown', 'false');
            });
            
            filterCollapse.addEventListener('shown.bs.collapse', function () {
                localStorage.setItem('financeOfflineFilterShown', 'true');
            });
        }
        
        // Date shortcuts - only if elements exist
        const dateButtons = document.querySelectorAll('.btn-date');
        if (dateButtons.length > 0) {
            dateButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const days = parseInt(this.getAttribute('data-days'));
                    const endDate = new Date();
                    const startDate = new Date();
                    startDate.setDate(startDate.getDate() - days);
                    
                    const dateEndEl = document.getElementById('date_end');
                    const dateStartEl = document.getElementById('date_start');
                    
                    if (dateEndEl) dateEndEl.value = formatDate(endDate);
                    if (dateStartEl) dateStartEl.value = formatDate(startDate);
                });
            });
        }
        
        // Month shortcuts - only if elements exist
        const monthButtons = document.querySelectorAll('.btn-month');
        if (monthButtons.length > 0) {
            monthButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const monthType = this.getAttribute('data-month');
                    const today = new Date();
                    
                    if (monthType === 'current') {
                        const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        const dateStartEl = document.getElementById('date_start');
                        const dateEndEl = document.getElementById('date_end');
                        
                        if (dateStartEl) dateStartEl.value = formatDate(startDate);
                        if (dateEndEl) dateEndEl.value = formatDate(today);
                    }
                });
            });
        }
        
        // Helper to format date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Form enhancement: Auto-submit on date change (optional)
        const dateInputs = document.querySelectorAll('#date_start, #date_end');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Optional: You can add auto-submit functionality here
                // this.closest('form').submit();
            });
        });
        
        // Form validation
        const filterForm = document.querySelector('form[action*="finance.offline.index"]');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                const dateStart = document.getElementById('date_start');
                const dateEnd = document.getElementById('date_end');
                
                if (dateStart && dateEnd && dateStart.value && dateEnd.value) {
                    if (new Date(dateStart.value) > new Date(dateEnd.value)) {
                        e.preventDefault();
                        alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
                        return false;
                    }
                }
            });
        }
    });
</script>
@endpush