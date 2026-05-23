@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">
                <div class="card-header bg-gradient-light d-flex justify-content-between align-items-center py-3 px-4">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-tags me-2"></i>Harga Awal (Versi)
                    </h5>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="fas fa-boxes me-1"></i> Data Produk
                    </a>
                </div>

                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="bg-light rounded-3 p-3 mb-4">
                        <form action="{{ route('products.initial-price.index') }}" method="GET" class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group input-group-seamless">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-3">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control ps-0 border-start-0 rounded-end-3" placeholder="Cari nama, SKU, atau barcode..." name="search" value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-4 d-flex">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill shadow-sm me-2 flex-grow-1">
                                    <i class="fas fa-filter me-1"></i> Cari
                                </button>
                                <a href="{{ route('products.initial-price.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                                    <i class="fas fa-redo-alt"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive border rounded-3 shadow-sm overflow-hidden">
                        <table class="table align-items-center mb-0 table-hover">
                            <thead class="bg-light">
                                <tr class="bg-white">
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 6%;">No.</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Nama Produk</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 12%;">SKU</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 14%;">Harga Awal Aktif</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 10%;">Versi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center" style="width: 12%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $index => $product)
                                    @php
                                        $active = $product->latestInitialPriceVersion;
                                        $activePrice = $active?->initial_price ?? ($product->initial_price ?? 0);
                                        $activeVersion = $active?->version ?? 1;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <p class="text-xs font-weight-bold mb-0">{{ $products->firstItem() + $index }}</p>
                                        </td>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm fw-semibold text-wrap">{{ $product->name }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-xs font-weight-bold text-wrap">
                                                {{ $product->sku ?? 'Tidak ada' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-xs font-weight-bold">
                                                Rp {{ number_format((float) $activePrice, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-primary">{{ $activeVersion }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('products.initial-price.show', $product->id) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                                <i class="fas fa-cog me-1"></i> Kelola
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="empty-state mb-3">
                                                    <i class="fas fa-tags fa-4x text-secondary opacity-50"></i>
                                                </div>
                                                <h6 class="fw-normal mb-1">Tidak ada data</h6>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center px-2 pt-4">
                        <p class="text-sm text-muted mb-0">
                            Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} data
                        </p>
                        <div class="pagination-container">
                            {{ $products->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .pagination-container .pagination {
        margin-bottom: 0;
    }
    
    .pagination .page-item .page-link {
        color: #5e72e4;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        margin: 0 2px;
        transition: all 0.2s ease;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #5e72e4;
        border-color: #5e72e4;
        color: white;
        box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
    }
    
    .pagination .page-item .page-link:hover {
        background-color: #f6f9fc;
        transform: translateY(-1px);
    }
    
    .pagination .page-item.active .page-link:hover {
        background-color: #5e72e4;
        transform: none;
    }
</style>
@endsection
