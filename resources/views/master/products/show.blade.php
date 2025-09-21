@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Detail Produk</h6>
                    <div>
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
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
                                            @if($product->initial_price)
                                                Rp {{ number_format($product->initial_price, 0, ',', '.') }}
                                            @else
                                                <span class="text-muted">Tidak ada</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Diskon (%)</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            @if($product->discount_percentage)
                                                {{ number_format($product->discount_percentage, 1) }}%
                                            @else
                                                <span class="text-muted">Tidak ada</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Harga Akhir</div>
                                        <div class="col-md-8 text-sm font-weight-bold text-success">
                                            @if($product->initial_price)
                                                @php
                                                    $finalPrice = $product->initial_price;
                                                    if($product->discount_percentage > 0) {
                                                        $finalPrice = $product->initial_price * (1 - $product->discount_percentage / 100);
                                                    }
                                                @endphp
                                                Rp {{ number_format($finalPrice, 0, ',', '.') }}
                                            @else
                                                <span class="text-muted">Tidak ada</span>
                                            @endif
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
@endsection 