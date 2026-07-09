@extends('layouts.app')

@section('title', 'Analytics Penjualan Detail')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
<style>
:root {
    --primary-color: #4361ee;
    --sticky-col-bg: #1f2937;
    --frozen-shadow: 2px 0 6px rgba(0,0,0,0.08);
}

/* ── Table wrapper ── */
.table-responsive {
    border-radius: 10px;
    border: 1px solid #e9eaec;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    max-height: 700px;
    overflow: auto;
}

/* ── Scroll indicator ── */
.table-responsive::after {
    content: '';
    position: sticky;
    right: 0;
    top: 0;
    width: 40px;
    height: 100%;
    background: linear-gradient(to right, transparent, rgba(0,0,0,0.04));
    pointer-events: none;
    z-index: 2;
}

/* ── Header ── */
.table-analytics thead {
    position: sticky;
    top: 0;
    z-index: 20;
}

.table-analytics thead tr th {
    background-color: var(--sticky-col-bg) !important;
    color: #f9fafb !important;
    font-weight: 500;
    font-size: 0.675rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 10px 10px;
    border-bottom: 1px solid #374151 !important;
    white-space: nowrap;
    vertical-align: middle;
    position: sticky;
    top: 0;
}

/* ── Column widths ── */
.col-no        { width: 38px; min-width: 38px; }
.col-date      { width: 92px; min-width: 92px; }
.col-day       { width: 65px; min-width: 65px; }
.col-order     { width: 140px; min-width: 140px; }
.col-platform  { width: 80px; min-width: 80px; }
.col-product   { min-width: 200px; }
.col-variant   { width: 80px; min-width: 80px; }
.col-qty       { width: 48px; min-width: 48px; }
.col-qty-retur { width: 68px; min-width: 68px; }
.col-price     { width: 105px; min-width: 105px; }
.col-total-item{ width: 115px; min-width: 115px; }
.col-qty-total { width: 72px; min-width: 72px; }
.col-invoice   { width: 125px; min-width: 125px; }
.col-resi      { width: 125px; min-width: 125px; }

/* ── Frozen columns (sticky horizontal) ── */
.col-frozen {
    position: sticky !important;
    z-index: 3;
    background-color: inherit;
    overflow: hidden;
}
.table-analytics thead tr th.col-frozen {
    z-index: 22;
    background-color: var(--sticky-col-bg) !important;
}
/* Last frozen column gets shadow on right edge */
.col-frozen.col-frozen-last {
    box-shadow: 2px 0 6px rgba(0,0,0,0.08);
}

