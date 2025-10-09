@extends('layouts.app')

@section('content')

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2"></i> Error!
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        @php
            $errorMessage = session('error');
            // Mencari pola "Error memproses..." dalam pesan error dan mengubahnya menjadi array
            $errors = [];
            if (preg_match_all('/Error memproses.*?(?=, Error memproses|$)/s', $errorMessage, $matches)) {
                $errors = $matches[0];
            } else {
                $errors = [$errorMessage];
            }
        @endphp
        <ul class="mb-0 ps-3">
            @foreach($errors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-2"></i> Peringatan!
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <p class="mb-0">{{ session('warning') }}</p>
    </div>
@endif


<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-primary">
                        <i class="fas fa-table me-2"></i>{{ __('Preview Data Import') }} - Shopee
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-light py-2 px-3 rounded">
                                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}" class="text-decoration-none">Menu Penjualan</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.choose-type') }}" class="text-decoration-none">Pilih Tipe Penjualan</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.online') }}" class="text-decoration-none">Penjualan Online</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.platform', ['platform' => 'shopee']) }}" class="text-decoration-none">Platform Shopee</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.shopee.import-excel') }}" class="text-decoration-none">Import Excel Shopee</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Preview Data</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    
                    <!-- Ringkasan Import dalam Card -->
                    <div class="card bg-light mb-4 border-0 shadow-sm">
                        <div class="card-header bg-primary text-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i> Ringkasan Import</h6>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-info text-white rounded-circle p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 small text-muted">Total Baris</p>
                                                <h5 class="mb-0 fw-bold">{{ isset($totalRows) ? $totalRows : count($data) }}</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success text-white rounded-circle p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 small text-muted">Baris Diproses</p>
                                                <h5 class="mb-0 fw-bold">{{ count($data) }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-warning text-white rounded-circle p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-ban"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 small text-muted">Baris Di-skip</p>
                                                <h5 class="mb-0 fw-bold">{{ isset($skippedRows) ? (is_array($skippedRows) && isset($skippedRows[0]['count']) ? collect($skippedRows)->pluck('count')->sum() : array_sum($skippedRows)) : 0 }}</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-secondary text-white rounded-circle p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-copy"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 small text-muted">Order Duplikat</p>
                                                <h5 class="mb-0 fw-bold">{{ isset($duplicateOrdersInFile) ? count($duplicateOrdersInFile) : 0 }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-danger text-white rounded-circle p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-unlink"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 small text-muted">Produk Belum Terpetakan</p>
                                                <h5 class="mb-0 fw-bold">{{ count($unmappedProducts) }}</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger text-white rounded-circle p-2 me-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 small text-muted">Data Tidak Valid</p>
                                                <h5 class="mb-0 fw-bold">{{ count($invalidData ?? []) }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Alert untuk informasi baris yang di-skip atau masalah lainnya --}}
                    @if(isset($skippedRows) && count($skippedRows) > 0)
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i> Informasi:
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p>Beberapa baris di-skip karena tidak memenuhi kriteria:</p>
                            <ul>
                                @foreach($skippedRows as $row)
                                    @php
                                        $reason = is_array($row) ? ($row['reason'] ?? '-') : (is_array($reason ?? null) ? json_encode($reason) : ($reason ?? '-'));
                                        $count = is_array($row) ? ($row['count'] ?? 1) : (is_array($count ?? null) ? json_encode($count) : ($count ?? 1));
                                    @endphp
                                    <li>{{ $count }} baris di-skip karena: {{ $reason }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- Alert untuk informasi No Order duplikat dalam file --}}
                    @if(isset($duplicateOrdersInFile) && count($duplicateOrdersInFile) > 0)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-copy me-2"></i> Order Duplikat dalam File:
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p>Beberapa order dengan produk yang sama dalam file akan di-skip:</p>
                            <div class="row">
                                @foreach($duplicateOrdersInFile as $duplicate)
                                    <div class="col-md-6 col-sm-12">
                                        <span class="badge bg-warning text-dark border mb-2 py-2 px-3 w-100 text-start">
                                            <i class="fas fa-tag me-2"></i>{{ $duplicate['order_number'] }} - {{ $duplicate['product_name'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    {{-- Alert untuk informasi No Order duplikat di database --}}
                    @if(isset($duplicateOrdersInDatabase) && count($duplicateOrdersInDatabase) > 0)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i> Order Duplikat di Database:
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p><strong>Beberapa nomor order sudah ada di database dan akan di-skip:</strong></p>
                            <div class="row">
                                @foreach($duplicateOrdersInDatabase as $duplicate)
                                    <div class="col-md-6 col-sm-12">
                                        <span class="badge bg-warning text-dark border mb-2 py-2 px-3 w-100 text-start">
                                            <i class="fas fa-skip-forward me-2"></i>{{ $duplicate['order_number'] }} - {{ $duplicate['product_name'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                   
                    @if(!empty($unmappedProducts))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i> Perhatian!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p>Beberapa produk belum memiliki mapping ke Master Product. <strong>Produk baru telah otomatis dibuat di Master Barang Platform dan perlu dimapping ke Master Product internal.</strong></p>
                            <p class="mb-2"><strong>Langkah selanjutnya:</strong></p>
                            <ol class="mb-3">
                                <li>Klik tombol "Mapping" untuk setiap produk di bawah ini</li>
                                <li>Pilih Master Product internal yang sesuai</li>
                                <li>Setelah semua produk dimapping, klik "Proses Import"</li>
                            </ol>
                            <div class="list-group">
                                @foreach($unmappedProducts as $product)
                                    @php
                                        // Handle both array format (new) and string format (backward compatibility)
                                        if (is_array($product)) {
                                            $productName = $product['name'];
                                            $variant = $product['variant'] ?? '';
                                            $fullProductName = $product['full_name'];
                                        } else {
                                            // Fallback to old parsing method for backward compatibility
                                            $productParts = explode(' - ', $product, 2);
                                            $productName = $productParts[0];
                                            $variant = isset($productParts[1]) ? $productParts[1] : '';
                                            $fullProductName = $product;
                                        }
                                    @endphp
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">{{ $productName }}</span>
                                            @if($variant)
                                                <small class="text-muted">Variant: {{ $variant }}</small>
                                            @endif
                                        </div>
                                        <a href="{{ route('master.mapping.index', ['search' => $productName]) }}" 
                                            class="btn btn-sm btn-warning" target="_blank">
                                            <i class="fas fa-link me-1"></i> Mapping
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            
                            @if(!empty($ordersWithUnmappedProducts))
                                <hr>
                                <p class="mb-2"><strong>{{ count($ordersWithUnmappedProducts) }} pesanan</strong> berikut mengandung produk yang belum dimapping:</p>
                                <div class="row">
                                    @foreach($ordersWithUnmappedProducts as $orderNumber)
                                        <div class="col-md-4 col-sm-6">
                                            <span class="badge bg-light text-dark border mb-2 py-2 px-3 w-100 text-start">
                                                <i class="fas fa-tag me-2 text-warning"></i>{{ $orderNumber }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 mb-0 small text-muted">Lakukan mapping produk terlebih dahulu untuk memproses pesanan ini.</p>
                            @endif
                        </div>
                    @endif

                    @if(!empty($invalidData))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-times-circle me-2"></i> Data Tidak Valid!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p>Beberapa data tidak valid:</p>
                            <ul>
                                @foreach($invalidData as $issue)
                                    @if(is_array($issue) && isset($issue['row'], $issue['data'], $issue['issues']))
                                        <li>
                                            Baris ke-{{ $issue['row'] }}: 
                                            {{ implode(', ', $issue['issues']) }}
                                            (No Order: {{ $issue['data']['no_order'] ?? '-' }}, Produk: {{ $issue['data']['nama_produk'] ?? ($issue['data']['nama_barang'] ?? '-') }})
                                        </li>
                                    @else
                                        <li>{{ is_array($issue) ? json_encode($issue) : $issue }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Alert untuk produk dengan stok tidak mencukupi --}}
                    @if(isset($insufficientStockProducts) && count($insufficientStockProducts) > 0)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i> Stok Tidak Mencukupi!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p><strong>Beberapa produk memiliki stok yang tidak mencukupi dan tidak bisa dilanjutkan:</strong></p>
                            <p class="text-danger mb-3">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <strong>Contoh:</strong> Barang ini QTY 100, diperlukan 102, tidak bisa dilanjutkan
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th class="text-center">Dibutuhkan</th>
                                            <th class="text-center">Tersedia</th>
                                            <th class="text-center">Kekurangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($insufficientStockProducts as $product)
                                            <tr>
                                                <td>
                                                    <strong>{{ $product['product_name'] }}</strong>
                                                    @if(isset($product['platform_product_name']))
                                                        <br><small class="text-muted">Platform: {{ $product['platform_product_name'] }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger">{{ $product['required_qty'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning text-dark">{{ $product['available_qty'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger">{{ $product['required_qty'] - $product['available_qty'] }}</span>
                                                    <br><small class="text-danger">Kekurangan</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Alert untuk mapping errors --}}
                    @if(isset($mappingErrors) && count($mappingErrors) > 0)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i> Mapping Error!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p>Beberapa produk tidak memiliki mapping barang:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Platform Product ID</th>
                                            <th>Nama Produk</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($mappingErrors as $error)
                                            <tr>
                                                <td>{{ $error['platform_product_id'] }}</td>
                                                <td>{{ $error['platform_product_name'] }}</td>
                                                <td class="text-danger">{{ $error['error'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Alert untuk data errors --}}
                    @if(isset($dataErrors) && count($dataErrors) > 0)
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i> Data Validation Error!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p>Beberapa data memiliki format yang tidak valid:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Row</th>
                                            <th>Order Number</th>
                                            <th>Errors</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($dataErrors as $error)
                                            <tr>
                                                <td>{{ $error['row'] }}</td>
                                                <td>{{ $error['order_number'] }}</td>
                                                <td>
                                                    <ul class="mb-0">
                                                        @foreach($error['errors'] as $err)
                                                            <li class="text-danger">{{ $err }}</li>
                                                        @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Alert untuk stock errors detail --}}
                    @if(isset($stockErrors) && count($stockErrors) > 0)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i> Stock Error Detail!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <p>Detail error stok yang akan mempengaruhi order:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-center">Dibutuhkan</th>
                                            <th class="text-center">Tersedia</th>
                                            <th class="text-center">Kekurangan</th>
                                            <th>Order Terpengaruh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockErrors as $error)
                                            <tr>
                                                <td>{{ $error['product_name'] }}</td>
                                                <td class="text-center">{{ $error['required_qty'] }}</td>
                                                <td class="text-center">{{ $error['available_qty'] }}</td>
                                                <td class="text-center text-danger fw-bold">{{ $error['shortage'] }}</td>
                                                <td>
                                                    @if(!empty($error['order_affected']))
                                                        <small>
                                                            @foreach($error['order_affected'] as $order)
                                                                <span class="badge bg-secondary me-1">{{ $order }}</span>
                                                            @endforeach
                                                        </small>
                                                    @else
                                                        <small class="text-muted">N/A</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-table me-2"></i> Data Preview
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-scroll-container">
                                <div class="table-responsive" style="overflow-x: auto !important; overflow-y: auto; max-height: 65vh;">
                                    <table class="table table-bordered table-hover" style="min-width: 1200px;">
                                        <thead style="position: sticky; top: 0; z-index: 1;">
                                            <tr class="bg-light">
                                                <th class="text-center" width="50">No</th>
                                                <th class="text-center" width="100">Tanggal</th>
                                                <th class="text-center" width="160">No Order</th>
                                                <th>Nama Barang</th>
                                                <th>Variasi</th>
                                                <th class="text-center" width="60">Qty</th>
                                                <th class="text-end" width="100">Harga</th>
                                                <th class="text-end" width="110">Total Item</th>
                                                <th class="text-center" width="140">No Resi</th>
                                                <th class="text-center" width="100">Status Hari</th>
                                                <th class="text-end" width="120">Total INV</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(!empty($data))
                                                @php 
                                                    $no = 1; 
                                                    $currentOrderNumber = null;
                                                    $orderItems = []; 
                                                    $orderTotals = [];
                                                    
                                                    // Mengelompokkan data berdasarkan nomor order
                                                    foreach($data as $item) {
                                                        $orderItems[$item['no_order']][] = $item;
                                                        if (!isset($orderTotals[$item['no_order']])) {
                                                            $orderTotals[$item['no_order']] = 0;
                                                        }
                                                        $orderTotals[$item['no_order']] += $item['qty'] * $item['harga_setelah_diskon'];
                                                    }
                                                @endphp
                                                
                                                @foreach($orderItems as $orderNumber => $items)
                                                    @php 
                                                        $rowspan = count($items);
                                                        $firstItem = $items[0];
                                                        $rowClass = $loop->even ? 'even-row' : 'odd-row';
                                                    @endphp
                                                    
                                                    @foreach($items as $index => $item)
                                                        <tr class="{{ $rowClass }} {{ $index === 0 ? 'new-order' : '' }}">
                                                            <td class="text-center">{{ $no++ }}</td>
                                                            
                                                            @if($index === 0)
                                                                <td class="text-center order-cell" rowspan="{{ $rowspan }}">
                                                                    @php
                                                                        $tanggal = $firstItem['tanggal'] ?? null;
                                                                        if ($tanggal instanceof \Carbon\Carbon) {
                                                                            echo $tanggal->format('Y-m-d');
                                                                        } elseif (is_string($tanggal) && strtotime($tanggal)) {
                                                                            echo date('Y-m-d', strtotime($tanggal));
                                                                        } else {
                                                                            echo '-';
                                                                        }
                                                                    @endphp
                                                                </td>
                                                                <td class="text-center order-number-cell" rowspan="{{ $rowspan }}">
                                                                    <span class="order-number">{{ $orderNumber }}</span>
                                                                </td>
                                                            @endif
                                                            
                                                            <td class="product-name">{{ $item['nama_barang'] }}</td>
                                                            <td>{{ $item['variasi'] ?? '-' }}</td>
                                                            <td class="text-center">{{ $item['qty'] }}</td>
                                                            <td class="text-end">
                                                                {{ number_format($item['harga_setelah_diskon'], 0, ',', '.') }}
                                                            </td>
                                                            <td class="text-end">
                                                                {{ number_format($item['qty'] * $item['harga_setelah_diskon'], 0, ',', '.') }}
                                                            </td>
                                                            
                                                            @if($index === 0)
                                                                <td class="text-center resi-cell" rowspan="{{ $rowspan }}">
                                                                    {{ $item['no_resi'] ?? '-' }}
                                                                </td>
                                                                <td class="text-center" rowspan="{{ $rowspan }}">
                                                                    {{ $item['status_hari'] ?? '-' }}
                                                                </td>
                                                                <td class="text-end invoice-total" rowspan="{{ $rowspan }}">
                                                                    {{ number_format($orderTotals[$orderNumber], 0, ',', '.') }}
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="10" class="text-center py-4">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                            <h5 class="fw-normal">Tidak ada data</h5>
                                                            <p class="text-muted">Tidak ada data yang valid untuk ditampilkan</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="{{ route('sales.shopee.import-excel') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali
                            </a>
                        </div>
                        <div class="col-md-6 text-end">
                            @if($canProceed)
                                <form action="{{ route('sales.shopee.process-import') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i> Simpan Data
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-danger d-flex align-items-start mb-0">
                                    <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                                    <div>
                                        <strong>Import Tidak Dapat Dilanjutkan!</strong>
                                        <p class="mb-2">Terdapat error yang harus diperbaiki terlebih dahulu:</p>
                                        <ul class="mb-0">
                                            @if(!empty($invalidData))
                                                <li><strong>Data tidak valid:</strong> {{ count($invalidData) }} baris memiliki format yang salah</li>
                                            @endif
                                            @if(!empty($insufficientStockProducts))
                                                <li><strong>Stok tidak mencukupi:</strong> {{ count($insufficientStockProducts) }} produk kekurangan stok (tidak bisa dilanjutkan)</li>
                                            @endif
                                            @if(!empty($unmappedProducts))
                                                <li><strong>Mapping produk:</strong> {{ count($unmappedProducts) }} produk belum di-mapping</li>
                                            @endif
                                            @if(!empty($mappingErrors))
                                                <li><strong>Mapping error:</strong> {{ count($mappingErrors) }} produk tidak memiliki mapping</li>
                                            @endif
                                            @if(!empty($dataErrors))
                                                <li><strong>Data validation:</strong> {{ count($dataErrors) }} baris memiliki error format</li>
                                            @endif
                                            @if(!empty($stockErrors))
                                                <li><strong>Stock error:</strong> {{ count($stockErrors) }} produk bermasalah dengan stok</li>
                                            @endif
                                        </ul>
                                        <p class="mb-0 mt-2">
                                            <small class="text-muted">
                                                Perbaiki semua error di atas sebelum dapat melanjutkan import.
                                            </small>
                                        </p>
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
    /* Container untuk table scrolling */
    .table-scroll-container {
        position: relative;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }
    
    /* Styling untuk tabel utama */
    .table {
        font-size: 13px;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    
    .table th {
        font-weight: 600;
        padding: 12px 8px;
        border: 1px solid #e3e6f0;
        background-color: #f8f9fa;
        vertical-align: middle;
        white-space: nowrap;
    }
    
    .table td {
        padding: 10px 8px;
        border: 1px solid #e3e6f0;
        vertical-align: middle;
    }
    
    /* Styling untuk baris alternating */
    .odd-row {
        background-color: #ffffff;
    }
    
    .even-row {
        background-color: #f9fafc;
    }
    
    /* Styling untuk baris awal order baru */
    .new-order td {
        border-top: 2px solid #4e73df;
    }
    
    /* Styling untuk cell order yang di-rowspan */
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
    
    /* Styling untuk nama produk */
    .product-name {
        max-width: 300px;
        line-height: 1.4;
    }
    
    /* Styling untuk total invoice */
    .invoice-total {
        font-weight: bold;
        background-color: #eef5ff;
        color: #2c3e50;
    }
    
    /* Styling untuk nomor resi */
    .resi-cell {
        font-family: monospace;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
        background-color: #f8f9fa;
    }
    
    /* Pastikan tabel dapat horizontal scrolling pada perangkat kecil */
    .table-responsive {
        overflow-x: auto !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }
    
    /* Fix untuk iOS */
    @supports (-webkit-touch-callout: none) {
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
    }
    
    /* Fix untuk browser yang tidak mendukung sticky dengan baik */
    @supports (position: sticky) {
        .table thead {
            position: sticky;
            top: 0;
            z-index: 1;
        }
    }
    
    /* Styling untuk alert */
    .alert {
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .alert .list-group {
        max-height: 300px;
        overflow-y: auto;
        margin-top: 10px;
    }
    
    .alert .badge {
        font-weight: normal;
        font-size: 0.85rem;
    }
    
    /* Media queries untuk responsive */
    @media (max-width: 992px) {
        .table {
            font-size: 12px;
        }
        
        .table th, 
        .table td {
            padding: 8px 5px;
        }
        
        .product-name {
            max-width: 200px;
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
    // Ensure horizontal scroll works properly
    const tableContainers = document.querySelectorAll('.table-responsive');
    tableContainers.forEach(container => {
        container.classList.add('disable-fixed-scrollbar');
        
        // Force browser to recalculate scroll dimensions
        setTimeout(function() {
            container.style.display = 'none';
            container.offsetHeight; // force reflow
            container.style.display = '';
        }, 10);
    });
    
    // Fix for Safari and other browsers that might have issues
    window.addEventListener('resize', function() {
        tableContainers.forEach(container => {
            container.style.width = '';
            container.style.width = container.parentElement.offsetWidth + 'px';
        });
    });
    
    // Initial trigger
    window.dispatchEvent(new Event('resize'));
});
</script>
@endpush