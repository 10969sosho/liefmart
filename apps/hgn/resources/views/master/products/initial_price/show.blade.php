@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">
                <div class="card-header bg-gradient-light d-flex justify-content-between align-items-center py-3 px-4">
                    <div>
                        <h5 class="mb-0 fw-semibold text-primary">
                            <i class="fas fa-tag me-2"></i>Kelola Harga Awal
                        </h5>
                        <div class="text-muted text-sm mt-1">
                            {{ $product->name }} • {{ $product->sku ?? '-' }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('products.initial-price.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="fas fa-edit me-1"></i> Edit Produk
                        </a>
                    </div>
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

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="border rounded-3 p-3 bg-light">
                                <h6 class="fw-semibold mb-3">Buat Versi Baru</h6>
                                <form action="{{ route('products.initial-price.store', $product->id) }}" method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Berlaku</label>
                                        <input type="datetime-local" name="effective_at" class="form-control @error('effective_at') is-invalid @enderror"
                                               value="{{ old('effective_at', now()->format('Y-m-d\\TH:i')) }}" required>
                                        @error('effective_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Harga Awal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="initial_price" min="0" step="0.01"
                                                   class="form-control @error('initial_price') is-invalid @enderror"
                                                   value="{{ old('initial_price', $activeVersion?->initial_price ?? ($product->initial_price ?? 0)) }}" required>
                                            @error('initial_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Diskon (%)</label>
                                        <div class="input-group">
                                            <input type="number" name="discount_percentage" min="0" max="100" step="0.01"
                                                   class="form-control @error('discount_percentage') is-invalid @enderror"
                                                   value="{{ old('discount_percentage', $activeVersion?->discount_percentage ?? ($product->discount_percentage ?? 0)) }}">
                                            <span class="input-group-text">%</span>
                                            @error('discount_percentage')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Alasan</label>
                                        <input type="text" name="change_reason" class="form-control @error('change_reason') is-invalid @enderror"
                                               value="{{ old('change_reason') }}" placeholder="Contoh: update pricelist">
                                        @error('change_reason')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                        <i class="fas fa-plus me-1"></i> Buat Versi Baru
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-semibold mb-0">Riwayat Versi</h6>
                                <div class="text-sm text-muted">
                                    Aktif: Versi {{ $activeVersion?->version ?? '-' }}
                                </div>
                            </div>

                            <div class="table-responsive border rounded-3 shadow-sm overflow-hidden">
                                <table class="table align-items-center mb-0 table-hover">
                                    <thead class="bg-light">
                                        <tr class="bg-white">
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 10%;">Versi</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 18%;">Berlaku</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 18%;">Berakhir</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Harga Awal</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Diskon</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Alasan</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" style="width: 10%;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($versions as $v)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge rounded-pill {{ $v->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $v->version }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-secondary text-xs font-weight-bold">
                                                        {{ optional($v->valid_from ?? $v->created_at)->format('d/m/Y H:i') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-secondary text-xs font-weight-bold">
                                                        {{ $v->valid_until ? $v->valid_until->format('d/m/Y H:i') : '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-secondary text-xs font-weight-bold">
                                                        Rp {{ number_format((float) $v->initial_price, 0, ',', '.') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-secondary text-xs font-weight-bold">
                                                        {{ number_format((float) ($v->discount_percentage ?? 0), 2) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-secondary text-xs font-weight-bold text-wrap">
                                                        {{ $v->change_reason ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill {{ $v->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $v->is_active ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <div class="empty-state mb-3">
                                                            <i class="fas fa-history fa-4x text-secondary opacity-50"></i>
                                                        </div>
                                                        <h6 class="fw-normal mb-1">Belum ada riwayat</h6>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

