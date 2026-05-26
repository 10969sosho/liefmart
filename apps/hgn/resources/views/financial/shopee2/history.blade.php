@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Riwayat Perubahan Transaksi</h1>
            <p class="text-muted small mb-0">Menampilkan riwayat perubahan untuk transaksi Shopee {{ $transaction->no_order }}</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('finance.shopee2.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Transaction Info Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-info-circle me-1"></i> Informasi Transaksi
            </h6>
            <div>
                @if($transaction->isLocked())
                    <span class="badge bg-danger">
                        <i class="fas fa-lock me-1"></i> Dikunci
                    </span>
                @else
                    <span class="badge bg-success">
                        <i class="fas fa-unlock me-1"></i> Tidak Dikunci
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="35%">No. Order</th>
                            <td width="65%">{{ $transaction->no_order }}</td>
                        </tr>
                        <tr>
                            <th>No. Invoice</th>
                            <td>{{ $transaction->no_invoice }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Order</th>
                            <td>{{ $transaction->tanggal_order ? $transaction->tanggal_order->format('d-m-Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Nominal Harga</th>
                            <td>Rp {{ number_format($transaction->nominal_harga, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="35%">Nominal Fix</th>
                            <td width="65%" class="fw-bold">Rp {{ number_format($transaction->nominal_fix, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Adjustment</th>
                            <td class="{{ $transaction->adjustment > 0 ? 'text-success' : ($transaction->adjustment < 0 ? 'text-danger' : '') }}">
                                Rp {{ number_format($transaction->adjustment, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <th>Saldo Masuk</th>
                            <td class="text-success">Rp {{ number_format($transaction->saldo_masuk, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Outstanding</th>
                            <td class="{{ $transaction->outstanding > 0 ? 'text-danger' : 'text-success' }}">
                                Rp {{ number_format($transaction->outstanding, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($transaction->isLocked())
            <div class="alert alert-secondary mt-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lock me-3 fa-2x"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Transaksi Ini Telah Dikunci</h6>
                        <p class="mb-0">
                            Dikunci oleh <strong>{{ $transaction->lockedByUser->name ?? 'Unknown' }}</strong> 
                            pada {{ $transaction->locked_at->format('d M Y H:i') }}
                        </p>
                    </div>
                    @if(auth()->user()->role == 'admin' || $transaction->locked_by == auth()->id())
                    <div class="ms-auto">
                        <form action="{{ route('finance.shopee2.unlock', $transaction->id) }}" method="POST">
                            @csrf
                            @method('POST')
                            <button type="submit" class="btn btn-outline-dark" onclick="return confirm('Yakin ingin membuka kunci transaksi ini?')">
                                <i class="fas fa-unlock me-1"></i> Buka Kunci
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Adjustment History -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-history me-1"></i> Riwayat Adjustment
            </h6>
        </div>
        <div class="card-body">
            @if(isset($adjustmentHistories) && $adjustmentHistories->count())
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>User</th>
                                <th>Adjustment Lama</th>
                                <th>Adjustment Baru</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adjustmentHistories as $history)
                                <tr>
                                    <td>{{ $history->created_at->format('d-m-Y H:i') }}</td>
                                    <td>{{ $history->user->name ?? '-' }}</td>
                                    <td>Rp {{ number_format($history->old_value, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($history->new_value, 0, ',', '.') }}</td>
                                    <td>{{ $history->description ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada riwayat adjustment untuk transaksi ini.</h5>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 