/* ── Body rows ── */
.table-analytics tbody tr td {
    padding: 6px 10px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f1f3;
    background-color: #fff;
}
/* Ensure frozen td has explicit bg */
.table-analytics tbody tr td.col-frozen {
    background-color: #fff;
}
/* Even/odd rows for frozen cells */
.table-row-even td.col-frozen { background-color: #fafbfc; }
.table-row-odd td.col-frozen  { background-color: #fff; }
/* Hover state for frozen cells */
.table-analytics tbody tr:hover td.col-frozen {
    background-color: rgba(99,102,241,0.05);
}

.table-analytics tbody tr { transition: background-color 160ms ease; }

/* Zebra */
.table-row-even { background-color: #fafbfc; }
.table-row-odd  { background-color: #fff; }

/* ── Click-to-copy ── */
.copy-cell {
    cursor: copy;
    position: relative;
}
.copy-cell:hover {
    background-color: rgba(99,102,241,0.04);
}
.copy-cell .copy-feedback {
    position: absolute;
    top: 50%;
    right: 6px;
    transform: translateY(-50%);
    font-size: 0.65rem;
    color: #10b981;
    opacity: 0;
    transition: opacity 200ms ease;
    pointer-events: none;
}
.copy-cell.copied .copy-feedback {
    opacity: 1;
}

/* ── Column resize handle ── */
.col-resizer {
    position: absolute;
    top: 0;
    right: 0;
    width: 4px;
    height: 100%;
    cursor: col-resize;
    z-index: 5;
}
.col-resizer:hover,
.col-resizer.resizing {
    background: rgba(99,102,241,0.4);
}
.table-analytics thead tr th { position: relative; }

/* ── Column toggle menu ── */
.col-toggle-menu {
    max-height: 380px;
    overflow-y: auto;
    min-width: 230px;
    padding: 4px 0;
}
.col-toggle-menu .col-menu-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    cursor: grab;
    user-select: none;
    transition: background 120ms ease;
}
.col-toggle-menu .col-menu-item:hover { background: #f3f4f6; }
.col-toggle-menu .col-menu-item.dragging { opacity: 0.4; }
.col-toggle-menu .col-menu-item.drag-over { border-top: 2px solid #6366f1; }
.col-toggle-menu .col-menu-item .drag-handle {
    color: #9ca3af;
    font-size: 0.75rem;
    cursor: grab;
    flex-shrink: 0;
}
.col-toggle-menu .col-menu-item .col-label {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.8rem;
}
.col-toggle-menu .col-menu-item .col-actions {
    display: flex;
    gap: 2px;
    align-items: center;
    flex-shrink: 0;
    margin-left: auto;
}
.col-toggle-menu .col-menu-item .col-actions button {
    border: none;
    background: none;
    padding: 2px 5px;
    font-size: 0.7rem;
    color: #9ca3af;
    border-radius: 3px;
    cursor: pointer;
    transition: all 120ms ease;
    line-height: 1;
}
.col-toggle-menu .col-menu-item .col-actions button:hover { background: #e5e7eb; color: #6366f1; }
.col-toggle-menu .col-menu-item .col-actions button.active { color: #6366f1; }
.col-toggle-menu .col-menu-item .col-actions .col-hide-btn { font-size: 0.85rem; }

/* ── Platform badges ── */
.platform-badge {
    display: inline-block;
    padding: 1px 8px;
    border-radius: 4px;
    font-size: 0.68rem;
    font-weight: 500;
}
.platform-shopee  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.platform-tiktok  { background: #f3f4f6; color: #111827; border: 1px solid #e5e7eb; }
.platform-offline { background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; }
.platform-unknown { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

/* ── Variant badge ── */
.variant-badge {
    display: inline-block;
    padding: 1px 7px;
    border-radius: 4px;
    font-size: 0.68rem;
    font-weight: 500;
    background: #eef2ff;
    color: #4f46e5;
    border: 1px solid #e0e7ff;
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ── Loading skeleton ── */
.skeleton-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.85);
    z-index: 30;
    display: flex;
    align-items: center;
    justify-content: center;
}
.skeleton-spinner { width: 40px; height: 40px; }

@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}
.skeleton-row {
    height: 36px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s ease-in-out infinite;
    border-radius: 4px;
    margin: 4px 0;
}

/* ── Price tabular-nums ── */
.price-value { font-variant-numeric: tabular-nums; white-space: nowrap; }

/* ── Summary cards ── */
.icon-circle {
    height: 2.8rem;
    width: 2.8rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.card h2.fw-bold {
    font-size: clamp(1.2rem, 2.5vw, 2rem) !important;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.summary-card-body { min-width: 0; overflow: hidden; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .table-analytics { font-size: 0.7rem; }
    .table-analytics thead tr th { font-size: 0.6rem; padding: 7px 5px; }
    .table-analytics tbody tr td { padding: 4px 5px; }
    .variant-badge { font-size: 0.6rem; padding: 1px 4px; max-width: 55px; }
}
@media print {
    .table-analytics thead { position: static; }
    .col-frozen { position: static !important; }
    .table-responsive::after { display: none; }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">Analytics</li>
            <li class="breadcrumb-item active">Penjualan Detail</li>
        </ol>
    </nav>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="m-0 fw-semibold">Analytics Penjualan Detail</h5>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown" title="Atur Kolom">
                        <i class="bi bi-layout-three-columns"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end col-toggle-menu" id="colToggleMenu"></div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('analytics.sales-detail-report') }}" id="filter-form" class="mb-5 p-3 bg-light rounded">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="platform_id" class="form-label">Platform</label>
                        <select class="form-select" id="platform_id" name="platform_id">
                            <option value="">Semua Platform</option>
                            @foreach($platforms as $platform)
                                <option value="{{ $platform->id }}" {{ $selectedPlatform == $platform->id ? 'selected' : '' }}>{{ $platform->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label">Urutkan</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="date_newest" {{ $sortBy == 'date_newest' ? 'selected' : '' }}>Tanggal Terbaru</option>
                            <option value="date_oldest" {{ $sortBy == 'date_oldest' ? 'selected' : '' }}>Tanggal Terlama</option>
                            <option value="value_highest" {{ $sortBy == 'value_highest' ? 'selected' : '' }}>Value Tertinggi</option>
                            <option value="value_lowest" {{ $sortBy == 'value_lowest' ? 'selected' : '' }}>Value Terendah</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="min_price" class="form-label">Harga Min</label>
                        <input type="number" class="form-control" id="min_price" name="min_price" placeholder="Min" value="{{ request('min_price') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="max_price" class="form-label">Harga Max</label>
                        <input type="number" class="form-control" id="max_price" name="max_price" placeholder="Max" value="{{ request('max_price') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="min_qty" class="form-label">Qty Total Min</label>
                        <input type="number" min="1" class="form-control" id="min_qty" name="min_qty" placeholder="Min" value="{{ request('min_qty') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="max_qty" class="form-label">Qty Total Max</label>
                        <input type="number" min="1" class="form-control" id="max_qty" name="max_qty" placeholder="Max" value="{{ request('max_qty') }}">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="filterBtn"><i class="bi bi-search"></i> Filter</button>
                            <a href="{{ route('analytics.sales-detail-report') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                            <button type="button" class="btn btn-success" onclick="exportSalesDetailReport()"><i class="bi bi-download"></i> Export</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase mb-2 opacity-75 small"><i class="bi bi-cart me-1"></i>Total Order</h6>
                                    <h2 class="fw-bold mb-0" style="font-size: 2rem;">{{ number_format($summary['total_orders_after_returns']) }}</h2>
                                </div>
                                <div class="icon-circle bg-white bg-opacity-20 text-white flex-shrink-0">
                                    <i class="bi bi-cart" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="small opacity-75">
                                @php
                                    $totalBeforeReturns = $summary['total_orders_before_returns'] ?? 0;
                                    $ordersWithReturns = $summary['orders_with_returns'] ?? 0;
                                    $totalAfterReturns = $summary['total_orders_after_returns'] ?? 0;
                                    if ($totalBeforeReturns < $totalAfterReturns) $totalBeforeReturns = $totalAfterReturns;
                                @endphp
                                Retur {{ number_format($ordersWithReturns) }} dari {{ number_format($totalBeforeReturns) }} order
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase mb-2 opacity-75 small"><i class="bi bi-cash-coin me-1"></i>Total Value</h6>
                                    <h2 class="fw-bold mb-0" style="font-size: 2rem;">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</h2>
                                </div>
                                <div class="icon-circle bg-white bg-opacity-20 text-white flex-shrink-0">
                                    <i class="bi bi-cash-coin" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="small opacity-75">Rata-rata: Rp {{ number_format($summary['avg_order_value'], 0, ',', '.') }}/order</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase mb-2 opacity-75 small"><i class="bi bi-box me-1"></i>Total Volume</h6>
                                    <h2 class="fw-bold mb-0" style="font-size: 2rem;">{{ number_format($summary['total_volume']) }} <small class="fw-normal" style="font-size:0.6em;">pcs</small></h2>
                                </div>
                                <div class="icon-circle bg-white bg-opacity-20 text-white flex-shrink-0">
                                    <i class="bi bi-box" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="small opacity-75">Rata-rata: {{ number_format($summary['avg_order_volume'] ?? ($summary['total_volume'] / max($summary['total_orders_after_returns'], 1)), 1) }} pcs/order</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card {{ $summary['percentage_shown'] == 100 ? 'bg-secondary' : 'bg-warning' }} text-white h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="text-uppercase mb-2 opacity-75 small"><i class="bi bi-percent me-1"></i>Persentase Tampil</h6>
                                    <h2 class="fw-bold mb-0" style="font-size: 2rem;">{{ number_format($summary['percentage_shown'], 1) }}%</h2>
                                </div>
                                <div class="icon-circle bg-white bg-opacity-20 text-white flex-shrink-0">
                                    <i class="bi bi-percent" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="small opacity-75">% dari filter yang dipilih</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order List -->
            <div class="table-responsive position-relative" id="tableWrapper">
                <!-- Skeleton overlay (hidden by default) -->
                <div class="skeleton-overlay d-none" id="skeletonOverlay">
                    <div class="text-center w-75">
                        <div class="d-flex justify-content-center mb-3">
                            <div class="spinner-border text-primary skeleton-spinner" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="skeleton-row w-100"></div>
                        <div class="skeleton-row w-100"></div>
                        <div class="skeleton-row w-75"></div>
                        <div class="skeleton-row w-100"></div>
                        <div class="skeleton-row w-85"></div>
                    </div>
                </div>

                <table class="table table-hover align-middle table-analytics mb-0" id="analyticsTable">
                    <thead>
                        <tr>
                            <th class="col-no col-frozen text-center" data-col="no" data-bs-toggle="tooltip" title="Nomor urut">No</th>
                            <th class="col-date col-frozen text-center text-nowrap" data-col="date" data-bs-toggle="tooltip" title="Tanggal transaksi">Tanggal</th>
                            <th class="col-day text-center text-nowrap" data-col="day" data-bs-toggle="tooltip" title="Hari transaksi">Hari</th>
                            <th class="col-order col-frozen text-nowrap" data-col="order" data-bs-toggle="tooltip" title="Nomor order / invoice">No Order</th>
                            <th class="col-platform text-center text-nowrap" data-col="platform" data-bs-toggle="tooltip" title="Platform penjualan">Platform</th>
                            <th class="col-product text-nowrap" data-col="product" data-bs-toggle="tooltip" title="Nama produk">Nama Barang</th>
                            <th class="col-variant text-center text-nowrap" data-col="variant" data-bs-toggle="tooltip" title="Varian produk">Varian</th>
                            <th class="col-qty text-center text-nowrap" data-col="qty" data-bs-toggle="tooltip" title="Quantity">Qty</th>
                            <th class="col-qty-retur text-center text-nowrap" data-col="qty_retur" data-bs-toggle="tooltip" title="Quantity retur">QTY Retur</th>
                            <th class="col-price text-end text-nowrap" data-col="price" data-bs-toggle="tooltip" title="Harga per item">Harga</th>
                            <th class="col-total-item text-end text-nowrap" data-col="total_item" data-bs-toggle="tooltip" title="Total per item">Total Item</th>
                            <th class="col-qty-total text-center text-nowrap" data-col="qty_total" data-bs-toggle="tooltip" title="Total quantity order">Qty Total</th>
                            <th class="col-invoice text-end text-nowrap" data-col="invoice" data-bs-toggle="tooltip" title="Total invoice setelah retur">Total Invoice</th>
                            <th class="col-resi text-nowrap" data-col="resi" data-bs-toggle="tooltip" title="Nomor resi pengiriman">No Resi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = ($orders->currentPage() - 1) * $orders->perPage() + 1;
                            $currentOrderId = null;
                        @endphp

                        @forelse($orders as $order)
                            @php
                                $orderItems = $order->orderItems;
                                $rowspan = $orderItems->count();

                                $totalOrderQtyRetur = 0.0;
                                $totalOrderValueAfterRetur = 0.0;
                                $totalOrderVolumeAfterRetur = 0.0;

                                foreach($orderItems as $orderItem) {
                                    $itemQtyReturIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $orderItem->id)
                                        ->whereHas('returPenjualan', function($q) { $q->whereIn('status', ['draft', 'selesai']); })
                                        ->sum('qty');
                                    $itemQtyReturIndividual = (float) $itemQtyReturIndividual;

                                    $itemPackageQuantity = 1;
                                    if ($orderItem->platformProduct && $orderItem->platformProduct->mappingBarang && $orderItem->platformProduct->mappingBarang->count() > 0) {
                                        $itemPackageQuantity = $orderItem->platformProduct->mappingBarang->sum('quantity');
                                    }

                                    $itemQtyRetur = $itemPackageQuantity > 0 ? $itemQtyReturIndividual / $itemPackageQuantity : $itemQtyReturIndividual;
                                    $totalOrderQtyRetur += $itemQtyRetur;

                                    $currentItemQty = (float) ($orderItem->quantity ?? 0);
                                    $originalQty = $currentItemQty + $itemQtyRetur;
                                    $remainingQty = max(0.0, $originalQty - $itemQtyRetur);
                                    $totalOrderVolumeAfterRetur += $remainingQty;

                                    $itemPrice = (float) ($orderItem->price_after_discount ?? 0);
                                    $remainingValue = round($itemPrice * $remainingQty, 2);
                                    $totalOrderValueAfterRetur += $remainingValue;
                                }

                                $totalOrderVolumeAfterRetur = round($totalOrderVolumeAfterRetur, 0);
                                $totalOrderValueAfterRetur = round($totalOrderValueAfterRetur, 2);
                                $totalOrderVolumeAfterRetur = $totalOrderVolumeAfterRetur ?? 0;
                                $totalOrderValueAfterRetur = $totalOrderValueAfterRetur ?? 0;
                                $orderTotal = $order->total_value;
                            @endphp

                            @forelse($orderItems as $index => $item)
                                <tr class="{{ $index % 2 == 0 ? 'table-row-even' : 'table-row-odd' }}">
                                    <td class="col-no col-frozen text-center" data-col="no">{{ $no++ }}</td>

                                    @if($index === 0)
                                        <td class="col-date col-frozen text-center text-nowrap cell-highlight" rowspan="{{ $rowspan }}" data-col="date">
                                            @if($order->tanggal)
                                                {{ \Carbon\Carbon::parse($order->tanggal)->format('d-m-Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td class="col-day text-center cell-highlight" rowspan="{{ $rowspan }}" data-col="day">{{ $order->hari ?? '-' }}</td>

                                        <td class="col-order col-frozen cell-highlight" rowspan="{{ $rowspan }}" data-col="order">
                                            <span class="copy-cell text-mono" data-copy="{{ $order->order_number }}" data-bs-toggle="tooltip" title="{{ $order->order_number }}">
                                                {{ $order->order_number }}
                                                <span class="copy-feedback"><i class="bi bi-check"></i></span>
                                            </span>
                                        </td>

                                        <td class="col-platform text-center cell-highlight" rowspan="{{ $rowspan }}" data-col="platform">
                                            @if($order->platform)
                                                <span class="platform-badge platform-{{ strtolower(str_replace(' ', '-', $order->platform->name)) }}">{{ $order->platform->name }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    @endif

                                    <td class="col-product" data-col="product">
                                        @if ($item->platformProduct)
                                            <span class="copy-cell d-block text-truncate" data-copy="{{ $item->platformProduct->platform_product_name }}" data-bs-toggle="tooltip" title="{{ $item->platformProduct->platform_product_name }}">
                                                {{ $item->platformProduct->platform_product_name }}
                                                <span class="copy-feedback"><i class="bi bi-check"></i></span>
                                            </span>
                                        @else
                                            <span class="text-muted small">Data tidak tersedia</span>
                                        @endif
                                    </td>

                                    <td class="col-variant text-center" data-col="variant">
                                        @if ($item->platformProduct && $item->platformProduct->variant)
                                            <span class="variant-badge" data-bs-toggle="tooltip" title="{{ $item->platformProduct->variant }}">{{ $item->platformProduct->variant }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    @php
                                        $qtyReturIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $item->id)
                                            ->whereHas('returPenjualan', function($q) { $q->whereIn('status', ['draft', 'selesai']); })
                                            ->sum('qty');
                                        $qtyReturIndividual = (float) $qtyReturIndividual;

                                        $packageQuantity = 1;
                                        if ($item->platformProduct && $item->platformProduct->mappingBarang && $item->platformProduct->mappingBarang->count() > 0) {
                                            $packageQuantity = $item->platformProduct->mappingBarang->sum('quantity');
                                        }

                                        $qtyRetur = $packageQuantity > 0 ? $qtyReturIndividual / $packageQuantity : $qtyReturIndividual;
                                        $currentQty = (float) ($item->quantity ?? 0);
                                        $originalItemQty = $currentQty + $qtyRetur;
                                        $itemPrice = (float) ($item->price_after_discount ?? 0);
                                        $originalItemValue = round($itemPrice * $originalItemQty, 2);
                                        $originalItemValue = $originalItemValue ?? 0;
                                    @endphp
                                    <td class="col-qty text-center fw-medium" data-col="qty">{{ number_format($originalItemQty) }}</td>
                                    <td class="col-qty-retur text-center" data-col="qty_retur">{{ number_format($qtyRetur) }}</td>
                                    <td class="col-price text-end price-value" data-col="price">Rp {{ number_format($item->price_after_discount ?? 0, 0, ',', '.') }}</td>
                                    <td class="col-total-item text-end price-value fw-medium" data-col="total_item">Rp {{ number_format($originalItemValue, 0, ',', '.') }}</td>

                                    @if($index === 0)
                                        <td class="col-qty-total text-center fw-semibold cell-highlight" rowspan="{{ $rowspan }}" data-col="qty_total">
                                            @php
                                                $displayQtyTotal = isset($totalOrderVolumeAfterRetur) ? $totalOrderVolumeAfterRetur : 0;
                                                $displayQtyTotal = is_numeric($displayQtyTotal) ? $displayQtyTotal : 0;
                                            @endphp
                                            {{ number_format($displayQtyTotal) }}
                                        </td>

                                        <td class="col-invoice text-end price-value fw-semibold cell-highlight" rowspan="{{ $rowspan }}" data-col="invoice">
                                            @php
                                                $displayTotalInvoice = isset($totalOrderValueAfterRetur) ? $totalOrderValueAfterRetur : 0;
                                                $displayTotalInvoice = is_numeric($displayTotalInvoice) ? $displayTotalInvoice : 0;
                                            @endphp
                                            Rp {{ number_format($displayTotalInvoice, 0, ',', '.') }}
                                        </td>

                                        <td class="col-resi cell-highlight" rowspan="{{ $rowspan }}" data-col="resi">
                                            @php
                                                $trackingNumber = collect($orderItems)->pluck('tracking_number')->filter()->first();
                                            @endphp
                                            @if ($trackingNumber)
                                                <span class="copy-cell text-mono d-inline-block text-truncate" data-copy="{{ $trackingNumber }}" data-bs-toggle="tooltip" title="{{ $trackingNumber }}" style="max-width:120px;">
                                                    {{ $trackingNumber }}
                                                    <span class="copy-feedback"><i class="bi bi-check"></i></span>
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="14" class="text-center py-4 text-muted">Tidak ada item pada pesanan ini</td></tr>
                            @endforelse
                        @empty
                            <tr><td colspan="14" class="text-center py-4 text-muted">Tidak ada data penjualan</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(method_exists($orders, 'links'))
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted small">Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} data</div>
                <div>{{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
<script>
function exportSalesDetailReport() {
    // Animate bell
    var bellIcon = document.querySelector('#exportNotificationsDropdown .fa-bell');
    if (bellIcon) {
        bellIcon.classList.add('pulse-bell');
        setTimeout(function() { bellIcon.classList.remove('pulse-bell'); }, 600);
    }

    // Collect filters
    var f = {};
    var form = document.getElementById('filter-form');
    if (form) {
        var fd = new FormData(form);
        for (var pair of fd.entries()) { if (pair[1]) f[pair[0]] = pair[1]; }
    }

    // Notify user
    alert('Sedang menyiapkan export, silakan tunggu...');

    fetch('{{ route("analytics.exports.dispatch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ type: 'sales_detail_report', filters: f })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (typeof loadNotifications === 'function') loadNotifications();
            if (typeof loadExportWidget === 'function') loadExportWidget();
        }
    })
    .catch(function() {});
}
</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Init Bootstrap Tooltips ──
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) {
        return new bootstrap.Tooltip(el, { boundary: 'window', trigger: 'hover' });
    });

    // ── Set default dates ──
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const todayFormatted = getTodayYYYYMMDD();
    if (!startDateInput.value) startDateInput.value = todayFormatted;
    if (!endDateInput.value) endDateInput.value = todayFormatted;
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('start_date') && !urlParams.has('end_date') && !document.referrer.includes('sales-detail-report')) {
        document.getElementById('filter-form').submit();
    }

    // ── Click-to-copy ──
    document.querySelectorAll('.copy-cell').forEach(function(el) {
        el.addEventListener('click', function(e) {
            const text = this.dataset.copy || this.textContent.trim();
            copyText(text, this);
        });
    });

    // ── Column Toggle Menu ──
    buildColumnToggle();

    // ── Column Resize ──
    initColumnResize();

    // ── Calculate freeze positions on load ──
    recalcFreezePositions();

    // ── Loading Skeleton ──
    const filterForm = document.getElementById('filter-form');
    const skeletonOverlay = document.getElementById('skeletonOverlay');
    if (filterForm && skeletonOverlay) {
        filterForm.addEventListener('submit', function() {
            skeletonOverlay.classList.remove('d-none');
        });
    }
});

// ── Copy to clipboard ──
function copyText(text, el) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => showCopied(el)).catch(() => fallbackCopy(text, el));
    } else {
        fallbackCopy(text, el);
    }
}
function fallbackCopy(text, el) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); showCopied(el); } catch(e) {}
    document.body.removeChild(ta);
}
function showCopied(el) {
    el.classList.add('copied');
    setTimeout(function() { el.classList.remove('copied'); }, 1200);
}

// ── Column Toggle, Freeze & Reorder ──
function buildColumnToggle() {
    const menu = document.getElementById('colToggleMenu');
    if (!menu) return;
    renderColumnMenu(menu);
    enableDragReorder(menu);
}

function renderColumnMenu(menu) {
    const ths = document.querySelectorAll('#analyticsTable thead th');
    const order = getColumnOrder();
    var html = '<div class="dropdown-header small fw-semibold d-flex justify-content-between align-items-center">' +
               '<span>Atur Kolom</span>' +
               '<span class="text-muted" style="font-size:0.6rem;font-weight:400;">seret untuk urutkan</span>' +
               '</div>\n';

    // Sort ths based on saved order
    var sortedThs = Array.from(ths).sort(function(a, b) {
        var ai = order.indexOf(a.dataset.col);
        var bi = order.indexOf(b.dataset.col);
        if (ai === -1) ai = 999;
        if (bi === -1) bi = 999;
        return ai - bi;
    });

    sortedThs.forEach(function(th) {
        var label = th.textContent.trim();
        var col = th.dataset.col;
        if (!col) return;
        var visible = localStorage.getItem('col_show_' + col) !== 'hidden';
        var frozen = th.classList.contains('col-frozen');
        html += '<div class="col-menu-item" data-col="' + col + '" draggable="true">';
        html += '  <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>';
        html += '  <span class="col-label">' + label + '</span>';
        html += '  <span class="col-actions">';
        html += '    <button class="col-hide-btn" title="' + (visible ? 'Sembunyikan' : 'Tampilkan') + '" data-action="toggle-vis">';
        html += '      <i class="bi ' + (visible ? 'bi-eye' : 'bi-eye-slash') + '"></i>';
        html += '    </button>';
        html += '    <button class="col-freeze-btn' + (frozen ? ' active' : '') + '" title="' + (frozen ? 'Lepas freeze' : 'Freeze kolom') + '" data-action="toggle-freeze">';
        html += '      <i class="bi bi-pin' + (frozen ? '-fill' : '') + '"></i>';
        html += '    </button>';
        html += '  </span>';
        html += '</div>\n';
    });
    menu.innerHTML = html;

    // Button handlers
    menu.querySelectorAll('[data-action="toggle-vis"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.col-menu-item');
            var col = item.dataset.col;
            var hidden = localStorage.getItem('col_show_' + col) === 'hidden';
            localStorage.setItem('col_show_' + col, hidden ? 'visible' : 'hidden');
            toggleColumn(col, hidden);
            // Update icon
            this.innerHTML = '<i class="bi ' + (hidden ? 'bi-eye' : 'bi-eye-slash') + '"></i>';
            this.title = hidden ? 'Sembunyikan' : 'Tampilkan';
        });
    });

    menu.querySelectorAll('[data-action="toggle-freeze"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.col-menu-item');
            var col = item.dataset.col;
            var frozen = toggleColumnFreeze(col);
            this.classList.toggle('active', frozen);
            this.innerHTML = '<i class="bi bi-pin' + (frozen ? '-fill' : '') + '"></i>';
            this.title = frozen ? 'Lepas freeze' : 'Freeze kolom';
        });
    });

    // Apply visibility
    menu.querySelectorAll('.col-menu-item').forEach(function(item) {
        var col = item.dataset.col;
        if (localStorage.getItem('col_show_' + col) === 'hidden') {
            toggleColumn(col, false);
        }
    });
}

