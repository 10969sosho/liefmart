@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle text-uppercase text-muted fs-sm fw-semibold">Overview</div>
            <h2 class="page-title fw-bold">Dashboard <span class="badge bg-primary-subtle text-primary ms-2 fs-sm">{{ session('main_category_name') }}</span></h2>
        </div>
        <div class="col-auto ms-auto">
            <div class="btn-group">
                <span class="btn btn-light d-inline-flex align-items-center gap-1 py-1">
                    <i class="fas fa-calendar text-muted"></i>
                    <span class="d-none d-md-inline">{{ now()->format('F Y') }}</span>
                </span>
                <button class="btn btn-primary d-inline-flex align-items-center gap-1 py-1">
                    <i class="fas fa-file-export"></i>
                    <span class="d-none d-md-inline">Export</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Platform-wise Sales Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="stat-icon rounded d-flex align-items-center justify-content-center">
                            <i class="fas fa-shopping-bag text-secondary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Shopee Sales</h6>
                        <h3 class="card-title fw-bold mb-0">Rp {{ number_format($shopeeSales ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success-subtle text-success me-2 d-flex align-items-center">
                        <i class="fas fa-arrow-up me-1"></i> {{ $shopeeGrowth ?? 0 }}%
                    </span>
                    <span class="text-muted small">vs previous month</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="stat-icon rounded d-flex align-items-center justify-content-center">
                            <i class="fas fa-store text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Tokopedia Sales</h6>
                        <h3 class="card-title fw-bold mb-0">Rp {{ number_format($tokopediaSales ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success-subtle text-success me-2 d-flex align-items-center">
                        <i class="fas fa-arrow-up me-1"></i> {{ $tokopediaGrowth ?? 0 }}%
                    </span>
                    <span class="text-muted small">vs previous month</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="stat-icon rounded d-flex align-items-center justify-content-center">
                            <i class="fas fa-music text-accent"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">TikTok Sales</h6>
                        <h3 class="card-title fw-bold mb-0">Rp {{ number_format($tiktokSales ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success-subtle text-success me-2 d-flex align-items-center">
                        <i class="fas fa-arrow-up me-1"></i> {{ $tiktokGrowth ?? 0 }}%
                    </span>
                    <span class="text-muted small">vs previous month</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="stat-icon rounded d-flex align-items-center justify-content-center">
                            <i class="fas fa-shopping-cart text-warning"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Blibli Sales</h6>
                        <h3 class="card-title fw-bold mb-0">Rp {{ number_format($blibliSales ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success-subtle text-success me-2 d-flex align-items-center">
                        <i class="fas fa-arrow-up me-1"></i> {{ $blibliGrowth ?? 0 }}%
                    </span>
                    <span class="text-muted small">vs previous month</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="stat-icon rounded d-flex align-items-center justify-content-center">
                            <i class="fas fa-store-alt text-success"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted text-uppercase fs-xs fw-semibold mb-1">Offline Sales</h6>
                        <h3 class="card-title fw-bold mb-0">Rp {{ number_format($offlineSales ?? 0, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success-subtle text-success me-2 d-flex align-items-center">
                        <i class="fas fa-arrow-up me-1"></i> {{ $offlineGrowth ?? 0 }}%
                    </span>
                    <span class="text-muted small">vs previous month</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Financial Overview -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 h-100">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0 fw-semibold">Financial Overview</h5>
                    <p class="card-subtitle text-muted mb-0 fs-sm">Monthly revenue across platforms</p>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light" type="button" id="chartOptions" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chartOptions">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-download me-2"></i>Export</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-print me-2"></i>Print</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-sync me-2"></i>Refresh</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <!-- Chart will be populated with real data -->
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 h-100">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0 fw-semibold">Platform Performance</h5>
                <p class="card-subtitle text-muted mb-0 fs-sm">Sales distribution by platform</p>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <!-- Chart will be populated with real data -->
                    <canvas id="platformPieChart"></canvas>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-secondary me-2" style="width: 12px; height: 12px;"></span>
                            <span class="text-muted small">Shopee</span>
                        </div>
                        <span class="fw-semibold">{{ $shopeePercentage ?? 0 }}%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2" style="width: 12px; height: 12px;"></span>
                            <span class="text-muted small">Tokopedia</span>
                        </div>
                        <span class="fw-semibold">{{ $tokopediaPercentage ?? 0 }}%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-accent me-2" style="width: 12px; height: 12px;"></span>
                            <span class="text-muted small">TikTok</span>
                        </div>
                        <span class="fw-semibold">{{ $tiktokPercentage ?? 0 }}%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-warning me-2" style="width: 12px; height: 12px;"></span>
                            <span class="text-muted small">Blibli</span>
                        </div>
                        <span class="fw-semibold">{{ $blibliPercentage ?? 0 }}%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2" style="width: 12px; height: 12px;"></span>
                            <span class="text-muted small">Offline Sales</span>
                        </div>
                        <span class="fw-semibold">{{ $offlinePercentage ?? 0 }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions & Stock Alerts -->
<div class="row g-3">
    <div class="col-lg-12">
        <div class="card border-0">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0 fw-semibold">Stock Alerts</h5>
                    <p class="card-subtitle text-muted mb-0 fs-sm">Products with low stock</p>
                </div>
                <a href="#" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($lowStockProducts ?? [] as $product)
                    <li class="list-group-item py-3 px-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="product-icon rounded d-flex align-items-center justify-content-center bg-light"
                                     style="width: 45px; height: 45px;">
                                    <i class="fas fa-box text-warning"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0 fw-semibold">{{ $product->name }}</h6>
                                        <p class="text-muted mb-0 small">SKU: {{ $product->sku }}</p>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger">{{ $product->stock }} left</span>
                                </div>
                                <div class="progress mt-2" style="height: 5px">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: {{ ($product->stock / $product->min_stock) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels ?? []) !!},
            datasets: [{
                label: 'Total Revenue',
                data: {!! json_encode($chartData ?? []) !!},
                borderColor: '#8799ff',
                backgroundColor: 'rgba(135, 153, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // Platform Distribution Chart
    const platformCtx = document.getElementById('platformPieChart').getContext('2d');
    new Chart(platformCtx, {
        type: 'doughnut',
        data: {
            labels: ['Shopee', 'Tokopedia', 'TikTok', 'Blibli'],
            datasets: [{
                data: [
                    {{ $shopeePercentage ?? 0 }},
                    {{ $tokopediaPercentage ?? 0 }},
                    {{ $tiktokPercentage ?? 0 }},
                    {{ $blibliPercentage ?? 0 }}
                ],
                backgroundColor: [
                    '#ff91e8',
                    '#8799ff',
                    '#ff7eb9',
                    '#ffcd5c'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '70%'
        }
    });
});
</script>
@endpush

@endsection
