<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gross Profit per Master Produk</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- TomSelect CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root { --primary-color: #4361ee; --secondary-color: #3f37c9; --success-color: #0bb4aa; --info-color: #4cc9f0; --warning-color: #f72585; --dark-color: #212529; --light-color: #f8f9fa; }
        body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; background-color: #f5f7fa; color: #333; line-height: 1.6; }
        .container-fluid { padding: 20px; max-width: 1440px; margin: 0 auto; }
        .card { border-radius: 10px; border: none; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; margin-bottom: 20px; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); }
        .card-header { border-radius: 10px 10px 0 0 !important; font-weight: 600; padding: 15px 20px; }
        .card-body { padding: 20px; }
        .btn { border-radius: 6px; padding: 8px 16px; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background-color: var(--secondary-color); border-color: var(--secondary-color); }
        .btn-outline-primary { color: var(--primary-color); border-color: var(--primary-color); }
        .btn-outline-primary:hover { background-color: var(--primary-color); color: white; }
        .table { border-radius: 8px; overflow: hidden; table-layout: auto; }
        .table-dark th { background-color: var(--dark-color) !important; color: white !important; font-weight: 500; }
        .table-responsive { max-height: 800px; overflow-y: auto; overflow-x: auto; }
        .table thead th { position: sticky; top: 0; z-index: 1; background-color: #212529; }
        .table tbody tr:hover { background-color: rgba(0, 0, 0, 0.075); }
        .table th:nth-child(4), .table td:nth-child(4) { min-width: 300px !important; width: 300px !important; word-wrap: break-word; }
        .table th:nth-child(8), .table td:nth-child(8) { min-width: 300px !important; width: 300px !important; word-wrap: break-word; }
        .badge { font-size: 0.8rem; font-weight: normal; border: 1px solid #dee2e6; }
        .summary-card { border-radius: 10px; color: white; height: 100%; min-height: 120px; }
        .summary-card .card-body { padding: 1rem; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .summary-card h6 { font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; text-align: center; }
        .summary-card h3, .summary-card h4 { font-weight: 700; margin-bottom: 0.25rem; text-align: center; word-break: break-all; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .summary-card small { font-size: 0.75rem; text-align: center; line-height: 1.2; }
        .bg-primary { background-color: var(--primary-color) !important; }
        .bg-success { background-color: var(--success-color) !important; }
        .bg-info { background-color: var(--info-color) !important; }
        .bg-dark { background-color: var(--dark-color) !important; }
        .bg-warning { background-color: #f59e0b !important; }
        .platform-box { display: inline-block; padding: 5px 10px; border-radius: 6px; font-weight: 500; }
        .platform-tokopedia { background-color: #42b549; color: white; }
        .platform-shopee { background-color: #f53d2d; color: white; }
        .platform-tiktok { background-color: #000000; color: white; }
        .platform-blibli { background-color: #0095da; color: white; }
        .platform-lazada { background-color: #f27e30; color: white; }
        .platform-offline { background-color: #6c757d; color: white; }
        .chart-container { position: relative; margin: 20px 0; height: 300px; }
        .breadcrumb { background-color: transparent; padding: 0; margin-bottom: 20px; }
        .breadcrumb-item a { color: var(--primary-color); text-decoration: none; }
        .breadcrumb-item.active { color: #6c757d; }
        .form-control, .form-select { border-radius: 6px; border: 1px solid #ced4da; padding: 10px 15px; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25); border-color: var(--primary-color); }
        .display-5 { font-size: 2.5rem; font-weight: 600; }
        .ts-wrapper { border-radius: 6px; }
        .ts-wrapper .ts-control { border: 1px solid #ced4da; border-radius: 6px; padding: 10px 15px; }
        .ts-wrapper.focus .ts-control { box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25); border-color: var(--primary-color); }
        @media (max-width: 768px) { .container-fluid { padding: 15px; } .display-5 { font-size: 2rem; } .card-body { padding: 15px; } .btn-group { flex-wrap: wrap; } .btn-group .btn { margin-bottom: 5px; } }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item">Analytics</li>
                <li class="breadcrumb-item active">Gross Profit per Master Produk</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Gross Profitper Master Produk</h5>
            </div>
            <div class="card-body">
                <!-- Quick Date Range Filters -->
                <div class="mb-4">
                    <h6 class="mb-2 fw-bold"><i class="bi bi-calendar3 me-2"></i>Filter Cepat:</h6>
                    <div class="btn-group" role="group" aria-label="Quick date filters">
                        <a href="{{ route('analytics.sales-by-master-product', ['quick_range' => '7days'] + request()->except(['start_date', 'end_date', 'quick_range'])) }}" 
                           class="btn {{ request('quick_range') == '7days' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-week me-1"></i> 7 Hari Terakhir
                        </a>
                        <a href="{{ route('analytics.sales-by-master-product', ['quick_range' => '2weeks'] + request()->except(['start_date', 'end_date', 'quick_range'])) }}" 
                           class="btn {{ request('quick_range') == '2weeks' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar2-week me-1"></i> 2 Minggu Terakhir
                        </a>
                        <a href="{{ route('analytics.sales-by-master-product', ['quick_range' => '1month'] + request()->except(['start_date', 'end_date', 'quick_range'])) }}" 
                           class="btn {{ request('quick_range') == '1month' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-month me-1"></i> 1 Bulan Terakhir
                        </a>
                        <a href="{{ route('analytics.sales-by-master-product', ['quick_range' => '3months'] + request()->except(['start_date', 'end_date', 'quick_range'])) }}" 
                           class="btn {{ request('quick_range') == '3months' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar3-range me-1"></i> 3 Bulan Terakhir
                        </a>
                        @if(request('quick_range'))
                            <a href="{{ route('analytics.sales-by-master-product', request()->except(['quick_range', 'start_date', 'end_date'])) }}" 
                               class="btn btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Reset Filter Cepat
                            </a>
                        @endif
                    </div>
                </div>
                
                <!-- Filter Form -->
                <form method="GET" action="{{ route('analytics.sales-by-master-product') }}" id="filter-form" class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-sliders me-2"></i>Filter Custom</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <!-- Date Range -->
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                    value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                    value="{{ $endDate }}">
                            </div>

                            <!-- Platform Filter -->
                            <div class="col-md-3">
                                <label for="platform_id" class="form-label">Platform</label>
                                <select class="form-select" id="platform_id" name="platform_id">
                                    <option value="">Semua Platform</option>
                                    @foreach($platforms as $platform)
                                        <option value="{{ $platform->id }}" 
                                            {{ $selectedPlatform == $platform->id ? 'selected' : '' }}>
                                            {{ $platform->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Search -->
                            <div class="col-md-3">
                                <label for="search" class="form-label">Cari Produk</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                    placeholder="Cari nama atau SKU" value="{{ request('search') }}">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" 
                                        data-bs-target="#advancedFilters">
                                    <i class="bi bi-funnel me-1"></i> Filter Lanjutan
                                </button>
                            </div>
                        </div>
                        
                        <div class="collapse {{ request()->hasAny(['main_categories', 'brands', 'sub_brands', 'product_categories', 'product_types', 'product_sizes', 'product_variants', 'order_number', 'sort']) ? 'show' : '' }}" id="advancedFilters">
                            <div class="row mt-3 g-3">
                                <!-- Order Number Filter -->
                                <div class="col-md-6">
                                    <label for="order_number" class="form-label">Nomor Order</label>
                                    <input type="text" class="form-control" id="order_number" name="order_number" 
                                        placeholder="Masukkan nomor order" value="{{ request('order_number') }}">
                                </div>
                                
                                <!-- Sort Options -->
                                <div class="col-md-6">
                                    <label for="sort" class="form-label">Urutkan Berdasarkan</label>
                                    <select class="form-select" id="sort" name="sort">
                                        <option value="revenue_highest" {{ $sortBy == 'revenue_highest' ? 'selected' : '' }}>
                                            Saldo Masuk Tertinggi
                                        </option>
                                        <option value="revenue_lowest" {{ $sortBy == 'revenue_lowest' ? 'selected' : '' }}>
                                            Saldo Masuk Terendah
                                        </option>
                                        <option value="profit_highest" {{ $sortBy == 'profit_highest' ? 'selected' : '' }}>
                                            Profit Tertinggi
                                        </option>
                                        <option value="profit_lowest" {{ $sortBy == 'profit_lowest' ? 'selected' : '' }}>
                                            Profit Terendah
                                        </option>
                                        <option value="quantity_highest" {{ $sortBy == 'quantity_highest' ? 'selected' : '' }}>
                                            Kuantitas Tertinggi
                                        </option>
                                        <option value="quantity_lowest" {{ $sortBy == 'quantity_lowest' ? 'selected' : '' }}>
                                            Kuantitas Terendah
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Main Categories Filter removed per requirement -->
                                
                                <!-- Brands Filter -->
                                <div class="col-md-6">
                                    <label for="brands" class="form-label">Brand</label>
                                    <select class="form-select" id="brands" name="brands[]" multiple>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" 
                                                {{ in_array($brand->id, $selectedBrands) ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Sub Brands Filter (cascades from Brand) -->
                                <div class="col-md-6">
                                    <label for="sub_brands" class="form-label">Sub Brand</label>
                                    <select class="form-select" id="sub_brands" name="sub_brands[]" multiple {{ empty($selectedBrands) ? 'disabled' : '' }}>
                                        @foreach($subBrands as $subBrand)
                                            <option value="{{ $subBrand->id }}" 
                                                {{ in_array($subBrand->id, $selectedSubBrands) ? 'selected' : '' }}>
                                                {{ $subBrand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Pilih brand terlebih dahulu untuk membuka sub brand</div>
                                </div>
                                
                                <!-- Product Categories Filter -->
                                <div class="col-md-6">
                                    <label for="product_categories" class="form-label">Kategori Produk</label>
                                    <select class="form-select" id="product_categories" name="product_categories[]" multiple {{ empty($selectedSubBrands) ? 'disabled' : '' }}>
                                        @foreach($productCategories as $category)
                                            <option value="{{ $category->id }}" 
                                                {{ in_array($category->id, $selectedProductCategories) ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Pilih sub brand terlebih dahulu</div>
                                </div>
                                
                                <!-- Product Types Filter -->
                                <div class="col-md-6">
                                    <label for="product_types" class="form-label">Tipe Produk</label>
                                    <select class="form-select" id="product_types" name="product_types[]" multiple {{ empty($selectedProductCategories) ? 'disabled' : '' }}>
                                        @foreach($productTypes as $type)
                                            <option value="{{ $type->id }}" 
                                                {{ in_array($type->id, $selectedProductTypes) ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Pilih kategori produk terlebih dahulu</div>
                                </div>
                                
                                <!-- Product Sizes Filter -->
                                <div class="col-md-6">
                                    <label for="product_sizes" class="form-label">Ukuran Produk</label>
                                    <select class="form-select" id="product_sizes" name="product_sizes[]" multiple {{ empty($selectedProductTypes) ? 'disabled' : '' }}>
                                        @foreach($productSizes as $size)
                                            <option value="{{ $size->id }}" 
                                                {{ in_array($size->id, $selectedProductSizes) ? 'selected' : '' }}>
                                                {{ $size->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Pilih tipe produk terlebih dahulu</div>
                                </div>
                                
                                <!-- Product Variants Filter -->
                                <div class="col-md-6">
                                    <label for="product_variants" class="form-label">Varian Produk</label>
                                    <select class="form-select" id="product_variants" name="product_variants[]" multiple {{ empty($selectedProductSizes) ? 'disabled' : '' }}>
                                        @foreach($productVariants as $variant)
                                            <option value="{{ $variant->id }}" 
                                                {{ in_array($variant->id, $selectedProductVariants) ? 'selected' : '' }}>
                                                {{ $variant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Pilih ukuran produk terlebih dahulu</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100" id="filter-submit-btn">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('analytics.sales-by-master-product') }}" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('analytics.sales-by-master-product.export', request()->all()) }}" class="btn btn-success w-100">
                                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                        
                        <!-- Loading indicator -->
                        <div class="row mt-3" id="loading-indicator" style="display: none;">
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Memproses data, mohon tunggu...</p>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Active Filters Display -->
                <div class="bg-light p-3 rounded mb-4 border">
                    <h6 class="fw-bold mb-3"><i class="bi bi-funnel-fill me-2"></i>Filter Aktif:</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-2">
                                <span class="fw-semibold">Periode:</span> 
                                {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                                @if($startDate != $endDate)
                                 - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <span class="fw-semibold">Platform:</span> 
                                @if($selectedPlatform)
                                    @php
                                        $platformName = $platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown';
                                    @endphp
                                    <span class="platform-box platform-{{ strtolower(str_replace(' ', '-', $platformName)) }}">
                                        {{ $platformName }}
                                    </span>
                                @else
                                    Semua Platform
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            @if(request()->hasAny(['start_date', 'end_date', 'platform_id', 'search', 'main_categories', 'brands', 'sub_brands', 'product_categories', 'product_types', 'product_sizes', 'product_variants', 'order_number', 'sort']))
                                <a href="{{ route('analytics.sales-by-master-product') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Reset Semua Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Info about data filtering -->
                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Informasi Data</h5>
                    <p class="mb-0">
                        Data yang ditampilkan mencakup <strong>transaksi yang sudah dibayar</strong> dari 4 platform:
                        <span class="platform-badge platform-shopee">Shopee</span>
                        <span class="platform-badge platform-tokopedia">Tokopedia</span>
                        <span class="platform-badge platform-tiktok">TikTok</span>
                        <span class="platform-badge platform-blibli">Blibli</span>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>Catatan:</strong> Hanya menampilkan order yang memiliki pembayaran (saldo_masuk > 0).
                    </p>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4 g-2">
                    <div class="col">
                        <div class="card bg-primary summary-card h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">Barang Keluar</h6>
                                <h3 class="mb-1">{{ number_format($summary['total_products']) }}</h3>
                                <small class="opacity-75">Jumlah barang keluar dari gudang</small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-success summary-card h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">Total Saldo Masuk</h6>
                                <h4 class="mb-1" style="font-size: 1.1rem; line-height: 1.2;">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</h4>
                                <small class="opacity-75">Uang Masuk</small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-warning summary-card h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">Saldo Masuk - PPN</h6>
                                @php $totalRevenueWithoutPPN = $summary['total_revenue'] / 1.11; @endphp
                                <h4 class="mb-1" style="font-size: 1.1rem; line-height: 1.2;">Rp {{ number_format($totalRevenueWithoutPPN, 0, ',', '.') }}</h4>
                                <small class="opacity-75">Setelah PPN 11%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-info summary-card h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">Total Modal</h6>
                                <h4 class="mb-1" style="font-size: 1.1rem; line-height: 1.2;">Rp {{ number_format($summary['total_capital'], 0, ',', '.') }}</h4>
                                <small class="opacity-75">Modal</small>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-dark summary-card h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">Gross Profit</h6>
                                @php $grossProfitSimple = $totalRevenueWithoutPPN - $summary['total_capital']; $profitMarginSimple = $totalRevenueWithoutPPN > 0 ? ($grossProfitSimple / $totalRevenueWithoutPPN) * 100 : 0; @endphp
                                <h4 class="mb-1" style="font-size: 1.1rem; line-height: 1.2;">Rp {{ number_format($grossProfitSimple, 0, ',', '.') }}</h4>
                                <small class="opacity-75">Margin: {{ number_format($profitMarginSimple, 2) }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if(count($productRows) > 0)
                    <!-- Calculation Method Info -->
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Informasi Perhitungan</h5>
                        <p>
                            <strong>Perhitungan Gross Profit (Sederhana):</strong>
                        </p>
                        <ul>
                            <li><strong>Total Saldo Masuk:</strong> Total uang masuk dari semua order</li>
                            <li><strong>Total Saldo Masuk - PPN:</strong> Total saldo masuk ÷ 1.11 (menghilangkan PPN 11%)</li>
                            <li><strong>Total Modal:</strong> Total biaya modal untuk semua produk</li>
                            <li><strong>Gross Profit:</strong> (Total Saldo Masuk - PPN) - Total Modal</li>
                            <li><strong>Margin:</strong> (Gross Profit ÷ Total Saldo Masuk - PPN) × 100%</li>
                        </ul>
                        <hr>
                        <p class="mb-0">
                            <strong>Contoh Perhitungan Gross Profit:</strong><br>
                            • Total Saldo Masuk: Rp 1.558.100<br>
                            • Total Saldo Masuk - PPN: Rp 1.558.100 ÷ 1.11 = Rp 1.403.693,69<br>
                            • Total Modal: Rp 1.284.323<br>
                            • Gross Profit: Rp 1.403.693,69 - Rp 1.284.323 = Rp 119.370,69<br>
                            • Margin: (Rp 119.370,69 ÷ Rp 1.403.693,69) × 100% = 8,51%
                        </p>
                    </div>
                    
                    <!-- Master Products Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 8%;">Tanggal (Pembayaran Masuk)</th>
                                    <th style="width: 7%;">No Pesanan</th>
                                    <th style="width: 7%;">No Invoice</th>
                                    <th style="min-width: 300px; width: 300px;">Nama Produk (Platform)</th>
                                    <th style="width: 7%;">Variasi (Platform)</th>
                                    <th class="text-end" style="width: 5%;">Jumlah QTY (PCS) (Platform)</th>
                                    <th style="width: 8%;">SKU</th>
                                    <th style="min-width: 300px; width: 300px;">Master Barang</th>
                                    <th class="text-end" style="width: 5%;">QTY</th>
                                    <th class="text-end" style="width: 8%;">Jumlah masuk pembayaran (Rp)</th>
                                    <th class="text-end" style="width: 8%;">Jumlah masuk pembayaran - PPN (Rp)</th>
                                    <th class="text-end" style="width: 8%;">Harga pricelist per item (Rp)</th>
                                    <th class="text-end" style="width: 8%;">Harga pricelist per item X QTY (Rp)</th>
                                    <th class="text-end" style="width: 8%;">Total harga pricelist (Rp)</th>
                                    <th class="text-end" style="width: 7%;">Persen dalam order (%)</th>
                                    <th class="text-end" style="width: 8%;">Masuk pembayaran per produk (Rp)</th>
                                    <th class="text-end" style="width: 8%;">Masuk pembayaran per produk - PPN (Rp)</th>
                                    <th class="text-end" style="width: 8%;">Harga Modal (COGS) (Rp)</th>
                                    <th class="text-end" style="width: 7%;">Profit per PCS (Rp)</th>
                                    <th class="text-end" style="width: 7%;">Gross Profit total (Rp)</th>
                                    <th class="text-end" style="width: 7%;">Margin per pcs (%)</th>
                                    <th class="text-end" style="width: 7%;">Margin per item (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productRows as $row)
                                    @php
                                        // Only determine row class, no calculations
                                        $rowClass = '';
                                        if(($row['profit_per_pcs'] ?? 0) < 0) { $rowClass = 'table-danger'; }
                                        if(($row['price'] ?? 0) == 0) { $rowClass = 'table-warning'; }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td>{{ \Carbon\Carbon::parse($row['order_date'] ?? null)->format('d/m/Y') }}</td>
                                        <td>{{ $row['order_number'] ?? '-' }}</td>
                                        <td>{{ $row['invoice_number'] ?? '-' }}</td>
                                        <td style="min-width: 300px; width: 300px;">{{ $row['platform_product_name'] ?? '-' }}</td>
                                        <td>{{ $row['platform_product_variant'] ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($row['platform_quantity'] ?? 0, 0) }}</td>
                                        <td>{{ $row['sku'] ?? '-' }}</td>
                                        <td style="min-width: 300px; width: 300px;">
                                            <strong>{{ $row['product_name'] ?? '-' }}</strong>
                                        </td>
                                        <td class="text-end">{{ number_format($row['quantity'] ?? 0, 0) }}</td>
                                        <td class="text-end">{{ number_format($row['order_total_payment'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format(($row['order_total_payment'] ?? 0) / 1.11, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($row['price'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($row['pricelist_total'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($row['total_order_value_from_products'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($row['proportion_percent'] ?? 0, 2) }}%</td>
                                        <td class="text-end">{{ number_format($row['payment_per_product_per_pcs'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($row['payment_per_product_without_ppn'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($row['unit_cost'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold {{ ($row['profit_per_pcs'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['profit_per_pcs'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold {{ ($row['gross_profit_total'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['gross_profit_total'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold {{ ($row['margin_per_pcs'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['margin_per_pcs'] ?? 0, 2) }}%</td>
                                        <td class="text-end fw-bold {{ ($row['margin_per_item'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['margin_per_item'] ?? 0, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="8">
                                        <strong>TOTAL ({{ number_format($summary['total_rows']) }} rows, {{ number_format($summary['total_products']) }} barang keluar)</strong>
                                    </td>
                                    <td class="text-end"><strong>{{ number_format($summary['total_quantity']) }}</strong></td>
                                    <td class="text-end"><strong>{{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong></td>
                                    <td class="text-end"><strong>{{ number_format($summary['total_revenue'] / 1.11, 0, ',', '.') }}</strong></td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end"><strong>{{ number_format($summary['total_capital'], 0, ',', '.') }}</strong></td>
                                    <td class="text-end">-</td>
                                    <td class="text-end"><strong>{{ number_format(($summary['total_revenue'] / 1.11) - $summary['total_capital'], 0, ',', '.') }}</strong></td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Menampilkan {{ $productRows->firstItem() ?? 0 }} - {{ $productRows->lastItem() ?? 0 }} dari {{ number_format($summary['total_rows']) }} data ({{ number_format($summary['total_products']) }} barang keluar)
                        </div>
                        <div>
                            {{ $productRows->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        @if(request('page') && request('page') > 1)
                            Tidak ada data pada halaman {{ request('page') }}. 
                            <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}" class="alert-link">Kembali ke halaman 1</a>
                        @else
                            Tidak ada data yang tersedia untuk kriteria filter yang dipilih.
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Calculation Details Modal -->
    <div class="modal fade" id="calculationModal" tabindex="-1" aria-labelledby="calculationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="calculationModalLabel">
                        <i class="bi bi-calculator me-2"></i>Detail Perhitungan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Product Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary">Informasi Produk</h6>
                            <table class="table table-sm">
                                <tr><td width="30%"><strong>SKU:</strong></td><td id="modal-sku"></td></tr>
                                <tr><td><strong>Nama Produk:</strong></td><td id="modal-product-name"></td></tr>
                                <tr><td><strong>Nama di Platform:</strong></td><td id="modal-platform-name"></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Informasi Order</h6>
                            <table class="table table-sm">
                                <tr><td width="30%"><strong>No. Order:</strong></td><td id="modal-order-number"></td></tr>
                                <tr><td><strong>Platform:</strong></td><td id="modal-platform"></td></tr>
                                <tr><td><strong>Tanggal:</strong></td><td id="modal-order-date"></td></tr>
                            </table>
                        </div>
                    </div>

                    <!-- Calculation Details -->
                    <h6 class="text-primary">Perhitungan Finansial</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Nilai</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><strong>QTY (Platform)</strong></td><td class="text-end" id="modal-platform-qty"></td><td class="small text-muted">Jumlah yang diorder di platform</td></tr>
                                <tr><td><strong>QTY (Master Barang)</strong></td><td class="text-end" id="modal-qty"></td><td class="small text-muted">Platform order qty × mapping qty</td></tr>
                                <tr><td><strong>Jumlah Masuk Pembayaran</strong></td><td class="text-end" id="modal-payment-amount"></td><td class="small text-muted">Total saldo masuk dari order</td></tr>
                                <tr><td><strong>Jumlah Masuk Pembayaran - PPN</strong></td><td class="text-end" id="modal-payment-amount-no-ppn"></td><td class="small text-muted">Jumlah masuk pembayaran ÷ 1.11</td></tr>
                                <tr class="table-info"><td><strong>% Distribusi Revenue</strong></td><td class="text-end" id="modal-proportion"></td><td class="small text-muted">Proporsi nilai produk dalam total order</td></tr>
                                <tr><td><strong>Masuk Pembayaran per Produk</strong></td><td class="text-end" id="modal-payment-per-product"></td><td class="small text-muted">(Jumlah masuk pembayaran × % distribusi) ÷ QTY master barang</td></tr>
                                <tr><td><strong>Masuk Pembayaran per Produk - PPN</strong></td><td class="text-end" id="modal-payment-per-product-no-ppn"></td><td class="small text-muted">Masuk pembayaran per produk ÷ 1.11</td></tr>
                                <tr><td><strong>Harga Modal (COGS)</strong></td><td class="text-end" id="modal-capital-per-unit"></td><td class="small text-muted">Total capital ÷ quantity</td></tr>
                                <tr class="table-success"><td><strong>Profit per PCS</strong></td><td class="text-end" id="modal-profit-per-pcs"></td><td class="small text-muted">Masuk pembayaran produk-PPN - harga modal</td></tr>
                                <tr class="table-warning"><td><strong>Gross Profit Total</strong></td><td class="text-end" id="modal-profit-total"></td><td class="small text-muted">Profit per PCS × QTY master produk</td></tr>
                                <tr class="table-info"><td><strong>Margin per PCS (%)</strong></td><td class="text-end" id="modal-margin-per-pcs"></td><td class="small text-muted">(Profit per PCS ÷ Masuk pembayaran produk-PPN) × 100%</td></tr>
                                <tr class="table-info"><td><strong>Margin per Item (%)</strong></td><td class="text-end" id="modal-margin-per-item"></td><td class="small text-muted">(Gross Profit Total ÷ (Masuk pembayaran produk-PPN × QTY master produk)) × 100%</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Formula Details -->
                    <div class="mt-4">
                        <h6 class="text-primary">Formula Perhitungan</h6>
                        <div class="bg-light p-3 rounded">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Saldo Masuk:</strong></p>
                                    <code>Total saldo masuk order × (% distribusi ÷ 100)</code>
                                    
                                    <p class="mb-1 mt-3"><strong>Gross Profit per Unit:</strong></p>
                                    <code>(Saldo masuk ÷ quantity) - modal per unit</code>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>% Distribusi Revenue:</strong></p>
                                    <code>(Nilai produk ÷ total nilai order) × 100%</code>
                                    
                                    <p class="mb-1 mt-3"><strong>Gross Profit Total:</strong></p>
                                    <code>Profit per PCS × QTY master produk</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- TomSelect JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filter-form');
            const loadingIndicator = document.getElementById('loading-indicator');
            const submitBtn = document.getElementById('filter-submit-btn');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    loadingIndicator.style.display = 'block';
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
                    loadingIndicator.scrollIntoView({ behavior: 'smooth' });
                });
            }
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            if (startDateInput && !startDateInput.value) { startDateInput.value = new Date().toISOString().split('T')[0]; }
            if (endDateInput && !endDateInput.value) { endDateInput.value = new Date().toISOString().split('T')[0]; }
            const multiSelects = ['brands','sub_brands','product_categories','product_types','product_sizes','product_variants'];
            multiSelects.forEach(function(selectId) { if (document.getElementById(selectId)) { new TomSelect('#' + selectId, { plugins: ['remove_button'], maxItems: null, placeholder: 'Pilih ' + selectId.replace(/_/g, ' '), allowEmptyOption: true }); } });
            const brandSelect = document.getElementById('brands');
            const subBrandSelect = document.getElementById('sub_brands');
            let subBrandTS = null; if (subBrandSelect && window.TomSelect) { subBrandTS = subBrandSelect.tomselect; }
            function resetField(selectEl, ts) { if (!selectEl) return; selectEl.setAttribute('disabled', 'disabled'); if (ts) { ts.clear(true); ts.clearOptions(); if (!ts.isDisabled) ts.disable(); ts.refreshOptions(false); } else { selectEl.innerHTML = ''; } }
            function updateSubBrands() {
                const selectedBrands = brandSelect?.tomselect ? (brandSelect.tomselect.items || []) : Array.from(brandSelect?.selectedOptions || []).map(o => o.value);
                resetField(subBrandSelect, subBrandTS);
                resetField(categorySelect, categorySelect?.tomselect);
                resetField(typeSelect, typeTS);
                resetField(sizeSelect, sizeTS);
                resetField(variantSelect, variantTS);
                if (!selectedBrands.length) { return; }
                subBrandSelect.removeAttribute('disabled'); if (subBrandTS && subBrandTS.isDisabled) subBrandTS.enable();
                fetch(`{{ route('analytics.get-subbrands') }}?` + new URLSearchParams({ brand_ids: selectedBrands }))
                    .then(r => r.json())
                    .then(items => {
                        if (subBrandTS) { subBrandTS.clear(true); subBrandTS.clearOptions(); }
                        subBrandSelect.innerHTML = '';
                        items.forEach(it => { const opt = document.createElement('option'); opt.value = it.id; opt.textContent = it.name; subBrandSelect.appendChild(opt); });
                        if (subBrandTS) { subBrandTS.addOptions(items.map(it => ({ value: it.id, text: it.name }))); subBrandTS.refreshOptions(false); }
                    });
            }
            if (brandSelect) { brandSelect.addEventListener('change', updateSubBrands); const hasBrand = brandSelect?.tomselect ? (brandSelect.tomselect.items || []).length > 0 : Array.from(brandSelect.selectedOptions).length > 0; if (!hasBrand) { subBrandSelect?.setAttribute('disabled', 'disabled'); } else { updateSubBrands(); } }
            const categorySelect = document.getElementById('product_categories');
            const typeSelect = document.getElementById('product_types');
            const sizeSelect = document.getElementById('product_sizes');
            const variantSelect = document.getElementById('product_variants');
            const typeTS = typeSelect?.tomselect || null;
            const sizeTS = sizeSelect?.tomselect || null;
            const variantTS = variantSelect?.tomselect || null;
            const subBrandTS2 = subBrandSelect?.tomselect || null;
            function updateCategories() { const ids = Array.from(subBrandSelect?.selectedOptions || []).map(o => o.value); if (!ids.length) { categorySelect.setAttribute('disabled', 'disabled'); if (categorySelect.tomselect) { categorySelect.tomselect.clearOptions(); categorySelect.tomselect.disable(); categorySelect.tomselect.refreshOptions(false); } return; } categorySelect.removeAttribute('disabled'); if (categorySelect.tomselect && categorySelect.tomselect.isDisabled) { categorySelect.tomselect.enable(); } fetch(`{{ route('analytics.get-product-categories') }}?` + new URLSearchParams({ sub_brand_ids: ids })) .then(r => r.json()).then(items => { if (categorySelect.tomselect) { categorySelect.tomselect.clear(true); categorySelect.tomselect.clearOptions(); } categorySelect.innerHTML = ''; items.forEach(it => { const opt = document.createElement('option'); opt.value = it.id; opt.textContent = it.name; categorySelect.appendChild(opt); }); if (categorySelect.tomselect) { categorySelect.tomselect.addOptions(items.map(it => ({ value: it.id, text: it.name }))); categorySelect.tomselect.refreshOptions(false); } updateTypes(); }); }
            subBrandSelect?.addEventListener('change', updateCategories); if (subBrandSelect?.tomselect) { subBrandSelect.tomselect.on('change', updateCategories); }
            function updateTypes() { const ids = categorySelect?.tomselect ? (categorySelect.tomselect.items || []) : Array.from(categorySelect?.selectedOptions || []).map(o => o.value); if (!ids.length) { typeSelect.setAttribute('disabled', 'disabled'); if (typeTS) { typeTS.clearOptions(); typeTS.disable(); typeTS.refreshOptions(false); } sizeSelect.setAttribute('disabled', 'disabled'); if (sizeTS) { sizeTS.clearOptions(); sizeTS.disable(); sizeTS.refreshOptions(false); } variantSelect.setAttribute('disabled', 'disabled'); if (variantTS) { variantTS.clearOptions(); variantTS.disable(); variantTS.refreshOptions(false); } return; } typeSelect.removeAttribute('disabled'); if (typeTS && typeTS.isDisabled) typeTS.enable(); fetch(`{{ route('analytics.get-product-types') }}?` + new URLSearchParams({ category_ids: ids })) .then(r => r.json()).then(items => { if (typeTS) { typeTS.clearOptions(); } typeSelect.innerHTML = ''; items.forEach(it => { const opt = document.createElement('option'); opt.value = it.id; opt.textContent = it.name; typeSelect.appendChild(opt); }); if (typeTS) { typeTS.addOptions(items.map(it => ({ value: it.id, text: it.name }))); typeTS.refreshOptions(false); } updateSizes(); }); }
            function updateSizes() { const ids = typeSelect?.tomselect ? (typeSelect.tomselect.items || []) : Array.from(typeSelect?.selectedOptions || []).map(o => o.value); if (!ids.length) { sizeSelect.setAttribute('disabled', 'disabled'); if (sizeTS) { sizeTS.clearOptions(); sizeTS.disable(); sizeTS.refreshOptions(false); } variantSelect.setAttribute('disabled', 'disabled'); if (variantTS) { variantTS.clearOptions(); variantTS.disable(); variantTS.refreshOptions(false); } return; } sizeSelect.removeAttribute('disabled'); if (sizeTS && sizeTS.isDisabled) sizeTS.enable(); fetch(`{{ route('analytics.get-product-sizes') }}?` + new URLSearchParams({ type_ids: ids })) .then(r => r.json()).then(items => { if (sizeTS) { sizeTS.clearOptions(); } sizeSelect.innerHTML = ''; items.forEach(it => { const opt = document.createElement('option'); opt.value = it.id; opt.textContent = it.name; sizeSelect.appendChild(opt); }); if (sizeTS) { sizeTS.addOptions(items.map(it => ({ value: it.id, text: it.name }))); sizeTS.refreshOptions(false); } updateVariants(); }); }
            function updateVariants() { const ids = sizeSelect?.tomselect ? (sizeSelect.tomselect.items || []) : Array.from(sizeSelect?.selectedOptions || []).map(o => o.value); if (!ids.length) { variantSelect.setAttribute('disabled', 'disabled'); if (variantTS) { variantTS.clearOptions(); variantTS.disable(); variantTS.refreshOptions(false); } return; } variantSelect.removeAttribute('disabled'); if (variantTS && variantTS.isDisabled) variantTS.enable(); fetch(`{{ route('analytics.get-product-variants') }}?` + new URLSearchParams({ size_ids: ids })) .then(r => r.json()).then(items => { if (variantTS) { variantTS.clearOptions(); } variantSelect.innerHTML = ''; items.forEach(it => { const opt = document.createElement('option'); opt.value = it.id; opt.textContent = it.name; variantSelect.appendChild(opt); }); if (variantTS) { variantTS.addOptions(items.map(it => ({ value: it.id, text: it.name }))); variantTS.refreshOptions(false); } }); }
            categorySelect?.addEventListener('change', updateTypes); if (categorySelect?.tomselect) { categorySelect.tomselect.on('change', updateTypes); } typeSelect?.addEventListener('change', updateSizes); if (typeSelect?.tomselect) { typeSelect.tomselect.on('change', updateSizes); } sizeSelect?.addEventListener('change', updateVariants); if (sizeSelect?.tomselect) { sizeSelect.tomselect.on('change', updateVariants); }
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });
            const todayFormatted = (new Date()).toISOString().split('T')[0];
            if (!startDateInput.value) { startDateInput.value = todayFormatted; }
            if (!endDateInput.value) { endDateInput.value = todayFormatted; }
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('start_date') && !urlParams.has('end_date') && !document.referrer.includes('sales-by-master-product')) { document.getElementById('filter-form').submit(); }
        });
        function showCalculationDetails(sku, productName, orderNumber, qty, price, capital, proportionPercent, revenue, grossProfitPerUnit, grossProfitTotal, platformProductName, platform, orderDate) {
            document.getElementById('modal-sku').textContent = sku || '-';
            document.getElementById('modal-product-name').textContent = productName || '-';
            document.getElementById('modal-platform-name').textContent = platformProductName || '-';
            document.getElementById('modal-order-number').textContent = orderNumber || '-';
            document.getElementById('modal-platform').innerHTML = platform ? '<span class="platform-box platform-' + platform.toLowerCase().replace(' ', '-') + '">' + platform + '</span>' : '-';
            if (orderDate && orderDate !== '') { const date = new Date(orderDate); document.getElementById('modal-order-date').textContent = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); } else { document.getElementById('modal-order-date').textContent = '-'; }
            const capitalPerUnit = qty > 0 ? capital / qty : 0; const paymentAmount = revenue; const paymentAmountWithoutPPN = paymentAmount / 1.11; const paymentPerProduct = revenue; const paymentPerProductPerPcs = qty > 0 ? revenue / qty : 0; const paymentPerProductWithoutPPN = paymentPerProductPerPcs / 1.11; const profitPerPCS = paymentPerProductWithoutPPN - capitalPerUnit; const grossProfitTotalCorrected = profitPerPCS * qty; const marginPerPCS = paymentPerProductWithoutPPN > 0 ? (profitPerPCS / paymentPerProductWithoutPPN) * 100 : 0; const marginPerItem = (paymentPerProductWithoutPPN * qty) > 0 ? (grossProfitTotalCorrected / (paymentPerProductWithoutPPN * qty)) * 100 : 0;
            const formatRupiah = (number) => { return 'Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number); };
            const formatPercent = (number) => { return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number) + '%'; };
            document.getElementById('modal-platform-qty').textContent = new Intl.NumberFormat('id-ID').format(qty);
            document.getElementById('modal-qty').textContent = new Intl.NumberFormat('id-ID').format(qty);
            document.getElementById('modal-payment-amount').textContent = formatRupiah(paymentAmount);
            document.getElementById('modal-payment-amount-no-ppn').textContent = formatRupiah(paymentAmountWithoutPPN);
            document.getElementById('modal-proportion').textContent = formatPercent(proportionPercent);
            document.getElementById('modal-payment-per-product').textContent = formatRupiah(paymentPerProductPerPcs);
            document.getElementById('modal-payment-per-product-no-ppn').textContent = formatRupiah(paymentPerProductWithoutPPN);
            document.getElementById('modal-capital-per-unit').textContent = formatRupiah(capitalPerUnit);
            document.getElementById('modal-profit-per-pcs').textContent = formatRupiah(profitPerPCS);
            document.getElementById('modal-profit-total').textContent = formatRupiah(grossProfitTotalCorrected);
            document.getElementById('modal-margin-per-pcs').textContent = formatPercent(marginPerPCS);
            document.getElementById('modal-margin-per-item').textContent = formatPercent(marginPerItem);
            const profitPerPcsEl = document.getElementById('modal-profit-per-pcs');
            const profitTotalEl = document.getElementById('modal-profit-total');
            const marginPerPcsEl = document.getElementById('modal-margin-per-pcs');
            const marginPerItemEl = document.getElementById('modal-margin-per-item');
            profitPerPcsEl.className = 'text-end fw-bold ' + (profitPerPCS < 0 ? 'text-danger' : 'text-success');
            profitTotalEl.className = 'text-end fw-bold ' + (grossProfitTotalCorrected < 0 ? 'text-danger' : 'text-success');
            marginPerPcsEl.className = 'text-end fw-bold ' + (marginPerPCS < 0 ? 'text-danger' : 'text-success');
            marginPerItemEl.className = 'text-end fw-bold ' + (marginPerItem < 0 ? 'text-danger' : 'text-success');
        }
    </script>
</body>
</html>