function toggleColumn(col, visible) {
    var cols = document.querySelectorAll('[data-col="' + col + '"]');
    cols.forEach(function(el) { el.style.display = visible ? '' : 'none'; });
    // Recalc freeze positions after show/hide
    setTimeout(recalcFreezePositions, 50);
    // Refresh menu to sync state
    refreshMenuState();
}

function toggleColumnFreeze(col) {
    var order = getColumnOrder();
    var colIndex = order.indexOf(col);
    if (colIndex === -1) return false;

    // Check if already frozen
    var th = document.querySelector('#analyticsTable thead th[data-col="' + col + '"]');
    var isFrozen = th && th.classList.contains('col-frozen');

    if (isFrozen) {
        // UNFREEZE: unfreeze this column and ALL columns to its right
        for (var i = order.length - 1; i >= colIndex; i--) {
            var c = order[i];
            var els = document.querySelectorAll('[data-col="' + c + '"]');
            els.forEach(function(el) {
                el.classList.remove('col-frozen', 'col-frozen-last');
                el.style.left = '';
            });
        }
        // Re-freeze columns before this one (they stay frozen)
        for (var i2 = 0; i2 < colIndex; i2++) {
            var c2 = order[i2];
            var els2 = document.querySelectorAll('[data-col="' + c2 + '"]');
            els2.forEach(function(el) { el.classList.add('col-frozen'); });
        }
    } else {
        // FREEZE: freeze this column and ALL columns to its left
        for (var j = 0; j <= colIndex; j++) {
            var c3 = order[j];
            var els3 = document.querySelectorAll('[data-col="' + c3 + '"]');
            els3.forEach(function(el) {
                el.classList.add('col-frozen');
            });
        }
    }

    recalcFreezePositions();
    refreshMenuState();

    // Check new state
    var th2 = document.querySelector('#analyticsTable thead th[data-col="' + col + '"]');
    return th2 && th2.classList.contains('col-frozen');
}

