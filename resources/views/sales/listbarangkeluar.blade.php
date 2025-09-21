@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 font-weight-bold text-primary">Daftar Barang Keluar</h5>
                    </div>

                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb bg-light py-2 px-3 rounded">
                                        <li class="breadcrumb-item"><a href="{{ route('warehouse.index') }}"
                                                class="text-decoration-none">Menu Gudang</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Daftar Barang Keluar</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <!-- Filter Section -->
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-light py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
                            </div>
                            <div class="card-body pt-3 pb-4">
                                <form action="{{ route('sales.outgoing-items') }}" method="GET" id="filterForm">
                                    <div class="row mb-4">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="tanggal_mulai" class="font-weight-bold mb-2">Tanggal Keluar Mulai</label>
                                                <input type="date" class="form-control form-control-sm shadow-sm" id="tanggal_mulai" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="tanggal_akhir" class="font-weight-bold mb-2">Tanggal Keluar Akhir</label>
                                                <input type="date" class="form-control form-control-sm shadow-sm" id="tanggal_akhir" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="product_id" class="font-weight-bold mb-2">Produk</label>
                                                <select class="form-control form-control-sm shadow-sm" id="product_id" name="product_id">
                                                    <option value="">Semua Produk</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="search" class="font-weight-bold mb-2">Pencarian</label>
                                                <input type="text" class="form-control form-control-sm shadow-sm" id="search" name="search" value="{{ request('search') }}" placeholder="Cari nomor order, produk, PO...">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="per_page" class="font-weight-bold mb-2">Baris Per Halaman</label>
                                                <select class="form-control form-control-sm shadow-sm" id="per_page" name="per_page">
                                                    <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                                    <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-9 d-flex align-items-end justify-content-end">
                                            <button type="submit" class="btn btn-primary mr-3 px-4 shadow-sm">
                                                <i class="fas fa-search mr-1"></i> Cari
                                            </button>
                                            <a href="{{ route('sales.outgoing-items') }}" class="btn btn-secondary px-4 shadow-sm">
                                                <i class="fas fa-sync-alt mr-1"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive disable-fixed-scrollbar" style="max-height: 65vh; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-hover table-bordered" id="dataTable">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr class="bg-white">
                                        <th style="width: 40px; text-align: center;">No</th>
                                        <th style="width: 100px; text-align: center;">Tanggal Keluar</th>
                                        <th style="width: 100px; text-align: center;">Tanggal Penerimaan</th>
                                        <th style="width: 100px; text-align: center;">Tanggal ED</th>
                                        <th style="min-width: 250px; width: auto;">Produk</th>
                                        <th style="width: 60px; text-align: center;">Qty</th>
                                        <th style="width: 120px; text-align: right;">HPP Asli</th>
                                        <th style="width: 120px; text-align: right;">Total Diskon</th>
                                        
                                        <th style="width: 120px; text-align: right;">Total HPP</th>
                                        <th style="width: 120px; text-align: center;">Nomor PO</th>
                                        <th style="width: 180px; text-align: center;">Order</th>
                                        <th style="width: 200px;">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($barangKeluar as $index => $item)
                                        <tr>
                                            <td style="text-align: center;">{{ $index + 1 }}</td>
                                            <td style="text-align: center;">{{ $item->tanggal_keluar->format('d-m-Y') }}</td>
                                            <td style="text-align: center;">
                                                @if($item->warehouseStock && $item->warehouseStock->penerimaanDetail && $item->warehouseStock->penerimaanDetail->penerimaan)
                                                    {{ $item->warehouseStock->penerimaanDetail->penerimaan->tanggal_penerimaan->format('d-m-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="text-align: center;">
                                                @if($item->warehouseStock && $item->warehouseStock->expired_date)
                                                    {{ $item->warehouseStock->expired_date->format('d-m-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                {{ optional($item->warehouseStock->product)->name ?? 'N/A' }}
                                            </td>
                                            <td style="text-align: center; font-weight: 500;">{{ $item->qty }}</td>
                                            <td style="text-align: right;">
                                                @if($item->warehouseStock && $item->warehouseStock->penerimaanDetail)
                                                    @php
                                                        $detail = $item->warehouseStock->penerimaanDetail;
                                                        $hppAsli = $detail->harga_hpp;
                                                    @endphp
                                                    {{ number_format($hppAsli, 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="text-align: right;">
                                                @if($item->warehouseStock && $item->warehouseStock->penerimaanDetail)
                                                    @php
                                                        $detail = $item->warehouseStock->penerimaanDetail;
                                                        $hppAsli = $detail->harga_hpp;
                                                        $diskonPersen = 0;
                                                        $diskonNominal = 0;
                                                        $hppSetelahDiskon = $hppAsli;
                                                        
                                                        // Hitung diskon persentase bertingkat
                                                        if ($detail->diskon_persen_1 > 0) {
                                                            $nilaiDiskon = $hppSetelahDiskon * $detail->diskon_persen_1 / 100;
                                                            $diskonPersen += $nilaiDiskon;
                                                            $hppSetelahDiskon -= $nilaiDiskon;
                                                        }
                                                        if ($detail->diskon_persen_2 > 0) {
                                                            $nilaiDiskon = $hppSetelahDiskon * $detail->diskon_persen_2 / 100;
                                                            $diskonPersen += $nilaiDiskon;
                                                            $hppSetelahDiskon -= $nilaiDiskon;
                                                        }
                                                        if ($detail->diskon_persen_3 > 0) {
                                                            $nilaiDiskon = $hppSetelahDiskon * $detail->diskon_persen_3 / 100;
                                                            $diskonPersen += $nilaiDiskon;
                                                            $hppSetelahDiskon -= $nilaiDiskon;
                                                        }
                                                        if ($detail->diskon_persen_4 > 0) {
                                                            $nilaiDiskon = $hppSetelahDiskon * $detail->diskon_persen_4 / 100;
                                                            $diskonPersen += $nilaiDiskon;
                                                            $hppSetelahDiskon -= $nilaiDiskon;
                                                        }
                                                        if ($detail->diskon_persen_5 > 0) {
                                                            $nilaiDiskon = $hppSetelahDiskon * $detail->diskon_persen_5 / 100;
                                                            $diskonPersen += $nilaiDiskon;
                                                            $hppSetelahDiskon -= $nilaiDiskon;
                                                        }
                                                        
                                                        // Hitung diskon nominal
                                                        $diskonNominal = $detail->diskon_nominal_1 + $detail->diskon_nominal_2 + $detail->diskon_nominal_3 + $detail->diskon_nominal_4 + $detail->diskon_nominal_5;
                                                        $hppSetelahDiskon -= $diskonNominal;
                                                        
                                                        // Total diskon (persen + nominal)
                                                        $totalDiskon = $diskonPersen + $diskonNominal;
                                                        
                                                        // Jika harga jadi negatif, set ke 0
                                                        if ($hppSetelahDiskon < 0) $hppSetelahDiskon = 0;
                                                    @endphp
                                                    {{ number_format($totalDiskon, 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                          
                                            <td style="text-align: right;">
                                                @if($item->warehouseStock && $item->warehouseStock->penerimaanDetail)
                                                    @php
                                                        $qty = $item->qty;
                                                        $totalHpp = $hppSetelahDiskon * $qty;
                                                    @endphp
                                                    {{ number_format($totalHpp, 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="text-align: center;">
                                                @if($item->warehouseStock && $item->warehouseStock->penerimaanDetail && $item->warehouseStock->penerimaanDetail->penerimaan)
                                                    {{ $item->warehouseStock->penerimaanDetail->penerimaan->nomor_po ?? '-' }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="text-align: center; font-family: monospace; font-weight: 500;">
                                                {{ optional($item->orderItem?->order)->order_number ?? 'N/A' }}
                                            </td>
                                            <td>{{ $item->catatan ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted small">
                                Menampilkan {{ $barangKeluar->firstItem() ?? 0 }} - {{ $barangKeluar->lastItem() ?? 0 }} dari {{ $barangKeluar->total() }} data
                            </div>
                            <div>
                                {{ $barangKeluar->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        table.table {
            font-size: 12px;
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            font-weight: 600;
            vertical-align: middle;
            padding: 10px 8px;
            border-bottom: 2px solid #e3e6f0;
            white-space: nowrap;
            background-color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table td {
            vertical-align: middle;
            padding: 8px;
            border-top: 1px solid #e3e6f0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.04);
        }
        
        .form-control-sm {
            height: calc(1.5em + 0.75rem);
            padding: 0.25rem 0.7rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }
        
        .card {
            border-radius: 0.35rem;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .card-header {
            border-radius: calc(0.35rem - 1px) calc(0.35rem - 1px) 0 0 !important;
        }
        
        .btn {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            transition: all 0.15s ease-in-out;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }
        
        .btn-secondary:hover {
            background-color: #717384;
            border-color: #6b6d7d;
        }
        
        .breadcrumb {
            border-radius: 0.35rem;
            font-size: 0.85rem;
        }
        
        /* Tambahkan border radius ke semua input */
        .form-control, .custom-select, .btn {
            border-radius: 0.25rem;
        }

        /* Disable fixed table styling to prevent errors */
        .disable-fixed-scrollbar {
            max-height: 65vh;
            overflow-y: auto;
            overflow-x: auto;
        }

        /* Style untuk tampilan responsive */
        @media (max-width: 768px) {
            .table {
                font-size: 10px;
            }
            
            .table th, .table td {
                padding: 4px 3px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Hanya validasi untuk rentang tanggal
            $('#tanggal_mulai, #tanggal_akhir').on('change', function() {
                const tanggalMulai = $('#tanggal_mulai').val();
                const tanggalAkhir = $('#tanggal_akhir').val();
                
                if (tanggalMulai && tanggalAkhir && tanggalMulai > tanggalAkhir) {
                    alert('Tanggal akhir harus lebih besar dari tanggal mulai');
                    $('#tanggal_akhir').val('');
                }
            });
        });
    </script>
@endpush