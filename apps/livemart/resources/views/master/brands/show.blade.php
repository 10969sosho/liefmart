@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Detail Brand</h6>
                    <div>
                        <a href="{{ route('brands.edit', $brand->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('brands.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card card-plain">
                                <div class="card-header pb-0">
                                    <h6 class="text-uppercase text-primary">Informasi Brand</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">ID</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $brand->id }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Nama</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $brand->name }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Kategori Utama</div>
                                        <div class="col-md-8 text-sm font-weight-bold">
                                            {{ $brand->mainCategory->name ?? 'Tidak ada' }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Status</div>
                                        <div class="col-md-8">
                                            <span class="badge {{ $brand->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $brand->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Dibuat pada</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $brand->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 text-sm text-secondary">Diperbarui pada</div>
                                        <div class="col-md-8 text-sm font-weight-bold">{{ $brand->updated_at->format('d M Y H:i') }}</div>
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
                                    <p class="text-sm">{{ $brand->description ?: 'Tidak ada deskripsi' }}</p>
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