@extends('layouts.app')

@section('title', 'Lazada - Preview Import')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye"></i> Preview Import Lazada
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

                    @if(!empty($unmappedProducts))
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Produk Belum Di-mapping</h5>
                            <p>Beberapa produk belum di-mapping ke produk internal:</p>
                            <ul class="mb-0">
                                @foreach($unmappedProducts as $product)
                                    <li>{{ $product['nama_barang'] }} @if($product['variasi']) - {{ $product['variasi'] }} @endif</li>
                                @endforeach
                            </ul>
                            <p class="mt-2">
                                <a href="{{ route('sales.lazada.mapping') }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-link"></i> Mapping Produk
                                </a>
                            </p>
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
                                                    <span class="info-box-number">{{ $stats['valid_data'] }}</span>
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
                                                    <span class="info-box-number">{{ $stats['skipped_orders'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-danger">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Duplikat</span>
                                                    <span class="info-box-number">{{ $stats['duplicate_orders'] }}</span>
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
                                                        <th>Tanggal</th>
                                                        <th>Hari</th>
                                                        <th>Status Hari</th>
                                                        <th>No Order</th>
                                                        <th>Produk</th>
                                                        <th>Varian</th>
                                                        <th>QTY</th>
                                                        <th>Harga</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data as $index => $row)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $row['tanggal'] }}</td>
                                                            <td>{{ $row['hari'] ?? '-' }}</td>
                                                            <td>{{ $row['status_hari'] ?? '-' }}</td>
                                                            <td>{{ $row['no_order'] }}</td>
                                                            <td>{{ $row['nama_barang'] }}</td>
                                                            <td>{{ $row['variasi'] ?? '-' }}</td>
                                                            <td>{{ $row['qty'] }}</td>
                                                            <td>Rp {{ number_format($row['harga_setelah_diskon'], 0, ',', '.') }}</td>
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
                                        <form action="{{ route('sales.lazada.import.process') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-save"></i> Simpan Data
                                            </button>
                                        </form>
                                        <a href="{{ route('sales.lazada.import') }}" class="btn btn-secondary btn-lg ml-2">
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
                            <a href="{{ route('sales.lazada.import') }}" class="btn btn-primary">
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