function recalcFreezePositions() {
    var order = getColumnOrder();
    var table = document.getElementById('analyticsTable');
    if (!table) return;

    // Remove all col-frozen-last classes first
    table.querySelectorAll('.col-frozen-last').forEach(function(el) {
        el.classList.remove('col-frozen-last');
    });

    // Get frozen column indices in display order
    var frozenCols = [];
    order.forEach(function(col) {
        var th = table.querySelector('thead th[data-col="' + col + '"]');
        if (th && th.classList.contains('col-frozen')) {
            frozenCols.push(col);
        }
    });

    if (frozenCols.length === 0) return;

    // Mark last frozen column
    var lastCol = frozenCols[frozenCols.length - 1];
    table.querySelectorAll('[data-col="' + lastCol + '"]').forEach(function(el) {
        el.classList.add('col-frozen-last');
    });

    // Calculate left positions
    var left = 0;
    frozenCols.forEach(function(col) {
        // Get actual rendered width from header th
        var th = table.querySelector('thead th[data-col="' + col + '"]');
        if (!th) return;
        var width = th.getBoundingClientRect().width;

        // Set on header
        th.style.left = left + 'px';

        // Set on all body cells in this column
        var colIndex = Array.from(th.parentElement.children).indexOf(th);
        table.querySelectorAll('tbody tr').forEach(function(row) {
            var cell = row.children[colIndex];
            if (cell && cell.classList.contains('col-frozen')) {
                cell.style.left = left + 'px';
            }
        });

        left += width;
    });
}

