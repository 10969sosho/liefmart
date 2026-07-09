@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Gross Profit Offline</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('analytics.index') }}">Analytics</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Gross Profit Offline</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" onclick="exportData()">
                <i class="fas fa-file-excel me-2"></i> Export Excel
            </button>
        </div>
        
        @include('analytics.partials.export_script', ['exportType' => 'gross_profit_offline'])
    </div>

    <!-- Filter Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light py-3">
            <h6 class="mb-0"><i class="fas fa-filter me-2 text-primary"></i> Filter & Pencarian</h6>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label small fw-medium">Tanggal Mulai</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="end_date" class="form-label small fw-medium">Tanggal Akhir</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="invoice_number" class="form-label small fw-medium">No. Invoice</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-file-invoice"></i></span>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ $selectedInvoice }}" placeholder="No. Invoice">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="po_number" class="form-label small fw-medium">No. PO</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-file-alt"></i></span>
                            <input type="text" class="form-control" id="po_number" name="po_number" value="{{ $selectedPO }}" placeholder="No. PO">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="sku" class="form-label small fw-medium">SKU</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                            <input type="text" class="form-control" id="sku" name="sku" value="{{ $selectedSKU }}" placeholder="SKU">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="customer_id" class="form-label small fw-medium">Customer</label>
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="">Semua Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $selectedCustomer == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-6 d-flex align-items-end justify-content-center">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                        <a href="{{ route('analytics.offline.gross-profit') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards - Server rendered (fast) -->
    <div id="summaryContainer">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="stat-icon rounded d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background-color: rgba(74, 108, 247, 0.1);">
                                    <i class="fas fa-shopping-cart text-primary" style="font-size: 1.2rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Total Penjualan</h6>
                                <h3 class="card-title fw-bold mb-0">{{ number_format($totalSales) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="stat-icon rounded d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background-color: rgba(34, 197, 94, 0.1);">
                                    <i class="fas fa-dollar-sign text-success" style="font-size: 1.2rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Total Revenue</h6>
                                <h3 class="card-title fw-bold mb-0">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="stat-icon rounded d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background-color: rgba(59, 130, 246, 0.1);">
                                    <i class="fas fa-receipt text-info" style="font-size: 1.2rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Total Revenue -PPN</h6>
                                <h3 class="card-title fw-bold mb-0">Rp {{ number_format($totalRevenueWithoutPPN, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="stat-icon rounded d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background-color: rgba(255, 193, 7, 0.1);">
                                    <i class="fas fa-chart-line text-warning" style="font-size: 1.2rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Total Profit</h6>
                                <h3 class="card-title fw-bold mb-0">Rp {{ number_format($totalProfit, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="stat-icon rounded d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background-color: rgba(220, 53, 69, 0.1);">
                                    <i class="fas fa-percentage text-danger" style="font-size: 1.2rem;"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Average Margin</h6>
                                <h3 class="card-title fw-bold mb-0">{{ number_format($averageMargin, 2) }}%</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table - Loaded via AJAX -->
    <div class="card shadow-sm" id="tableCard">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <span>Detail Gross Profit Offline</span>
                <small class="text-muted ms-2" id="totalRecords"></small>
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted" id="loadingIndicator" style="font-size: 0.8rem; display: none;">
                    <i class="fas fa-spinner fa-spin me-1"></i> Memuat data...
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="tableContainer">
                <div class="table-responsive disable-fixed-scrollbar" style="max-height: 65vh; overflow-y: auto; overflow-x: auto;">
                    <table class="table table-hover" id="dataTable">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr class="bg-white">
                                <th scope="col" class="text-center">#</th>
                                <th scope="col">Tgl Penjualan</th>
                                <th scope="col">Tgl Bayar</th>
                                <th scope="col">Customer</th>
                                <th scope="col">No. PO</th>
                                <th scope="col">No. Invoice</th>
                                <th scope="col">Nama Produk</th>
                                <th scope="col" class="text-center">Qty</th>
                                <th scope="col">SKU</th>
                                <th scope="col" class="text-end">Pembayaran per INV</th>
                                <th scope="col" class="text-end">per INV -PPN</th>
                                <th scope="col" class="text-end">per Produk -PPN</th>
                                <th scope="col" class="text-end">per PCS -PPN</th>
                                <th scope="col" class="text-end">COGS/pcs</th>
                                <th scope="col" class="text-end">COGS Total</th>
                                <th scope="col" class="text-end">Profit/pcs</th>
                                <th scope="col" class="text-end">Profit/Produk</th>
                                <th scope="col" class="text-end">Profit/INV</th>
                                <th scope="col" class="text-center">Margin/pcs</th>
                                <th scope="col" class="text-center">Margin/Produk</th>
                                <th scope="col" class="text-center">Margin/INV</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr id="skeletonRow">
                                <td colspan="21">
                                    @include('analytics.partials.skeleton_table')
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" id="paginationContainer" style="display: none !important;">
                <div class="text-muted small" id="paginationInfo"></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Hidden: initial filter values for AJAX -->
<div id="filterData" 
     data-start="{{ $startDate }}" 
     data-end="{{ $endDate }}"
     data-invoice="{{ $selectedInvoice }}"
     data-po="{{ $selectedPO }}"
     data-sku="{{ $selectedSKU }}"
     data-customer="{{ $selectedCustomer }}">
</div>
@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let isLoading = false;

    function loadTableData(page) {
        if (isLoading) return;
        isLoading = true;
        
        const filterData = document.getElementById('filterData');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const tableBody = document.getElementById('tableBody');
        const paginationContainer = document.getElementById('paginationContainer');
        
        loadingIndicator.style.display = 'inline';
        tableBody.innerHTML = `
            <tr>
                <td colspan="21">
                    @include('analytics.partials.skeleton_table')
                </td>
            </tr>
        `;
        
        const params = new URLSearchParams({
            page: page,
            start_date: filterData.dataset.start || '',
            end_date: filterData.dataset.end || '',
            invoice_number: filterData.dataset.invoice || '',
            po_number: filterData.dataset.po || '',
            sku: filterData.dataset.sku || '',
            customer_id: filterData.dataset.customer || '',
        });
        
        fetch('{{ route("analytics.offline.gross-profit.table-data") }}?' + params.toString())
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    tableBody.innerHTML = data.html;
                    currentPage = data.page;
                    
                    // Update summary cards
                    if (data.summary) {
                        updateSummary(data.summary);
                    }
                    
                    // Update pagination
                    updatePagination(data.page, data.last_page, data.total);
                    
                    // Show pagination
                    paginationContainer.style.display = 'flex';
                }
                loadingIndicator.style.display = 'none';
                isLoading = false;
            })
            .catch(() => {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="21" class="text-center py-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                <h5 class="fw-normal">Gagal memuat data</h5>
                                <p class="text-muted">Terjadi kesalahan. <a href="javascript:void(0)" onclick="loadTableData(1)">Coba lagi</a></p>
                            </div>
                        </td>
                    </tr>
                `;
                loadingIndicator.style.display = 'none';
                isLoading = false;
            });
    }
    
    function updateSummary(summary) {
        const container = document.getElementById('summaryContainer');
        if (!container) return;
        
        // Find card titles and update values
        const cardTitles = container.querySelectorAll('.card-title.fw-bold');
        if (cardTitles.length >= 5) {
            // Total Sales
            cardTitles[0].textContent = Number(summary.totalSales).toLocaleString('id-ID');
            // Total Revenue
            cardTitles[1].textContent = 'Rp ' + Number(summary.totalRevenue).toLocaleString('id-ID');
            // Revenue -PPN
            cardTitles[2].textContent = 'Rp ' + Number(summary.totalRevenueWithoutPPN).toLocaleString('id-ID');
            // Total Profit
            cardTitles[3].textContent = 'Rp ' + Number(summary.totalProfit).toLocaleString('id-ID');
            // Average Margin
            cardTitles[4].textContent = Number(summary.averageMargin).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
        }
        
        // Update total records
        const totalEl = document.getElementById('totalRecords');
        if (totalEl) {
            totalEl.textContent = '(' + Number(summary.totalSales).toLocaleString('id-ID') + ' transaksi)';
        }
    }
    
    function updatePagination(current, lastPage, total) {
        const info = document.getElementById('paginationInfo');
        const links = document.getElementById('paginationLinks');
        
        if (info) {
            info.textContent = 'Menampilkan halaman ' + current + ' dari ' + lastPage + ' (total ' + Number(total).toLocaleString('id-ID') + ' baris)';
        }
        
        if (!links) return;
        
        let html = '';
        
        // Prev button
        html += `<li class="page-item ${current <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadTableData(${current - 1})">&laquo;</a>
        </li>`;
        
        // Page numbers
        const start = Math.max(1, current - 2);
        const end = Math.min(lastPage, current + 2);
        
        if (start > 1) {
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadTableData(1)">1</a></li>`;
            if (start > 2) {
                html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
            }
        }
        
        for (let i = start; i <= end; i++) {
            html += `<li class="page-item ${i === current ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="loadTableData(${i})">${i}</a>
            </li>`;
        }
        
        if (end < lastPage) {
            if (end < lastPage - 1) {
                html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadTableData(${lastPage})">${lastPage}</a></li>`;
        }
        
        // Next button
        html += `<li class="page-item ${current >= lastPage ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadTableData(${current + 1})">&raquo;</a>
        </li>`;
        
        links.innerHTML = html;
    }

    // Export function
    function exportData() {
        const filterData = document.getElementById('filterData');
        const filters = {};
        
        const startDate = filterData.dataset.start;
        const endDate = filterData.dataset.end;
        const invoiceNumber = filterData.dataset.invoice;
        const poNumber = filterData.dataset.po;
        const sku = filterData.dataset.sku;
        const customerId = filterData.dataset.customer;
        
        if (startDate) filters.start_date = startDate;
        if (endDate) filters.end_date = endDate;
        if (invoiceNumber) filters.invoice_number = invoiceNumber;
        if (poNumber) filters.po_number = poNumber;
        if (sku) filters.sku = sku;
        if (customerId) filters.customer_id = customerId;
        
        dispatchExport('gross_profit_offline', filters);
    }

    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        loadTableData(1);
        
        // Intercept form submit to use AJAX
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const filterData = document.getElementById('filterData');
            const form = e.target;
            
            filterData.dataset.start = form.querySelector('[name="start_date"]').value;
            filterData.dataset.end = form.querySelector('[name="end_date"]').value;
            filterData.dataset.invoice = form.querySelector('[name="invoice_number"]').value;
            filterData.dataset.po = form.querySelector('[name="po_number"]').value;
            filterData.dataset.sku = form.querySelector('[name="sku"]').value;
            filterData.dataset.customer = form.querySelector('[name="customer_id"]').value;
            
            loadTableData(1);
        });
        
        // Initialize date formatting
        function waitForDateFormat() {
            if (typeof window.formatDateDDMMYYYY === 'function' && window.dateFormatLoaded) {
                initializeDateInputs();
            } else {
                setTimeout(waitForDateFormat, 100);
            }
        }
        
        function initializeDateInputs() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            if (startDateInput && endDateInput) {
                if (startDateInput.value) startDateInput.value = window.convertToDDMMYYYY(startDateInput.value);
                if (endDateInput.value) endDateInput.value = window.convertToDDMMYYYY(endDateInput.value);
                
                startDateInput.addEventListener('focus', function() {
                    if (this.value) this.value = window.convertToDDMMYYYY(this.value);
                });
                endDateInput.addEventListener('focus', function() {
                    if (this.value) this.value = window.convertToDDMMYYYY(this.value);
                });
                
                startDateInput.addEventListener('blur', function() {
                    if (this.value) this.value = window.convertToYYYYMMDD(this.value);
                });
                endDateInput.addEventListener('blur', function() {
                    if (this.value) this.value = window.convertToYYYYMMDD(this.value);
                });
            }
        }
        
        waitForDateFormat();
    });
</script>
@endpush
