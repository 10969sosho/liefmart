@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Detail Retur Pembelian</h4>
                    <div class="card-tools">
                        <a href="{{ route('retur-pembelian.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Cetak
                        </button>
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
                                    <th>Qty Retur</th>
                                    <th>Satuan</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returPembelian->details as $index => $detail)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $detail->product->name }}</td>
                                    <td>{{ $detail->qty }}</td>
                                    <td>{{ $detail->satuan->name ?? '-' }}</td>
                                    <td>{{ $detail->alasan ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada detail barang</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <form action="{{ route('retur-pembelian.destroy', $returPembelian->id) }}" method="POST" class="d-inline">
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
        .card-header, .btn, .alert, .main-sidebar, .main-header, .main-footer {
            display: none !important;
        }
        .content-wrapper {
            margin-left: 0 !important;
        }
        body {
            margin: 20px;
        }
    }
    
    /* Badge styling */
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
    
    /* Button styling improvements */
    .btn {
        margin-right: 5px;
    }
    
    .d-inline.ml-2 {
        margin-left: 8px !important;
    }
    
    @media (max-width: 768px) {
        .col-md-12.text-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        
        .col-md-12.text-right form,
        .col-md-12.text-right a {
            margin-bottom: 10px;
            width: 100%;
        }
        
        .d-inline.ml-2 {
            margin-left: 0 !important;
            margin-top: 10px;
        }
    }
</style>
@endpush 