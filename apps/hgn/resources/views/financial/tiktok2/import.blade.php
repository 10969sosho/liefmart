@extends('layouts.app')

@section('title', 'Import Data Pembayaran TikTok')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="card-title mb-0">Import Data Pembayaran TikTok</h5>
                    <a href="{{ route('finance.tiktok2.index') }}" class="btn btn-light btn-sm">
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
                                $bankInfo = \App\Models\TiktokFinancialTransaction::getBankAccountInfo();
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
                                    <li class="mb-2">Buka file laporan TikTok, cari sheet "Order details"</li>
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
                                        <code>NOMOR PESANAN | TANGGAL MASUK PEMBAYARAN | HARI MASUK PEMBAYARAN | JUMLAH MASUK PEMBAYARAN | BIAYA ADMIN | AFFILIATE COMMISSION | SELLER SHIPPING FEE + SFP SERVICE FEE | VOUCHER XTRA SERVICE FEE | CASHBACK FEE | BIAYA6 | BIAYA7 | BIAYA8 | BIAYA9 | BIAYA10 | BIAYA11 | BIAYA12</code>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lightbulb me-2"></i> 
                                        <span class="fw-bold">Catatan Penting:</span>
                                        <ul class="mb-0 mt-1">
                                            <li>Nilai diskon dimasukkan dalam format negatif (contoh: -500)</li>
                                            <li>JUMLAH MASUK PEMBAYARAN harus dalam format angka</li>
                                            <li>Sistem secara otomatis mencocokkan nomor pesanan dengan data order yang ada</li>
                                            <li>Hanya transaksi yang valid yang akan diproses saat import</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('finance.tiktok2.import-preview') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                        @csrf
                        <div class="mb-4">
                            <label for="file" class="form-label fw-bold">Upload File Excel</label>
                            <div class="input-group">
                                <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".xlsx, .xls" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Upload & Preview
                                </button>
                            </div>
                            @error('file')
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