@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="card-title mb-0">Import Data Pembayaran Tokopedia</h5>
                    <a href="{{ route('finance.tokopedia.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-university mr-2"></i> Informasi Rekening Aktif</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $bankInfo = \App\Models\TokopediaFinancialTransaction::getBankAccountInfo();
                            @endphp
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Bank:</strong> {{ $bankInfo['bank_name'] }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Nomor Rekening:</strong> {{ $bankInfo['account_number'] }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Atas Nama:</strong> {{ $bankInfo['account_name'] }}</p>
                                </div>
                            </div>
                            @if(!$bankInfo['has_active'])
                                <div class="alert alert-warning mb-0 mt-2">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> Belum ada rekening bank yang diatur sebagai aktif. 
                                    <a href="{{ route('bank-accounts.index') }}" class="alert-link">Atur rekening aktif sekarang</a>.
                                </div>
                            @else
                                <p class="small text-muted mb-0">Informasi rekening ini akan ditampilkan di semua invoice yang dicetak.</p>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Petunjuk Import:</h5>
                                <hr>
                                <ol class="mb-0">
                                    <li class="mb-2">Download laporan keuangan dari Tokopedia Seller Center</li>
                                    <li class="mb-2">Pastikan file berformat Excel (.xlsx, .xls)</li>
                                    <li class="mb-2">Upload file ke sistem untuk dianalisis</li>
                                    <li class="mb-2">Sistem akan secara otomatis memproses data transaksi</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Format Data yang Dibutuhkan</h6>
                                </div>
                                <div class="card-body">
                                    <p>File Excel harus memiliki kolom berikut:</p>
                                    <div class="bg-light p-3 rounded mb-3">
                                        <code>Description | Nominal (Rp)</code>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lightbulb me-2"></i> 
                                        <span class="fw-bold">Catatan Penting:</span>
                                        <ul class="mb-0 mt-1">
                                            <li>Kolom <strong>Description</strong> harus berisi informasi transaksi dengan format nomor order (contoh: INV/20240523/MPL/3923642079)</li>
                                            <li>Kolom <strong>Nominal (Rp)</strong> berisi nilai transaksi atau diskon</li>
                                            <li>Sistem akan secara otomatis memproses transaksi dan <strong>memisahkan Biaya Admin dan Biaya Layanan</strong></li>
                                            <li>Biaya Admin akan masuk ke kolom terpisah dari Biaya Layanan</li>
                                            <li>Nilai yang dihitung akan otomatis mengisi nominal_fix = saldo_masuk</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> 
                                        <span class="fw-bold">Mapping Biaya (HEADER DIPERBAIKI):</span>
                                        <ul class="mb-0 mt-1">
                                            <li><strong>Komisi</strong> → Kolom "Komisi" (nominal_diskon1) ✅</li>
                                            <li><strong>Bebas Ongkir</strong> → Kolom "Biaya Layanan" (nominal_diskon2) ✅</li>
                                            <li><strong>Power Merchant saja</strong> → Kolom "Biaya Admin" (nominal_diskon3) ✅</li>
                                            <li><strong>Ongkir/Shipping</strong> → Kolom "Ongkir" (nominal_diskon4) ✅</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('finance.tokopedia.import-preview') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                        @csrf
                        <div class="mb-4">
                            <label for="file" class="form-label fw-bold">Upload File Excel</label>
                            <div class="input-group">
                                <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx, .xls" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Upload & Preview
                                </button>
                            </div>
                            @error('file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted mt-2" id="file-selected">
                                <i class="fas fa-file-excel me-1"></i> Belum ada file yang dipilih
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
    document.getElementById('file').addEventListener('change', function(e) {
        const fileInput = e.target;
        const fileInfo = document.getElementById('file-selected');
        
        if (fileInput.files && fileInput.files[0]) {
            const fileName = fileInput.files[0].name;
            const fileSize = (fileInput.files[0].size / 1024).toFixed(2);
            fileInfo.innerHTML = `<i class="fas fa-file-excel me-1 text-success"></i> <span class="fw-bold">${fileName}</span> (${fileSize} KB)`;
        } else {
            fileInfo.innerHTML = `<i class="fas fa-file-excel me-1"></i> Belum ada file yang dipilih`;
        }
    });
</script>
@endpush