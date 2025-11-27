@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="fw-bold mb-2">Pilih Platform Keuangan</h1>
            <p class="text-muted mb-0">Pilih platform untuk melihat dan mengelola data keuangan</p>
        </div>
    </div>
    <div class="row justify-content-center g-4">
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <img src="{{ asset('images/logo/shopee.png') }}" alt="Shopee" class="img-fluid" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x80?text=Shopee'">
                    </div>
                    <h5 class="card-title fw-bold">Shopee Lamourad</h5>
                    <p class="card-text text-muted">Kelola data keuangan platform Shopee</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.shopee.index') }}" class="btn btn-primary">
                            <i class="fas fa-money-bill me-1"></i> Kelola Keuangan
                        </a>
                        <a href="{{ route('finance.shopee.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Import Data
                        </a>
                        <a href="{{ route('finance.aruskasshopee.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-line me-1"></i> Arus Kas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <img src="{{ asset('images/logo/tokopedia.png') }}" alt="Tokopedia" class="img-fluid" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x80?text=Tokopedia'">
                    </div>
                    <h5 class="card-title fw-bold">Tokopedia</h5>
                    <p class="card-text text-muted">Kelola data keuangan platform Tokopedia</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.tokopedia.index') }}" class="btn btn-success">
                            <i class="fas fa-money-bill me-1"></i> Kelola Keuangan
                        </a>
                        <a href="{{ route('finance.tokopedia.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Import Data
                        </a>
                        <a href="{{ route('finance.aruskastokopedia.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-line me-1"></i> Arus Kas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <img src="{{ asset('images/logo/tiktok.png') }}" alt="Tiktok" class="img-fluid" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x80?text=TikTok'">
                    </div>
                    <h5 class="card-title fw-bold">Tiktok Lamourad</h5>
                    <p class="card-text text-muted">Kelola data keuangan platform TikTok</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.tiktok.index') }}" class="btn btn-dark">
                            <i class="fas fa-money-bill me-1"></i> Kelola Keuangan
                        </a>
                        <a href="{{ route('finance.tiktok.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Import Data
                        </a>
                        <a href="{{ route('finance.aruskastiktok.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-line me-1"></i> Arus Kas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <img src="{{ asset('images/logo/blibli.png') }}" alt="Blibli" class="img-fluid" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x80?text=Blibli'">
                    </div>
                    <h5 class="card-title fw-bold">Blibli</h5>
                    <p class="card-text text-muted">Kelola data keuangan platform Blibli</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.blibli.index') }}" class="btn btn-info">
                            <i class="fas fa-money-bill me-1"></i> Kelola Keuangan
                        </a>
                        <a href="{{ route('finance.blibli.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Import Data
                        </a>
                        <a href="{{ route('finance.aruskasblibli.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-line me-1"></i> Arus Kas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <img src="{{ asset('images/logo/lazada.png') }}" alt="Lazada" class="img-fluid" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x80?text=Lazada'">
                    </div>
                    <h5 class="card-title fw-bold">Lazada Lamourad</h5>
                    <p class="card-text text-muted">Kelola data keuangan platform Lazada</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.lazada.index') }}" class="btn btn-danger">
                            <i class="fas fa-money-bill me-1"></i> Kelola Keuangan
                        </a>
                        <a href="{{ route('finance.lazada.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Import Data
                        </a>
                        <a href="#" class="btn btn-outline-info disabled">
                            <i class="fas fa-chart-line me-1"></i> Arus Kas (Coming Soon)
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <img src="{{ asset('images/logo/shopee.png') }}" alt="Shopee2" class="img-fluid" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x80?text=Shopee2'">
                    </div>
                    <h5 class="card-title fw-bold">Shopee Trubleu</h5>
                    <p class="card-text text-muted">Kelola data keuangan platform Shopee2</p>
                    <div class="d-grid gap-2">
                    <a href="{{ route('finance.shopee2.index') }}" class="btn btn-primary">
                        <i class="fas fa-money-bill me-1"></i> Kelola Keuangan
                    </a>
                    <a href="{{ route('finance.shopee2.import') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-file-import me-1"></i> Import Data
                    </a>
                    <a href="{{ route('finance.aruskasshopee2.index') }}" class="btn btn-outline-info">
                        <i class="fas fa-chart-line me-1"></i> Arus Kas
                    </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <img src="{{ asset('images/logo/tiktok.png') }}" alt="TikTok2" class="img-fluid" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x80?text=TikTok2'">
                    </div>
                    <h5 class="card-title fw-bold">Tiktok Trubleu</h5>
                    <p class="card-text text-muted">Kelola data keuangan platform TikTok2</p>
                    <div class="d-grid gap-2">
                    <a href="{{ route('finance.tiktok2.index') }}" class="btn btn-dark">
                        <i class="fas fa-money-bill me-1"></i> Kelola Keuangan
                    </a>
                    <a href="{{ route('finance.tiktok2.import') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-file-import me-1"></i> Import Data
                    </a>
                    <a href="{{ route('finance.aruskastiktok2.index') }}" class="btn btn-outline-info">
                        <i class="fas fa-chart-line me-1"></i> Arus Kas
                    </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 shadow-sm border-0 hover-card bg-warning bg-opacity-10">
                <div class="card-body text-center p-4">
                    <div class="platform-icon mb-3">
                        <i class="fas fa-exclamation-circle fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title fw-bold text-warning">Order Belum Ada Pembayaran</h5>
                    <p class="card-text text-muted">Lihat dan export semua order yang belum ada pembayaran di semua platform</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.unpaid-orders.index') }}" class="btn btn-warning text-white">
                            <i class="fas fa-search-dollar me-1"></i> Lihat Order Belum Bayar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-card {
        transition: transform 0.3s ease;
    }
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .platform-icon img, .platform-icon i {
        margin-bottom: 8px;
    }
    .card-title {
        font-size: 1.2rem;
    }
    .card-text {
        min-height: 40px;
    }
</style>
@endsection