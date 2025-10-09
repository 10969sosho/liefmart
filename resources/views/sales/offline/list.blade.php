@extends('layouts.app')

@section('content')
@php
    use App\Helpers\NumberFormatter;
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Daftar Penjualan Offline</h1>
            <p class="text-muted mb-0">Kategori: {{ session('main_category_name') }}</p>
        </div>
        <a href="{{ route('sales.offline.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Penjualan Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Filter Pencarian</h5>
                <small class="text-muted">Kategori: {{ session('main_category_name') }}</small>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('sales.offline.list') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_start" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" id="date_start" name="date_start" value="{{ request('date_start') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_end" class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" id="date_end" name="date_end" value="{{ request('date_end') }}">
                </div>
                <div class="col-md-3">
                    <label for="surat_jalan_number" class="form-label">Nomor Surat Jalan</label>
                    <input type="text" class="form-control" id="surat_jalan_number" name="surat_jalan_number" value="{{ request('surat_jalan_number') }}" placeholder="Cari nomor surat jalan...">
                </div>
                <div class="col-md-3">
                    <label for="No_PO" class="form-label">Nomor PO</label>
                    <input type="text" class="form-control" id="No_PO" name="No_PO" value="{{ request('No_PO') }}" placeholder="Cari nomor PO...">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('sales.offline.list') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Penjualan</h5>
                    <h2 class="display-5">{{ number_format($summary['total_sales']) }}</h2>
                    <p>
                        <i class="fas fa-calendar me-1"></i>
                        @if(request('date_start') && request('date_end'))
                            {{ \Carbon\Carbon::parse(request('date_start'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('date_end'))->format('d/m/Y') }}
                        @else
                            Semua Periode
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Value</h5>
                    <h2 class="display-5">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</h2>
                    <p>Rata-rata: Rp {{ number_format($summary['avg_order_value'], 0, ',', '.') }} per penjualan</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Volume</h5>
                    <h2 class="display-5">{{ number_format($summary['total_volume']) }} pcs</h2>
                    <p>Jumlah produk terjual</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Status Breakdown</h5>
                    <div class="d-flex justify-content-between">
                        @php
                            $paidCount = 0;
                            $partialCount = 0;
                            $pendingCount = 0;
                            $cancelledCount = 0;
                            
                            foreach ($offlineSales as $sale) {
                                if ($sale->status == 'cancelled') {
                                    $cancelledCount++;
                                } else {
                                    $paymentStatus = $sale->getPaymentStatus();
                                    if ($paymentStatus == 'paid') {
                                        $paidCount++;
                                    } elseif ($paymentStatus == 'partial') {
                                        $partialCount++;
                                    } else {
                                        $pendingCount++;
                                    }
                                }
                            }
                        @endphp
                        <div class="text-center">
                            <div class="h6">{{ $paidCount }}</div>
                            <small>Lunas</small>
                        </div>
                        <div class="text-center">
                            <div class="h6">{{ $partialCount }}</div>
                            <small>Sebagian</small>
                        </div>
                        <div class="text-center">
                            <div class="h6">{{ $pendingCount }}</div>
                            <small>Pending</small>
                        </div>
                        <div class="text-center">
                            <div class="h6">{{ $cancelledCount }}</div>
                            <small>Batal</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="table-responsive disable-fixed-scrollbar" style="max-height: 65vh; overflow-y: auto; overflow-x: auto;">
                <table class="table table-hover">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                        <tr class="bg-white">
                            <th scope="col">#</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">No. Surat Jalan</th>
                            <th scope="col">No. PO</th>
                            <th scope="col">Pelanggan</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($offlineSales as $sale)
                        <tr>
                            <td>{{ $loop->iteration + ($offlineSales->currentPage() - 1) * $offlineSales->perPage() }}</td>
                            <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td>{{ $sale->surat_jalan_number }}</td>
                            <td>{{ $sale->No_PO ?? '-' }}</td>
                            <td>{{ $sale->customer_name }}</td>
                            <td>{{ number_format(\App\Helpers\NumberFormatter::roundToTwoDecimals($sale->tax_amount > 0 ? $sale->total_amount : $sale->subtotal), 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $paymentStatus = $sale->getPaymentStatus();
                                @endphp
                                @if ($sale->status == 'cancelled')
                                    <span class="badge bg-danger">Dibatalkan</span>
                                @elseif ($paymentStatus == 'paid')
                                    <span class="badge bg-success">Lunas</span>
                                @elseif ($paymentStatus == 'partial')
                                    <span class="badge bg-info">Sebagian</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('sales.offline.show', $sale) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sales.offline.print.sj', $sale) }}" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-truck"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-3">Belum ada data penjualan offline</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $offlineSales->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Perbaikan untuk sticky header */
    .table-responsive thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table-light {
        background-color: white;
    }
    
    .table th {
        background-color: white;
    }
</style>
@endpush 