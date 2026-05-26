@extends('layouts.app')

@push('styles')
<style>
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-control[readonly] {
        background-color: #f8f9fa;
        border-color: #e9ecef;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="ds-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Tambah Master Barang Platform
                            </h5>
                            <p class="mb-0" style="opacity:0.85; font-size:0.8rem;">Buat barang platform baru</p>
                        </div>
                        <a href="{{ route('barang-platform.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
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
