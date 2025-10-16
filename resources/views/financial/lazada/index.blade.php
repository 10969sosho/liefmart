@extends('layouts.app')

@section('title', 'Lazada Financial Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i> Transaksi Keuangan Lazada
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('finance.lazada.import') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-import"></i> Import Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No Order</th>
                                    <th>Tanggal Order</th>
                                    <th>Nominal Harga</th>
                                    <th>Biaya Proses Fix</th>
                                    <th>Gratis Ongkir</th>
                                    <th>Biaya Admin</th>
                                    <th>Biaya Transaksi</th>
                                    <th>Nominal Fix</th>
                                    <th>Saldo Masuk</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Outstanding</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $index => $transaction)
                                    <tr>
                                        <td>{{ $transactions->firstItem() + $index }}</td>
                                        <td>{{ $transaction->no_order }}</td>
                                        <td>{{ $transaction->formatted_date }}</td>
                                        <td>Rp {{ number_format($transaction->nominal_harga, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($transaction->nominal_diskon1, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($transaction->nominal_diskon2, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($transaction->nominal_diskon3, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($transaction->nominal_diskon4, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($transaction->nominal_fix, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($transaction->saldo_masuk, 0, ',', '.') }}</td>
                                        <td>{{ $transaction->formatted_payment_date }}</td>
                                        <td class="{{ $transaction->outstanding > 0 ? 'text-danger' : 'text-success' }}">
                                            Rp {{ number_format($transaction->outstanding, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('finance.lazada.show', $transaction) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('finance.lazada.destroy', $transaction) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">Tidak ada data transaksi keuangan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
