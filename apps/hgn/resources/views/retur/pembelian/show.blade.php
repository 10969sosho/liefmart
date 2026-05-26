@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="ds-card-header">
                    <h4 class="card-title">Detail Retur Pembelian</h4>
                    <div class="card-tools">
                        <a href="{{ route('retur-pembelian.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="{{ route('retur-pembelian.print', $returPembelian->id) }}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-print"></i> Cetak Invoice
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Kode Retur</th>
                                    <td>: {{ $returPembelian->kode_retur }}</td>
                                </tr>
                                <tr>
                                    <th>Nomor PO</th>
                                    <td>: {{ $returPembelian->penerimaan->nomor_po }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Penerimaan</th>
                                    <td>: {{ $returPembelian->penerimaan->tanggal_penerimaan->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Retur</th>
                                    <td>: {{ $returPembelian->tanggal_retur->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Tipe Retur</th>
                                    <td>: 
                                        @if($returPembelian->tipe_retur == 'sebagian')
                                        <span class="badge badge-warning text-dark">Sebagian</span>
                                        @elseif($returPembelian->tipe_retur == 'full')
                                        <span class="badge badge-danger">Full</span>
                                        @else
                                        <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Dibuat Oleh</th>
                                    <td>: {{ $returPembelian->user ? $returPembelian->user->name : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Dibuat Pada</th>
                                    <td>: {{ $returPembelian->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Catatan</th>
                                    <td>: {{ $returPembelian->catatan ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h5>Detail Barang Retur</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Barang</th>
                                    <th>Harga HPP</th>
                                    <th>Qty Retur</th>
                                    <th>Satuan</th>
                                    <th>Total Nominal</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $grandTotalNominal = 0;
                                @endphp
                                @forelse($returPembelian->details as $index => $detail)
                                @php
                                    // Calculate harga per unit after tiered discounts
                                    $hargaHpp = 0;
                                    if ($detail->penerimaanDetail) {
                                        $penerimaanDetail = $detail->penerimaanDetail;
                                        // Use subtotal (already includes all tiered discounts) divided by qty to get unit price
                                        if ($penerimaanDetail->qty > 0 && $penerimaanDetail->subtotal > 0) {
                                            $hargaHpp = $penerimaanDetail->subtotal / $penerimaanDetail->qty;
                                        } else {
                                            // Fallback: calculate from harga_hpp with discounts
                                            $hargaHpp = $penerimaanDetail->harga_hpp;
                                            // Apply percentage discounts in sequence (tiered discounts)
                                            for ($i = 1; $i <= 5; $i++) {
                                                $diskonPersen = $penerimaanDetail->{"diskon_persen_$i"} ?? 0;
                                                if ($diskonPersen > 0) {
                                                    $hargaHpp = $hargaHpp * (1 - $diskonPersen / 100);
                                                }
                                            }
                                            // Apply nominal discounts (per unit)
                                            for ($i = 1; $i <= 5; $i++) {
                                                $diskonNominal = $penerimaanDetail->{"diskon_nominal_$i"} ?? 0;
                                                if ($diskonNominal > 0) {
                                                    $hargaHpp = $hargaHpp - ($diskonNominal / $penerimaanDetail->qty);
                                                }
                                            }
                                        }
                                    }
                                    $totalNominal = $hargaHpp * $detail->qty;
                                    $grandTotalNominal += $totalNominal;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $detail->product->name }}</td>
                                    <td class="text-right">Rp {{ number_format($hargaHpp, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($detail->qty, 0) }}</td>
                                    <td>{{ $detail->satuan->name ?? '-' }}</td>
                                    <td class="text-right">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                                    <td>{{ $detail->alasan ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada detail barang</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="5" class="text-right">Total:</td>
                                    <td class="text-right">Rp {{ number_format($grandTotalNominal, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @php
                        // Get tax_category_id from penerimaan
                        $taxId = $returPembelian->penerimaan->tax_category_id ?? null;
                        
                        // Calculate DPP, PPN, and Grand Total
                        // grandTotalNominal adalah total retur (DPP retur)
                        $dpp = \App\Helpers\NumberFormatter::calculateDPP($grandTotalNominal);
                        $ppn = 0;
                        $grandTotal = $dpp;
                        
                        if ($taxId == 3) {
                            // PKP: Calculate PPN
                            // DPP = grandTotalNominal (total retur)
                            // DPP 11/12 = DPP * (11/12)
                            // PPN = DPP 11/12 * 12% = DPP * 0.11
                            $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
                            $ppn = \App\Helpers\NumberFormatter::calculatePPN($dpp11_12);
                            $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($dpp, $ppn);
                        } else {
                            // Non-PKP: No PPN
                            $dpp11_12 = 0;
                            $ppn = 0;
                            $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($dpp);
                        }
                        // grandTotal adalah nominal retur (pembayaran) = DPP + PPN
                    @endphp

                    <div class="card mt-3">
                        <div class="ds-card-header">
                            <h5 class="card-title mb-0">Rincian Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 offset-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="50%">DPP (Dasar Pengenaan Pajak)</th>
                                            <td class="text-right"><strong>Rp {{ number_format($dpp, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>PPN (11%)</th>
                                            <td class="text-right"><strong>Rp {{ number_format($ppn, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        <tr class="table-info">
                                            <th>TOTAL (DPP + PPN)</th>
                                            <td class="text-right"><strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <a href="{{ route('retur-pembelian.edit', $returPembelian->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Retur
                            </a>
                            <form action="{{ route('retur-pembelian.destroy', $returPembelian->id) }}" method="POST" class="d-inline ml-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus retur ini? Stok akan dikembalikan ke gudang.')">
                                    <i class="fas fa-trash"></i> Hapus Retur
                                </button>
                            </form>
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
    @media print {
        .card-header, .ds-card-header, .btn, .alert, .main-sidebar, .main-header, .main-footer {
            display: none !important;
        }
        .content-wrapper {
            margin-left: 0 !important;
        }
        body {
            margin: 20px;
        }
    }

    .badge {
        font-size: 90%;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 4px;
    }

    .badge-warning {
        background-color: #ffc107;
    }

    .badge-danger {
        background-color: #dc3545;
    }
</style>
@endpush 