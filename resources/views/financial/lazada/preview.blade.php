@extends('layouts.app')

@section('title', 'Preview Import Financial Data Lazada')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye"></i> Preview Import Data Keuangan Lazada
                    </h3>
                </div>
                <div class="card-body">
                    @if(!empty($headerIssues))
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Masalah Header</h5>
                            <ul class="mb-0">
                                @foreach($headerIssues as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(!empty($invalidData))
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-circle"></i> Data Tidak Valid</h5>
                            <ul class="mb-0">
                                @foreach($invalidData as $invalid)
                                    <li>{{ $invalid }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Statistik Import -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Statistik Import</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-info">
                                                    <i class="fas fa-file-excel"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Total Baris</span>
                                                    <span class="info-box-number">{{ $stats['total_rows'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Data Valid</span>
                                                    <span class="info-box-number">{{ $stats['processed_rows'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-warning">
                                                    <i class="fas fa-exclamation"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Data Dilewati</span>
                                                    <span class="info-box-number">{{ $stats['skipped_rows'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-danger">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Data Invalid</span>
                                                    <span class="info-box-number">{{ $stats['invalid_data'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($data))
                        <!-- Preview Data -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Preview Data ({{ count($data) }} baris)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>No Order</th>
                                                        <th>Tanggal Masuk Pembayaran</th>
                                                        <th>Hari Masuk Pembayaran</th>
                                                        <th>Jumlah Masuk Pembayaran</th>
                                                        <th>Biaya Proses Fix</th>
                                                        <th>Gratis Ongkir</th>
                                                        <th>Biaya Admin</th>
                                                        <th>Biaya Transaksi</th>
                                                        <th>Diskon 5</th>
                                                        <th>Diskon 6</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data as $index => $row)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $row['no_order'] ?? '-' }}</td>
                                                            <td>{{ $row['tanggal_masuk_pembayaran'] ? \Carbon\Carbon::parse($row['tanggal_masuk_pembayaran'])->format('d-m-Y') : '-' }}</td>
                                                            <td>{{ $row['hari_masuk_pembayaran'] ?? '-' }}</td>
                                                            <td class="text-success">Rp {{ number_format($row['saldo_masuk'] ?? 0, 0, ',', '.') }}</td>
                                                            <td class="text-danger">Rp {{ number_format($row['nominal_diskon1'] ?? 0, 0, ',', '.') }}</td>
                                                            <td class="text-danger">Rp {{ number_format($row['nominal_diskon2'] ?? 0, 0, ',', '.') }}</td>
                                                            <td class="text-danger">Rp {{ number_format($row['nominal_diskon3'] ?? 0, 0, ',', '.') }}</td>
                                                            <td class="text-danger">Rp {{ number_format($row['nominal_diskon4'] ?? 0, 0, ',', '.') }}</td>
                                                            <td class="text-danger">Rp {{ number_format($row['nominal_diskon5'] ?? 0, 0, ',', '.') }}</td>
                                                            <td class="text-danger">Rp {{ number_format($row['nominal_diskon6'] ?? 0, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <form action="{{ route('finance.lazada.process') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-save"></i> Simpan Data
                                            </button>
                                        </form>
                                        <a href="{{ route('finance.lazada.import') }}" class="btn btn-secondary btn-lg ml-2">
                                            <i class="fas fa-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Tidak ada data yang valid untuk diimport.
                        </div>
                        <div class="text-center">
                            <a href="{{ route('finance.lazada.import') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
