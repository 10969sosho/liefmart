@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="m-0">Daftar Barang Keluar</h5>
                    </div>

                    <div class="card-body">
                        <div class="ds-filter-card">
                            <div class="ds-filter-title">Filter Data</div>
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

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive disable-fixed-scrollbar">
                            <table class="table table-hover table-bordered" id="dataTable">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Tanggal Keluar</th>
                                        <th class="text-center">Tanggal Penerimaan</th>
                                        <th class="text-center">Tanggal ED</th>
                                        <th>Produk</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">HPP Asli</th>
                                        <th class="text-end">Total Diskon</th>
                                        <th class="text-end">Total HPP</th>
                                        <th class="text-center">Nomor PO</th>
                                        <th class="text-center">Order</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($barangKeluar as $index => $item)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td class="text-center">{{ $item->tanggal_keluar->format('d-m-Y') }}</td>
                                            <td class="text-center">
                                                @if($item->warehouseStock && $item->warehouseStock->penerimaanDetail && $item->warehouseStock->penerimaanDetail->penerimaan)
                                                    {{ $item->warehouseStock->penerimaanDetail->penerimaan->tanggal_penerimaan->format('d-m-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($item->warehouseStock && $item->warehouseStock->expired_date)
                                                    {{ $item->warehouseStock->expired_date->format('d-m-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                {{ optional($item->warehouseStock->product)->name ?? 'N/A' }}
                                            </td>
                                            <td class="text-center fw-medium">{{ $item->qty }}</td>
                                            <td class="text-end">
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
                                            <td class="text-end">
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
                                          
                                            <td class="text-end">
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
                                            <td class="text-center">
                                                @if($item->warehouseStock && $item->warehouseStock->penerimaanDetail && $item->warehouseStock->penerimaanDetail->penerimaan)
                                                    {{ $item->warehouseStock->penerimaanDetail->penerimaan->nomor_po ?? '-' }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-center font-monospace fw-medium">
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
        .table-responsive {
            max-height: 65vh;
            overflow-y: auto;
            overflow-x: auto;
        }

        table.table {
            font-size: 12px;
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