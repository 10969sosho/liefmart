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
                                <i class="fas fa-edit me-2"></i>Edit Master Barang Platform
                            </h5>
                            <p class="mb-0" style="opacity:0.85; font-size:0.8rem;">Platform: {{ $platformProduct->platform->name }}</p>
                        </div>
                        <a href="{{ route('barang-platform.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <form action="{{ route('barang-platform.update', $platformProduct->id) }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        
                        <!-- Platform Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-store me-2"></i>Informasi Platform
                                </h5>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="platform_id" class="form-label">Platform</label>
                                    <input type="text" class="form-control" value="{{ $platformProduct->platform->name }}" readonly>
                                    <div class="form-text text-muted">
                                        <i class="fas fa-lock me-1"></i>Platform tidak dapat diubah
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="platform_product_name" class="form-label">
                                        Nama Barang Platform <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="platform_product_name" id="platform_product_name" 
                                           class="form-control @error('platform_product_name') is-invalid @enderror"
                                           value="{{ old('platform_product_name', $platformProduct->platform_product_name) }}" 
                                           required>
                                    @error('platform_product_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-info">
                                        <i class="fas fa-info-circle me-1"></i> 
                                        Mengubah nama akan mempengaruhi semua analytics dan laporan
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-cube me-2"></i>Detail Produk
                                </h5>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="variant" class="form-label">Variant</label>
                                    <input type="text" name="variant" id="variant" 
                                           class="form-control @error('variant') is-invalid @enderror"
                                           value="{{ old('variant', $platformProduct->variant) }}"
                                           placeholder="Contoh: Warna Merah, Size L, dll">
                                    @error('variant')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted">
                                        <i class="fas fa-tag me-1"></i>Variant produk (opsional)
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info Mapping -->
                        @if($platformProduct->mappingBarang->count() > 0)
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle fa-2x me-3 mt-1"></i>
                                    <div>
                                        <h5 class="alert-heading mb-2">Informasi Mapping</h5>
                                        <p class="mb-2">Barang ini sudah di-mapping dengan <strong>{{ $platformProduct->mappingBarang->count() }}</strong> produk internal:</p>
                                        <ul class="mb-0">
                                            @foreach($platformProduct->mappingBarang as $mapping)
                                                <li><strong>{{ $mapping->product ? $mapping->product->name : 'Product tidak ditemukan (ID: ' . $mapping->product_id . ')' }}</strong> (Qty: {{ $mapping->quantity }})</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Info Sales Usage -->
                        @php
                            $usedInSales = \DB::table('order_items')
                                ->where('platform_product_id', $platformProduct->id)
                                ->exists();
                        @endphp
                        @if($usedInSales)
                            <div class="alert alert-warning mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-exclamation-triangle fa-2x me-3 mt-1"></i>
                                    <div>
                                        <h5 class="alert-heading mb-2">Peringatan Penting</h5>
                                        <p class="mb-0">Barang ini sudah digunakan dalam transaksi penjualan. Mengubah nama akan mempengaruhi semua laporan dan analytics.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-3">
                                    <a href="{{ route('barang-platform.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Barang Platform
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-save draft functionality
let draftKey = 'barang-platform-edit-{{ $platformProduct->id }}';

// Save draft on input change
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[type="text"]');
    
    // Load saved draft
    const savedDraft = localStorage.getItem(draftKey);
    if (savedDraft) {
        const draft = JSON.parse(savedDraft);
        Object.keys(draft).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.value === '') {
                input.value = draft[key];
            }
        });
    }
    
    // Save draft on input change
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const formData = new FormData(form);
            const draft = {};
            for (let [key, value] of formData.entries()) {
                if (key !== '_token' && key !== '_method') {
                    draft[key] = value;
                }
            }
            localStorage.setItem(draftKey, JSON.stringify(draft));
        });
    });
    
    // Clear draft on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem(draftKey);
    });
});

// Confirmation before submit
document.querySelector('form').addEventListener('submit', function(e) {
    const platformProductName = document.getElementById('platform_product_name').value;
    const originalName = '{{ $platformProduct->platform_product_name }}';
    
    if (platformProductName !== originalName) {
        if (!confirm('Anda yakin ingin mengubah nama barang platform? Perubahan ini akan mempengaruhi semua analytics dan laporan.')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>
@endpush
