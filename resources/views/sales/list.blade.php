@extends('layouts.app')

@section('content')
    <script>
        // Immediate execution script to enforce table height
        (function() {
            console.log("Sales list immediate table fix running");
            document.addEventListener('DOMContentLoaded', function() {
                // Force table height constraint
                const tableContainer = document.querySelector('.table-responsive');
                if (tableContainer) {
                    tableContainer.style.maxHeight = '500px';
                    tableContainer.style.overflowY = 'auto';
                    console.log("Applied immediate sales table height fix");
                }
            });
        })();
    </script>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary">Daftar Penjualan</h5>
                        <div>
                            <a href="{{ route('sales.choose-type') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Tambah Penjualan Baru
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" id="toggleFilterBtn">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb bg-light py-2 px-3 rounded">
                                        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}"
                                                class="text-decoration-none">Menu Penjualan</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Daftar Penjualan</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>

                        <!-- Filter Section - Hidden by default -->
                        <div id="filterSection" class="mb-4 p-3 border rounded bg-light" style="display: none;">
                            <form action="{{ route('sales.list') }}" method="GET" id="filterForm">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="dateStart" class="form-label small">Tanggal Mulai</label>
                                        <input type="date" class="form-control form-control-sm" id="dateStart" name="date_start" 
                                               value="{{ request('date_start') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="dateEnd" class="form-label small">Tanggal Akhir</label>
                                        <input type="date" class="form-control form-control-sm" id="dateEnd" name="date_end" 
                                               value="{{ request('date_end') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="platformFilter" class="form-label small">Platform</label>
                                        <select class="form-select form-select-sm" id="platformFilter" name="platform">
                                            <option value="">Semua Platform</option>
                                            <option value="shopee" {{ request('platform') == 'shopee' ? 'selected' : '' }}>Shopee</option>
                                            <option value="tokopedia" {{ request('platform') == 'tokopedia' ? 'selected' : '' }}>Tokopedia</option>
                                            <option value="tiktok" {{ request('platform') == 'tiktok' ? 'selected' : '' }}>Tiktok</option>
                                            <option value="blibli" {{ request('platform') == 'blibli' ? 'selected' : '' }}>Blibli</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="orderNumber" class="form-label small">Nomor Order</label>
                                        <input type="text" class="form-control form-control-sm" id="orderNumber" name="order_number" 
                                               placeholder="Cari nomor order..." value="{{ request('order_number') }}">
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end mt-2">
                                            <button type="submit" class="btn btn-sm btn-primary me-2">
                                                <i class="fas fa-search"></i> Cari
                                            </button>
                                            <a href="{{ route('sales.list') }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-sync-alt"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive disable-fixed-scrollbar" style="max-height: 500px; overflow-y: auto; overflow-x: auto; border: 1px solid #dee2e6;">
                            <table class="table table-hover table-bordered-bottom wide-table">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr class="bg-white">
                                        <th style="width: 40px; text-align: center;">No</th>
                                        <th style="width: 85px; text-align: center;">Tanggal</th>
                                        <th style="width: 60px; text-align: center;">Hari</th>
                                        <th style="width: 80px; text-align: center;">Status Hari</th>
                                        <th style="width: 100px; text-align: center;">Platform</th>
                                        <th style="width: 160px; text-align: center;">No Order</th>
                                        <th style="min-width: 300px; width: auto;">Nama Barang</th>
                                        <th style="width: 80px; text-align: center;">Varian</th>
                                        <th style="width: 50px; text-align: center;">Qty</th>
                                        <th style="width: 90px; text-align: right;">Harga</th>
                                        <th style="width: 90px; text-align: right;">Total Item</th>
                                        <th style="width: 100px; text-align: right;">Total Invoice</th>
                                        <th style="width: 110px; text-align: center;">No Resi</th>
                                        <th style="width: 80px; text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php 
                                        $no = ($orders->currentPage() - 1) * $orders->perPage() + 1;
                                        $currentOrderId = null;
                                        $currentOrderNumber = null;
                                        $orderTotal = 0;
                                        $rowspan = 0;
                                    @endphp
                                    @forelse($orders as $order)
                                        @php
                                            // Menghitung total per invoice (nomor pesanan)
                                            $orderItems = $order->orderItems;
                                            $orderTotal = $orderItems->sum(function($item) {
                                                return $item->price_after_discount * $item->quantity;
                                            });
                                            
                                            // Hitung rowspan untuk order ini
                                            $rowspan = $orderItems->count();
                                            
                                            // Tentukan nomor order saat ini
                                            $currentOrderNumber = $order->order_number;
                                        @endphp
                                        
                                        @forelse($orderItems as $index => $item)
                                            <tr>
                                                <!-- Nomor urut tetap ditampilkan per baris -->
                                                <td style="text-align: center;">{{ $no++ }}</td>
                                                
                                                @if($index === 0)
                                                    <!-- Tanggal hanya muncul sekali per order -->
                                                    <td style="text-align: center;" rowspan="{{ $rowspan }}">
                                                        @if($order->tanggal) 
                                                            {{ \Carbon\Carbon::parse($order->tanggal)->format('d-m-Y') }}
                                                        @else 
                                                            -
                                                        @endif
                                                    </td>
                                                    
                                                    <!-- Hari hanya muncul sekali per order -->
                                                    <td style="text-align: center;" rowspan="{{ $rowspan }}">
                                                        {{ $order->hari ?? '-' }}
                                                    </td>
                                                    
                                                    <!-- Status Hari hanya muncul sekali per order -->
                                                    <td style="text-align: center;" rowspan="{{ $rowspan }}">
                                                        {{ $order->status_hari ?? '-' }}
                                                    </td>
                                                    
                                                    <!-- Platform hanya muncul sekali per order -->
                                                    <td style="text-align: center;" rowspan="{{ $rowspan }}">
                                                        @if($order->platform)
                                                            <span class="badge bg-{{ 
                                                                $order->platform->name == 'shopee' ? 'warning' : 
                                                                ($order->platform->name == 'tokopedia' ? 'success' : 
                                                                ($order->platform->name == 'tiktok' ? 'dark' : 
                                                                ($order->platform->name == 'blibli' ? 'info' : 'primary'))) 
                                                            }}">
                                                                {{ ucfirst($order->platform->name) }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    
                                                    <!-- Nomor Order hanya muncul sekali per order -->
                                                    <td style="text-align: center;" rowspan="{{ $rowspan }}">
                                                        {{ $currentOrderNumber }}
                                                    </td>
                                                @endif
                                                
                                                <!-- Detail produk, qty dan harga tetap per baris -->
                                                <td title="{{ $item->platformProduct ? $item->platformProduct->platform_product_name : 'Data produk tidak tersedia' }}">
                                                    @if ($item->platformProduct)
                                                        <div class="product-info">
                                                            <div class="product-name fw-medium">
                                                        {{ $item->platformProduct->platform_product_name }}
                                                            </div>
                                                            @if($item->platformProduct->variant)
                                                                <div class="product-variant">
                                                                    <small class="text-muted">{{ $item->platformProduct->variant }}</small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted fst-italic">Data produk tidak tersedia</span>
                                                    @endif
                                                </td>
                                                
                                                <!-- Kolom Varian -->
                                                <td style="text-align: center;">
                                                    @if ($item->platformProduct && $item->platformProduct->variant)
                                                        <span class="badge bg-info text-white small">{{ $item->platformProduct->variant }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                
                                                <td style="text-align: center;">{{ $item->quantity }}</td>
                                                <td style="text-align: right;">
                                                    {{ number_format($item->price_after_discount, 0, ',', '.') }}
                                                </td>
                                                <td style="text-align: right;">
                                                    {{ number_format($item->price_after_discount * $item->quantity, 0, ',', '.') }}
                                                </td>
                                                
                                                @if($index === 0)
                                                    <!-- Total invoice hanya muncul sekali per order -->
                                                    <td style="text-align: right;" class="invoice-total" rowspan="{{ $rowspan }}">
                                                        {{ number_format($orderTotal, 0, ',', '.') }}
                                                    </td>
                                                    
                                                    <!-- No Resi hanya muncul sekali per order -->
                                                    <td style="text-align: center;" rowspan="{{ $rowspan }}">
                                                        @if ($item->tracking_number)
                                                            {{ $item->tracking_number }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    
                                                    <!-- Aksi hanya muncul sekali per order -->
                                                    <td style="text-align: center;" rowspan="{{ $rowspan }}">
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item" href="#" onclick="showOrderDetail('{{ $order->id }}')">
                                                                    <i class="fas fa-eye fa-sm text-primary"></i> Detail
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" onclick="printInvoice('{{ $order->id }}', '{{ strtolower($order->platform->name ?? '') }}')">
                                                                    <i class="fas fa-print fa-sm text-success"></i> Cetak
                                                                </a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                @if(Auth::check() && (Auth::user()->role === 'superadmin' || Auth::user()->isSuperAdmin() || Auth::user()->canDelete()))
                                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete('{{ $order->id }}')">
                                                                    <i class="fas fa-trash fa-sm text-danger"></i> Hapus
                                                                </a></li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="14" class="text-center py-4">Tidak ada item pada pesanan ini</td>
                                            </tr>
                                        @endforelse
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center py-4">Tidak ada data penjualan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} data</small>
                            </div>
                            <div>
                                {{ $orders->withQueryString()->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailModalLabel">Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="printOrderBtn">Cetak</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pesanan ini? Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Menghapus pesanan akan secara otomatis mengembalikan stok ke gudang.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteOrderForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Hapus Pesanan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
    .table-responsive {
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: 500px !important; /* Fixed height */
        border: 1px solid #dee2e6;
        width: 100% !important;
    }

    .wide-table {
        min-width: 1200px;
        margin-bottom: 0 !important;
    }

    table.table {
        font-size: 12px; /* Ukuran font lebih kecil */
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 6px; /* Padding lebih kecil */
        border-bottom: 2px solid #e3e6f0;
        white-space: nowrap; /* Judul tidak wrap */
        background-color: #f8f9fa !important; /* Background header */
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .table td {
        vertical-align: middle;
        padding: 6px; /* Padding lebih kecil */
        border-top: 1px solid #e3e6f0;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.04);
    }

    .pagination {
        margin-bottom: 0;
    }

    /* Style untuk baris alternating */
    tbody tr:nth-of-type(odd) {
        background-color: #f8f9fc;
    }

    /* Style khusus untuk nomor resi */
    td:last-child {
        font-family: monospace;
        font-size: 11px;
        letter-spacing: 0px;
    }
    
    /* Style untuk cell yang berisi total per invoice */
    .invoice-total {
        font-weight: bold;
        background-color: #f0f8ff;
    }
    
    /* Style untuk sel dengan rowspan */
    td[rowspan] {
        vertical-align: middle;
        background-color: #f8f9fa;
        border-right: 1px solid #e3e6f0;
    }
    
    /* Highlight untuk nomer order */
    td[rowspan]:nth-child(5) {
        font-weight: bold;
        font-family: monospace;
        background-color: #f0f8ff;
    }
    
    /* Mengatur lebar maksimum untuk kolom nama produk dan memotong teks yang terlalu panjang */
    td:nth-child(6) {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap; /* Buat nama barang hanya 1 baris */
        line-height: 1.2;
    }
    
    /* Style untuk kolom varian */
    .badge-info {
        background-color: #17a2b8;
        color: white;
        font-weight: 500;
        font-size: 10px;
        padding: 3px 6px;
        border-radius: 4px;
    }
    
    /* Style untuk sticky header */
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 1;
        background-color: #f8f9fa;
    }
    
    /* Style untuk table-bordered-bottom */
    .table-bordered-bottom th {
        border: 1px solid #e3e6f0;
    }
    
    /* Platform badge styles */
    .badge.bg-warning {
        background-color: #FF6720 !important; /* Shopee orange */
        color: white;
    }
    
    .badge.bg-success {
        background-color: #42B549 !important; /* Tokopedia green */
    }
    
    .badge.bg-dark {
        background-color: #000000 !important; /* TikTok black */
    }
    
    .badge.bg-info {
        background-color: #0074b1 !important; /* Blibli blue */
        color: white;
    }
    
    /* Animasi untuk filter section toggle */
    #filterSection {
        transition: all 0.3s ease-in-out;
        overflow: hidden;
    }
    
    /* Filter styling */
    #filterSection {
        border-left: 4px solid #4e73df !important;
    }
    
    /* Tambahan style untuk responsive pada berbagai ukuran layar */
    @media (max-width: 768px) {
        .table {
            font-size: 10px;
        }
        
        .table th, .table td {
            padding: 4px 3px;
        }
        
        td:nth-child(6) {
            max-width: 150px;
        }
        
        .badge {
            font-size: 9px;
            padding: 2px 4px;
        }
    }
    
    /* Make sticky headers work properly with horizontal scroll */
    .table-responsive .sticky-top {
        position: sticky;
        background-color: #f8f9fa;
        z-index: 2;
    }
    
    /* Tooltip styling */
    td[title]:hover {
        position: relative;
        cursor: pointer;
    }
    
    td[title]:hover::after {
        content: attr(title);
        position: absolute;
        left: 0;
        top: 100%;
        background-color: #333;
        color: white;
        padding: 5px 8px;
        border-radius: 4px;
        z-index: 10;
        max-width: 300px;
        white-space: normal;
        font-size: 11px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* Style untuk cell order yang di-rowspan */
    .order-cell {
        background-color: #f8f9fa;
    }
    
    /* Styling untuk nomor order */
    .order-number-cell {
        background-color: #eef5ff;
    }
    
    .order-number {
        font-family: monospace;
        font-weight: 600;
        color: #4e73df;
        font-size: 0.9rem;
    }
    
    /* Styling untuk product info */
    .product-info {
        max-width: 300px;
    }
    
    .product-name {
        line-height: 1.3;
        margin-bottom: 4px;
        word-break: break-word;
    }
    
    .product-variant {
        margin-top: 2px;
    }
    
    /* Styling untuk nama produk */
    .product-name {
        max-width: 300px;
        line-height: 1.4;
    }
    </style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Sales list DOM loaded");
        
        // Debug any active filters
        const urlParams = new URLSearchParams(window.location.search);
        console.log("Active URL parameters:", Object.fromEntries(urlParams.entries()));
        
        // Log platform filter value
        const platformFilter = document.getElementById('platformFilter');
        if (platformFilter) {
            console.log("Platform filter value:", platformFilter.value);
        }
        
        // Monitor and fix table dimensions
        function checkTableDimensions() {
            const tableContainer = document.querySelector('.table-responsive');
            if (tableContainer && tableContainer.scrollHeight > 500) {
                console.log("Fixing table height - current scrollHeight:", tableContainer.scrollHeight);
                tableContainer.style.maxHeight = '500px';
                tableContainer.style.overflowY = 'auto';
            }
        }
        
        // Run initially and every 2 seconds
        checkTableDimensions();
        setInterval(checkTableDimensions, 2000);
        
        // Toggle filter section
        const toggleFilterBtn = document.getElementById('toggleFilterBtn');
        const filterSection = document.getElementById('filterSection');
        
        toggleFilterBtn.addEventListener('click', function() {
            if (filterSection.style.display === 'none') {
                filterSection.style.display = 'block';
                setTimeout(() => {
                    filterSection.style.maxHeight = filterSection.scrollHeight + 'px';
                }, 10);
            } else {
                filterSection.style.maxHeight = '0px';
                setTimeout(() => {
                    filterSection.style.display = 'none';
                }, 300);
            }
        });
        
        // Show filter section if there are active filters
        if (window.location.search && window.location.search.length > 1) {
            filterSection.style.display = 'block';
        }
        
        // Date filter sync and validation
        const dateStart = document.getElementById('dateStart');
        const dateEnd = document.getElementById('dateEnd');
        
        if (dateStart && dateEnd) {
            dateStart.addEventListener('change', function() {
                if (dateEnd.value && dateStart.value > dateEnd.value) {
                    dateEnd.value = dateStart.value;
                }
            });
            
            dateEnd.addEventListener('change', function() {
                if (dateStart.value && dateEnd.value < dateStart.value) {
                    dateStart.value = dateEnd.value;
                }
            });
        }
    });
    
    // Function to show order detail in modal
    function showOrderDetail(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
        const modalContent = document.getElementById('orderDetailContent');
        
        // Set loading state
        modalContent.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data...</p>
            </div>
        `;
        
        // Show the modal
        modal.show();
        
        // Fetch order details
        fetch(`/sales/orders/${orderId}/detail`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                modalContent.innerHTML = html;
                
                // Set print button action
                document.getElementById('printOrderBtn').onclick = function() {
                    // Get platform info from the button data attribute or from the current page context
                    const platformName = document.querySelector(`[onclick*="showOrderDetail('${orderId}')"]`)
                        ?.closest('tr')?.querySelector('[onclick*="printInvoice"]')
                        ?.getAttribute('onclick')?.match(/'([^']*)'$/)?.[1] || '';
                    printInvoice(orderId, platformName);
                };
            })
            .catch(error => {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Gagal memuat detail pesanan: ${error.message}
                    </div>
                `;
            });
    }
    
    // Function to print invoice
    function printInvoice(orderId, platformName = '') {
        let printUrl;
        
        // Use different route for Blibli orders to avoid under.construction middleware
        if (platformName === 'blibli') {
            printUrl = `/sales/blibli/orders/${orderId}/print`;
        } else {
            printUrl = `/sales/orders/${orderId}/print`;
        }
        
        window.open(printUrl, '_blank');
    }
    
    // Function to confirm deletion
    function confirmDelete(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        document.getElementById('deleteOrderForm').action = `{{ url('sales/orders') }}/${orderId}`;
        modal.show();
    }
</script>
@endpush