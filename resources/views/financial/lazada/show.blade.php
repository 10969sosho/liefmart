@extends('layouts.app')

@section('title', 'Detail Transaksi Keuangan Lazada')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Detail Transaksi Keuangan Lazada</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('finance.index') }}">Menu Keuangan</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('finance.lazada.index') }}">Transaksi Keuangan Lazada</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Detail Transaksi</li>
                        </ol>
                    </nav>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Informasi Order</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">No Order</th>
                                    <td>{{ $transaction->no_order }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Order</th>
                                    <td>{{ $transaction->formatted_date }}</td>
                                </tr>
                                <tr>
                                    <th>Hari Order</th>
                                    <td>{{ $transaction->hari_order ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>No Invoice</th>
                                    <td>{{ $transaction->no_invoice ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Qty</th>
                                    <td>{{ $transaction->qty }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Informasi Pembayaran</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Tanggal Masuk Pembayaran</th>
                                    <td>{{ $transaction->formatted_payment_date }}</td>
                                </tr>
                                <tr>
                                    <th>Hari Masuk Pembayaran</th>
                                    <td>{{ $transaction->hari_masuk_pembayaran ?? '-' }}</td>
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

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Rincian Harga dan Biaya</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Nominal Harga</th>
                                    <td class="text-primary">Rp {{ number_format($transaction->nominal_harga, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Biaya Proses Fix</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon1, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Gratis Ongkir</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon2, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Biaya Admin</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon3, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Biaya Transaksi</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon4, 0, ',', '.') }}</td>
                                </tr>
                                @if($transaction->nominal_diskon5 != 0)
                                <tr>
                                    <th>Diskon 5</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon5, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->nominal_diskon6 != 0)
                                <tr>
                                    <th>Diskon 6</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon6, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->nominal_diskon7 != 0)
                                <tr>
                                    <th>Diskon 7</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon7, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->nominal_diskon8 != 0)
                                <tr>
                                    <th>Diskon 8</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon8, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->nominal_diskon9 != 0)
                                <tr>
                                    <th>Diskon 9</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon9, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->nominal_diskon10 != 0)
                                <tr>
                                    <th>Diskon 10</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon10, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->nominal_diskon11 != 0)
                                <tr>
                                    <th>Diskon 11</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon11, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->nominal_diskon12 != 0)
                                <tr>
                                    <th>Diskon 12</th>
                                    <td class="text-danger">Rp {{ number_format($transaction->nominal_diskon12, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($transaction->adjustment != 0)
                                <tr>
                                    <th>Adjustment</th>
                                    <td class="{{ $transaction->adjustment > 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($transaction->adjustment, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endif
                                <tr class="table-info">
                                    <th><strong>Nominal Fix</strong></th>
                                    <td class="text-primary"><strong>Rp {{ number_format($transaction->nominal_fix, 0, ',', '.') }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Persentase</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Persentase Biaya Proses Fix</th>
                                    <td>{{ number_format($transaction->persentase_diskon1, 2) }}%</td>
                                </tr>
                                <tr>
                                    <th>Persentase Gratis Ongkir</th>
                                    <td>{{ number_format($transaction->persentase_diskon2, 2) }}%</td>
                                </tr>
                                <tr>
                                    <th>Persentase Biaya Admin</th>
                                    <td>{{ number_format($transaction->persentase_diskon3, 2) }}%</td>
                                </tr>
                                <tr>
                                    <th>Persentase Biaya Transaksi</th>
                                    <td>{{ number_format($transaction->persentase_diskon4, 2) }}%</td>
                                </tr>
                                <tr class="table-info">
                                    <th><strong>Total Persentase</strong></th>
                                    <td><strong>{{ number_format($transaction->total_persentase, 2) }}%</strong></td>
                                </tr>
                                <tr>
                                    <th>Persentase Dibayar</th>
                                    <td class="text-success">{{ number_format($transaction->percentage_paid, 2) }}%</td>
                                </tr>
                                <tr>
                                    <th>Persentase Outstanding</th>
                                    <td class="text-danger">{{ number_format($transaction->percentage_outstanding, 2) }}%</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('finance.lazada.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                                <div>
                                    <form action="{{ route('finance.lazada.destroy', $transaction) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Hapus Transaksi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
