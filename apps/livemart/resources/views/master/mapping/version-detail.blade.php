@extends('layouts.app')

@section('title', 'Detail Perubahan Versi')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-code-branch me-2"></i>
                            Detail Perubahan Versi v{{ $version }}
                        </h4>
                        @php
                            // Ambil mapping aktif terbaru untuk kembali ke edit
                            $activeMapping = \App\Models\MappingBarang::where('platform_product_id', $platformProduct->id)
                                ->where('is_active', true)
                                ->first();
                        @endphp
                        <div class="d-flex gap-2">
                            <a href="{{ route('master.mapping.version-history', $platformProduct->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Kembali ke Riwayat
                            </a>
                            @if($activeMapping)
                                <a href="{{ route('master.mapping.edit', $activeMapping->id) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i>
                                    Edit Mapping
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2">
                        <h6 class="text-muted mb-0">{{ $platformProduct->platform_product_name }}</h6>
                        <small class="text-muted">{{ $platformProduct->platform ? $platformProduct->platform->name : 'Unknown Platform' }}</small>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Ringkasan Perubahan -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-plus-circle text-success fa-2x mb-2"></i>
                                    <h5 class="text-success">{{ count($changes['added']) }}</h5>
                                    <p class="mb-0">Ditambah</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-minus-circle text-danger fa-2x mb-2"></i>
                                    <h5 class="text-danger">{{ count($changes['removed']) }}</h5>
                                    <p class="mb-0">Dihapus</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-edit text-warning fa-2x mb-2"></i>
                                    <h5 class="text-warning">{{ count($changes['modified']) }}</h5>
                                    <p class="mb-0">Diubah</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Perubahan -->
                    <div class="row">
                        <!-- Produk yang Ditambah -->
                        @if(count($changes['added']) > 0)
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-plus me-1"></i>
                                        Produk yang Ditambah
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @foreach($changes['added'] as $item)
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <small class="text-muted">{{ $item['product']->name }}</small>
                                            </div>
                                            <span class="badge bg-success">{{ $item['quantity'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Produk yang Dihapus -->
                        @if(count($changes['removed']) > 0)
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-minus me-1"></i>
                                        Produk yang Dihapus
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @foreach($changes['removed'] as $item)
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <small class="text-muted">{{ $item['product']->name }}</small>
                                            </div>
                                            <span class="badge bg-danger">{{ $item['quantity'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Produk yang Diubah -->
                        @if(count($changes['modified']) > 0)
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-edit me-1"></i>
                                        Produk yang Diubah
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @foreach($changes['modified'] as $item)
                                        <div class="mb-2">
                                            <small class="text-muted">{{ $item['product']->name }}</small>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-danger">{{ $item['old_quantity'] }}</span>
                                                <i class="fas fa-arrow-right text-muted"></i>
                                                <span class="badge bg-success">{{ $item['new_quantity'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Jika tidak ada perubahan -->
                    @if(count($changes['added']) == 0 && count($changes['removed']) == 0 && count($changes['modified']) == 0)
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada perubahan</h5>
                            <p class="text-muted">Versi ini tidak memiliki perubahan dari versi sebelumnya.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
