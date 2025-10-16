@extends('layouts.app')

@section('title', 'Riwayat Versi Mapping')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Riwayat Versi Mapping
                        </h4>
                        @php
                            // Ambil mapping aktif terbaru untuk kembali ke edit
                            $activeMapping = \App\Models\MappingBarang::where('platform_product_id', $platformProduct->id)
                                ->where('is_active', true)
                                ->first();
                        @endphp
                        @if($activeMapping)
                            <a href="{{ route('master.mapping.edit', $activeMapping->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Kembali ke Edit
                            </a>
                        @else
                            <a href="{{ route('master.mapping.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Kembali ke Daftar
                            </a>
                        @endif
                    </div>
                    <div class="mt-2">
                        <h6 class="text-muted mb-0">{{ $platformProduct->platform_product_name }}</h6>
                        <small class="text-muted">{{ $platformProduct->platform->name }}</small>
                    </div>
                </div>
                
                <div class="card-body">
                    @if($versionSummary->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Versi</th>
                                        <th width="15%">Status</th>
                                        <th width="25%">Jumlah Produk</th>
                                        <th width="20%">Tanggal Dibuat</th>
                                        <th width="25%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($versionSummary as $version)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">v{{ $version->version }}</span>
                                            </td>
                                            <td>
                                                @if($version->last_active_at)
                                                    <span class="badge bg-success">Aktif</span>
                                                @else
                                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $version->total_products }}</strong> produk
                                            </td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($version->created_at)->format('d/m/Y H:i') }}
                                            </td>
                                            <td>
                                                <a href="{{ route('master.mapping.version-detail', [$platformProduct->id, $version->version]) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>
                                                    Lihat Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada riwayat versi</h5>
                            <p class="text-muted">Mapping ini belum pernah diubah.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
