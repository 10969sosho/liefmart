@extends('layouts.app')

@section('page-title', 'Arus Kas Tiktok Lamourad')

@section('content')
<div class="ds-page-header">
    <div>
        <h1 class="text-gradient">Data Arus Kas Tiktok Lamourad</h1>
    </div>
    <div>
        <a href="{{ route('finance.aruskastiktok.import') }}" class="btn btn-primary">
            <i class="fas fa-file-import me-1"></i> Import Data
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
                    @include('common.alert')

                    <!-- Filter Form -->
                    <form action="{{ route('finance.aruskastiktok.index') }}" method="GET" class="mb-4">
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
                                    <a href="{{ route('finance.aruskastiktok.index') }}" class="btn btn-secondary w-100">
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
                                                <a href="{{ route('finance.aruskastiktok.import') }}" class="btn btn-sm btn-primary mt-2">
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

@endsection