// Refresh the column menu to reflect current freeze/visibility state
function refreshMenuState() {
    var menu = document.getElementById('colToggleMenu');
    if (!menu) return;
    menu.querySelectorAll('.col-menu-item').forEach(function(item) {
        var col = item.dataset.col;
        var th = document.querySelector('#analyticsTable thead th[data-col="' + col + '"]');
        if (!th) return;
        var frozen = th.classList.contains('col-frozen');
        var visible = th.style.display !== 'none';

        // Update freeze button
        var freezeBtn = item.querySelector('[data-action="toggle-freeze"]');
        if (freezeBtn) {
            freezeBtn.classList.toggle('active', frozen);
            freezeBtn.innerHTML = '<i class="bi bi-pin' + (frozen ? '-fill' : '') + '"></i>';
            freezeBtn.title = frozen ? 'Lepas freeze' : 'Freeze kolom';
        }

        // Update visibility button
        var visBtn = item.querySelector('[data-action="toggle-vis"]');
        if (visBtn) {
            visBtn.innerHTML = '<i class="bi ' + (visible ? 'bi-eye' : 'bi-eye-slash') + '"></i>';
            visBtn.title = visible ? 'Sembunyikan' : 'Tampilkan';
        }
    });
}

function getColumnOrder() {
    try {
        var saved = JSON.parse(localStorage.getItem('col_order')) || [];
        if (saved.length > 0) return saved;
    } catch(e) {}
    // Default order from HTML
    return ['no','date','day','order','platform','product','variant','qty','qty_retur','price','total_item','qty_total','invoice','resi'];
}

