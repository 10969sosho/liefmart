@extends('layouts.app')

@section('page-title', 'Import Arus Kas Tokopedia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Import Data Arus Kas Tokopedia</h5>
                    <a href="{{ route('finance.aruskastokopedia.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>

                <div class="card-body">
                    @include('common.alert')

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info bg-light-info border-start border-info border-4">
                                <h5 class="alert-heading fw-bold mb-3"><i class="fas fa-info-circle me-2"></i> Informasi Format File</h5>
                                <p>File Excel yang diimpor harus memiliki kolom-kolom berikut:</p>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6 class="fw-bold mt-2 mb-3">Kolom Utama (Wajib):</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item bg-transparent"><i class="fas fa-star text-warning me-2"></i> <strong>TANGGAL MASUK PEMBAYARAN</strong> - Format tanggal</li>
                                                    <li class="list-group-item bg-transparent"><i class="fas fa-star text-warning me-2"></i> <strong>HARI MASUK PEMBAYARAN</strong> - Hari dalam seminggu</li>
                                                    <li class="list-group-item bg-transparent"><i class="fas fa-star text-warning me-2"></i> <strong>Mutation (Debit/Credit)</strong> - Tipe mutasi</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item bg-transparent"><i class="fas fa-star text-warning me-2"></i> <strong>Description</strong> - Penjelasan transaksi</li>
                                                    <li class="list-group-item bg-transparent"><i class="fas fa-star text-warning me-2"></i> <strong>Nominal (Rp)</strong> - Nilai transaksi</li>
                                                    <li class="list-group-item bg-transparent"><i class="fas fa-star text-warning me-2"></i> <strong>Balance (Rp)</strong> - Saldo akhir</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 alert alert-warning py-2 px-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>Catatan Penting:</strong> Kolom lain yang tidak disebutkan di atas akan diabaikan dalam proses import.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 bg-light-subtle mb-4">
                        <div class="card-body">
                            <form action="{{ route('finance.aruskastokopedia.preview') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="form-group mb-3">
                                            <label for="file" class="form-label fw-medium">File Excel Arus Kas Tokopedia</label>
                                            <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" required accept=".xlsx,.xls">
                                            @error('file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text mt-1">
                                                <i class="fas fa-info-circle text-muted me-1"></i> 
                                                Format file yang didukung: .xlsx, .xls
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-eye me-1"></i> Preview Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-info {
        background-color: rgba(79, 70, 229, 0.07);
    }
    .bg-light-subtle {
        background-color: #f8f9fa;
    }
    .list-group-flush .list-group-item {
        padding: 0.5rem 0;
    }
    .form-label.fw-medium {
        font-weight: 500;
    }
    .text-warning {
        color: #F59E0B !important;
    }
    .border-info {
        border-color: #4F46E5 !important;
    }
    .btn-primary {
        background-color: #4F46E5;
        border-color: #4F46E5;
    }
    .btn-primary:hover {
        background-color: #3730A3;
        border-color: #3730A3;
    }
</style>
@endsection 