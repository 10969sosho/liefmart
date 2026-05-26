@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">
                <div class="card-header bg-gradient-light d-flex justify-content-between align-items-center py-3 px-4">
                    <div>
                        <h5 class="mb-0 fw-semibold text-primary">
                            <i class="fas fa-eye me-2"></i>Detail Produk
                        </h5>
                        <div class="text-muted text-sm mt-1">
                            {{ $product->name }} • {{ $product->sku ?? '-' }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('products.initial-price.show', $product->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="fas fa-tag me-1"></i> Harga Awal
                        </a>
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-outline-info btn-sm rounded-pill px-3 shadow-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card card-plain">
                                <div class="card-header pb-0">
                                    <h6 class="text-uppercase text-primary">Informasi Produk</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">ID</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $product->id }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Nama</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $product->name }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">SKU</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $product->sku ?? 'Tidak ada' }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Barcode</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $product->barcode ?? '-' }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Status</div>
                                        <div class="col-md-8">
                                            <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $product->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Harga Awal</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            Rp {{ number_format($product->initial_price ?? 0, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Diskon (%)</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ number_format($product->discount_percentage ?? 0, 1) }}%
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Harga Akhir</div>
                                        <div class="col-md-8 text-sm font-weight-bold text-success">
                                            @php
                                                $initialPrice = $product->initial_price ?? 0;
                                                $discountPercentage = $product->discount_percentage ?? 0;
                                                $finalPrice = $initialPrice;
                                                if($discountPercentage > 0) {
                                                    $finalPrice = $initialPrice * (1 - $discountPercentage / 100);
                                                }
                                            @endphp
                                            Rp {{ number_format($finalPrice, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Dibuat pada</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $product->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Diperbarui pada</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $product->updated_at->format('d M Y H:i') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card card-plain">
                                <div class="card-header pb-0">
                                    <h6 class="text-uppercase text-primary">Klasifikasi Produk</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Main Category</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $product->mainCategory->name ?? 'Tidak ada' }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Brand</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $product->brand->name ?? 'Tidak ada' }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Sub Brand</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $product->subBrand->name ?? 'Tidak ada' }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Kategori</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $product->productCategory->name ?? 'Tidak ada' }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Tipe</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $product->productType->name ?? 'Tidak ada' }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Ukuran</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $product->productSize->name ?? 'Tidak ada' }} 
                                            @if($product->productSize && $product->productSize->code)
                                                ({{ $product->productSize->code }})
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Varian</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $product->productVariant->name ?? 'Tidak ada' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="card card-plain">
                                <div class="card-header pb-0">
                                    <h6 class="text-uppercase text-primary">Deskripsi</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <p class="text-sm">{{ $product->description ?: 'Tidak ada deskripsi' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-light {
        background: linear-gradient(to right, #f8f9fa, #fff);
    }
</style>
@endsection 