function saveColumnOrder() {
    var items = document.querySelectorAll('#colToggleMenu .col-menu-item');
    var order = Array.from(items).map(function(el) { return el.dataset.col; });
    localStorage.setItem('col_order', JSON.stringify(order));
}

function enableDragReorder(menu) {
    var draggedItem = null;

    menu.addEventListener('dragstart', function(e) {
        var item = e.target.closest('.col-menu-item');
        if (!item) return;
        draggedItem = item;
        item.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.col);
    });

    menu.addEventListener('dragend', function(e) {
        var item = e.target.closest('.col-menu-item');
        if (item) item.classList.remove('dragging');
        menu.querySelectorAll('.col-menu-item').forEach(function(el) {
            el.classList.remove('drag-over');
        });
    });

    menu.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('.col-menu-item');
        if (!target || target === draggedItem) return;
        menu.querySelectorAll('.col-menu-item').forEach(function(el) { el.classList.remove('drag-over'); });

        // Determine if above or below target
        var rect = target.getBoundingClientRect();
        var midY = rect.top + rect.height / 2;
        if (e.clientY < midY) {
            target.classList.add('drag-over');
        } else {
            // Insert after
            var next = target.nextElementSibling;
            if (next) next.classList.add('drag-over');
            else {
                // No next sibling, use border-bottom approach
                target.style.borderBottom = '2px solid #6366f1';
            }
        }
    });

    menu.addEventListener('dragleave', function(e) {
        var target = e.target.closest('.col-menu-item');
        if (target) {
            target.classList.remove('drag-over');
            target.style.borderBottom = '';
        }
    });

    menu.addEventListener('drop', function(e) {
        e.preventDefault();
        menu.querySelectorAll('.col-menu-item').forEach(function(el) {
            el.classList.remove('drag-over');
            el.style.borderBottom = '';
        });
        var target = e.target.closest('.col-menu-item');
        if (!target || !draggedItem || target === draggedItem) return;

        var rect = target.getBoundingClientRect();
        var midY = rect.top + rect.height / 2;

        if (e.clientY < midY) {
            target.parentNode.insertBefore(draggedItem, target);
        } else {
            target.parentNode.insertBefore(draggedItem, target.nextSibling);
        }

        saveColumnOrder();
        applyColumnOrder();
    });
}

