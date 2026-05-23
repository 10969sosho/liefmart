@extends('layouts.app')

@section('page-title', 'Arus Kas Tiktok Liefmarket')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Data Arus Kas Tiktok Liefmarket</h5>
                    <div>
                        <a href="{{ route('finance.aruskastiktok2.import') }}" class="btn btn-primary">
                            <i class="fas fa-file-import me-1"></i> Import Data
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @include('common.alert')

                    <!-- Filter Form -->
                    <form action="{{ route('finance.aruskastiktok2.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <a href="{{ route('finance.aruskastiktok2.index') }}" class="btn btn-secondary w-100">
                                        <i class="fas fa-times me-1"></i> Clear Filter
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th class="no-col">No</th>
                                    <th class="tanggal-col">Tanggal Pembayaran</th>
                                    <th class="deskripsi-col">Deskripsi</th>
                                    <th class="nopesanan-col">No. Pesanan</th>
                                    <th class="tglpesanan-col">Tanggal Pesanan</th>
                                    <th class="pembayaran-col">Pembayaran</th>
                                    <th class="saldo-col">Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $index => $transaction)
                                    <tr>
                                        <td class="no-col">{{ $index + 1 }}</td>
                                        <td class="tanggal-col fw-bold">{{ $transaction->formatted_tanggal_pembayaran }}</td>
                                        <td class="deskripsi-col">{{ $transaction->deskripsi }}</td>
                                        <td class="nopesanan-col fw-bold">{{ $transaction->no_pesanan }}</td>
                                        <td class="tglpesanan-col">{{ $transaction->formatted_tanggal_pesanan ?: '-' }}</td>
                                        <td class="pembayaran-col text-end fw-bold">Rp {{ $transaction->formatted_pembayaran }}</td>
                                        <td class="saldo-col text-end fw-bold">Rp {{ $transaction->formatted_saldo_akhir }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5 class="fw-light text-muted">Tidak ada data</h5>
                                                <p class="text-muted">Silahkan import data arus kas terlebih dahulu</p>
                                                <a href="{{ route('finance.aruskastiktok2.import') }}" class="btn btn-sm btn-primary mt-2">
                                                    <i class="fas fa-file-import me-1"></i> Import Data
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <p class="text-muted">
                            Menampilkan {{ count($transactions) }} data
                            @if($startDate || $endDate)
                                dari {{ $startDate }} sampai {{ $endDate }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .pagination {
        margin-bottom: 0;
    }
    .page-item.active .page-link {
        background-color: #4F46E5;
        border-color: #4F46E5;
    }
    .page-link {
        color: #4F46E5;
    }
    .page-link:hover {
        color: #3730A3;
    }
    .pagination-container {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 0.25rem;
    }
    .table th, .table td {
        white-space: nowrap;
    }
    .table th.no-col, .table td.no-col {
        width: 40px;
        text-align: center;
    }
    .table th.tanggal-col, .table td.tanggal-col {
        width: 120px;
        text-align: center;
    }
    .table th.deskripsi-col, .table td.deskripsi-col {
        max-width: 220px;
        min-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .table th.nopesanan-col, .table td.nopesanan-col {
        width: 150px;
        text-align: center;
    }
    .table th.tglpesanan-col, .table td.tglpesanan-col {
        width: 120px;
        text-align: center;
    }
    .table th.pembayaran-col, .table td.pembayaran-col,
    .table th.saldo-col, .table td.saldo-col {
        width: 120px;
        text-align: right;
    }
    .bg-primary.text-white {
        background-color: #4F46E5 !important;
    }
    .form-label {
        font-weight: 500;
        color: #4B5563;
    }
    .form-control:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
    }
</style>
@endsection 