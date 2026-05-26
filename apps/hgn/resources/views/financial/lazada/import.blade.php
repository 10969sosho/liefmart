@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="card-title mb-0">Import Data Pembayaran Lazada</h5>
                    <a href="{{ route('finance.lazada.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            @php
                                $error = session('error');
                            @endphp
                            @if(is_array($error))
                                <ul class="mb-0 ps-3">
                                    @foreach($error as $err)
                                        @if(is_array($err))
                                            @foreach($err as $suberr)
                                                <li>{{ $suberr }}</li>
                                            @endforeach
                                        @else
                                            <li>{{ $err }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                {{ $error }}
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Petunjuk Import:</h5>
                                <hr>
                                <ol class="mb-0">
                                    <li class="mb-2">Buka file laporan Lazada</li>
                                    <li class="mb-2">Pastikan file berformat Excel (.xlsx, .xls)</li>
                                    <li class="mb-2">Sesuaikan header jika diperlukan</li>
                                    <li class="mb-2">Sistem akan mencari data order berdasarkan nomor pesanan</li>
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
                                    <div class="bg-light p-3 rounded mb-3 overflow-auto">
                                        <code>NAMA BIAYA | NOMINAL BIAYA | TANGGAL PEMBAYARAN | HARI PEMBAYARAN | NOMOR PESANAN | UANG MASUK</code>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> 
                                        <span class="fw-bold">Struktur Data:</span>
                                        <ul class="mb-0 mt-1">
                                            <li>Data diorganisir dalam <strong>grup berdasarkan NOMOR PESANAN</strong></li>
                                            <li>Setiap grup memiliki beberapa baris dengan <strong>NAMA BIAYA</strong> yang berbeda</li>
                                            <li><strong>UANG MASUK</strong> hanya muncul di baris pertama setiap grup</li>
                                            <li><strong>TANGGAL PEMBAYARAN</strong> dan <strong>HARI PEMBAYARAN</strong> sama untuk semua baris dalam satu grup</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lightbulb me-2"></i> 
                                        <span class="fw-bold">Jenis Nama Biaya yang Didukung:</span>
                                        <ul class="mb-0 mt-1">
                                            <li><strong>HARGA SETELAH DISKON</strong> - Harga setelah diskon (nilai positif)</li>
                                            <li><strong>BIAYA PROSES FIX</strong> - Biaya proses tetap (nilai negatif)</li>
                                            <li><strong>GRATIS ONGKIR</strong> - Biaya gratis ongkir (nilai negatif)</li>
                                            <li><strong>BIAYA ADMIN</strong> - Biaya administrasi (nilai negatif)</li>
                                            <li><strong>BIAYA TRANSAKSI</strong> - Biaya transaksi (nilai negatif)</li>
                                            <li><strong>DISKON 5</strong> atau <strong>BIAYA 5</strong> - Diskon/biaya tambahan (opsional)</li>
                                            <li><strong>DISKON 6</strong> atau <strong>BIAYA 6</strong> - Diskon/biaya tambahan (opsional)</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i> 
                                        <span class="fw-bold">Catatan Penting:</span>
                                        <ul class="mb-0 mt-1">
                                            <li><strong>Format Tanggal:</strong> DD Mon YYYY (contoh: 10 Nov 2025) atau DD-MM-YYYY</li>
                                            <li><strong>Format Nominal:</strong> Gunakan angka tanpa format mata uang</li>
                                            <li>Nilai biaya bisa positif atau negatif sesuai jenis biaya</li>
                                            <li>Sistem secara otomatis mencocokkan nomor pesanan dengan data order yang ada</li>
                                            <li>Order yang tidak ditemukan akan ditandai sebagai tidak valid</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('finance.lazada.preview') }}" enctype="multipart/form-data" id="lazadaFinancialImportForm" class="mt-4">
                        @csrf
                        <div class="mb-4">
                            <label for="excel_file" class="form-label fw-bold">Upload File Excel</label>
                            <div class="input-group">
                                <input type="file" class="form-control @error('excel_file') is-invalid @enderror" id="excel_file" name="excel_file" accept=".xls,.xlsx,.csv" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Upload & Preview
                                </button>
                            </div>
                            @error('excel_file')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
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
    document.getElementById('excel_file').addEventListener('change', function(e) {
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