function applyColumnOrder() {
    var order = getColumnOrder();
    var table = document.getElementById('analyticsTable');
    if (!table) return;
    var thead = table.querySelector('thead tr');
    if (!thead) return;

    // Reorder header cells
    var ths = Array.from(thead.children);
    order.forEach(function(col) {
        var th = ths.find(function(el) { return el.dataset.col === col; });
        if (th) thead.appendChild(th);
    });

    // Reorder body cells per row
    table.querySelectorAll('tbody tr').forEach(function(row) {
        var cells = Array.from(row.children);
        order.forEach(function(col) {
            var cell = cells.find(function(el) { return el.dataset.col === col; });
            if (cell) row.appendChild(cell);
        });
    });

    // Recalculate freeze positions
    recalcFreezePositions();
}

// ── Column Resize ──
function initColumnResize() {
    var ths = document.querySelectorAll('#analyticsTable thead th');
    ths.forEach(function(th) {
        // Skip frozen columns for simplicity
        if (th.classList.contains('col-frozen')) return;

        var resizer = document.createElement('div');
        resizer.className = 'col-resizer';
        th.appendChild(resizer);

        var startX, startWidth;
        resizer.addEventListener('mousedown', function(e) {
            startX = e.clientX;
            startWidth = th.offsetWidth;
            resizer.classList.add('resizing');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';

            function onMouseMove(e2) {
                var diff = e2.clientX - startX;
                var newWidth = Math.max(40, startWidth + diff);
                th.style.width = newWidth + 'px';
                th.style.minWidth = newWidth + 'px';
                // Sync body cells in same column
                var colIndex = Array.from(th.parentElement.children).indexOf(th);
                var table = th.closest('table');
                if (table) {
                    table.querySelectorAll('tbody tr').forEach(function(row) {
                        var cell = row.children[colIndex];
                        if (cell) {
                            cell.style.width = newWidth + 'px';
                            cell.style.minWidth = newWidth + 'px';
                        }
                    });
                }
            }

            function onMouseUp() {
                resizer.classList.remove('resizing');
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            }

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    });
}
</script>
@endpush