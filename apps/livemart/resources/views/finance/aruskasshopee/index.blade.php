@extends('layouts.app')

@section('page-title', 'Arus Kas Shopee Lamourad')

@section('content')
<div class="ds-page-header">
    <div>
        <h1 class="text-gradient">Data Arus Kas Shopee Lamourad</h1>
    </div>
    <div>
        <a href="{{ route('finance.aruskasshopee.import') }}" class="btn btn-primary">
            <i class="fas fa-file-import me-1"></i> Import Data
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
                    @include('common.alert')

                    <!-- Filter Form -->
                    <form action="{{ route('finance.aruskasshopee.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="{{ $startDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="{{ $endDate }}">
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
                                    <a href="{{ route('finance.aruskasshopee.index') }}" class="btn btn-secondary w-100">
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
                                    <th class="tanggal-col">Tanggal Pemasukan</th>
                                    <th class="deskripsi-col">Deskripsi</th>
                                    <th class="nopesanan-col">No. Pesanan</th>
                                    <th class="tanggalpesanan-col">Tanggal Pesanan</th>
                                    <th class="pemasukan-col">Pemasukan</th>
                                    <th class="saldo-col">Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $index => $transaction)
                                    <tr>
                                        <td class="no-col">{{ $index + 1 }}</td>
                                        <td class="tanggal-col fw-bold">{{ $transaction->tanggal_pemasukan->format('d-m-Y') }}</td>
                                        <td class="deskripsi-col">{{ $transaction->deskripsi }}</td>
                                        <td class="nopesanan-col fw-bold">{{ $transaction->no_pesanan }}</td>
                                        <td class="tanggalpesanan-col fw-bold">
                                            @if($transaction->tanggal_pesanan)
                                                {{ $transaction->tanggal_pesanan->format('d-m-Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="pemasukan-col text-end fw-bold">Rp {{ number_format($transaction->pemasukan, 0, ',', '.') }}</td>
                                        <td class="saldo-col text-end fw-bold">Rp {{ number_format($transaction->saldo_akhir, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5 class="fw-light text-muted">Tidak ada data</h5>
                                                <p class="text-muted">Silahkan import data arus kas terlebih dahulu</p>
                                                <a href="{{ route('finance.aruskasshopee.import') }}" class="btn btn-sm btn-primary mt-2">
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