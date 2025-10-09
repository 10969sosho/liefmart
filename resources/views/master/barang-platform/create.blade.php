@extends('layouts.app')

@push('styles')
<style>
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .card-header .card-title {
        color: white;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn {
        border-radius: 0.375rem;
        font-weight: 500;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background-color: #6c757d;
        border: none;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
        transform: translateY(-1px);
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-1">
                                <i class="fas fa-plus me-2"></i>Tambah Master Barang Platform
                            </h3>
                            <p class="text-muted mb-0">Buat barang platform baru</p>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('barang-platform.index') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <form action="{{ route('barang-platform.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="platform_id">Platform <span class="text-danger">*</span></label>
                                    <select name="platform_id" id="platform_id" class="form-control @error('platform_id') is-invalid @enderror" required>
                                        <option value="">Pilih Platform</option>
                                        @foreach($platforms as $platform)
                                            <option value="{{ $platform->id }}" {{ old('platform_id') == $platform->id ? 'selected' : '' }}>
                                                {{ $platform->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('platform_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="platform_product_name">Nama Barang Platform <span class="text-danger">*</span></label>
                                    <input type="text" name="platform_product_name" id="platform_product_name" 
                                           class="form-control @error('platform_product_name') is-invalid @enderror"
                                           value="{{ old('platform_product_name') }}" required>
                                    @error('platform_product_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="variant">Variant</label>
                                    <input type="text" name="variant" id="variant" 
                                           class="form-control @error('variant') is-invalid @enderror"
                                           value="{{ old('variant') }}"
                                           placeholder="Contoh: Warna Merah, Size L, dll">
                                    @error('variant')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-info">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Kombinasi Platform + Nama + Variant harus unik
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('barang-platform.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
