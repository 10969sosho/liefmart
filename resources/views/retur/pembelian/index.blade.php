@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Daftar Retur Pembelian</h4>
                    <div class="card-tools d-flex gap-2">
                        <a href="{{ route('retur-pembelian.export') }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="{{ route('retur-pembelian.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Buat Retur Baru
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

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Kode Retur</th>
                                    <th>Nomor PO</th>
                                    <th>Tanggal Penerimaan</th>
                                    <th>Tanggal Retur</th>
                                    <th>Tipe Retur</th>
                                    <th>Total Qty</th>
                                    <th>Total Nominal</th>
                                    <th>User</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returPembelians as $retur)
                                @php
                                    $totalQty = $retur->details->sum('qty');
                                    $totalNominal = $retur->details->sum(function($detail) {
                                        if ($detail->penerimaanDetail) {
                                            return $detail->penerimaanDetail->harga_hpp * $detail->qty;
                                        }
                                        return 0;
                                    });
                                @endphp
                                <tr>
                                    <td>{{ $retur->kode_retur }}</td>
                                    <td>{{ $retur->penerimaan->nomor_po }}</td>
                                    <td>{{ $retur->penerimaan->tanggal_penerimaan->format('d/m/Y') }}</td>
                                    <td>{{ $retur->tanggal_retur->format('d/m/Y') }}</td>
                                    <td>
                                        @if($retur->tipe_retur == 'sebagian')
                                        <span class="badge badge-warning text-dark">Sebagian</span>
                                        @elseif($retur->tipe_retur == 'full')
                                        <span class="badge badge-danger">Full</span>
                                        @else
                                        <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($totalQty, 0) }}</td>
                                    <td>Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                                    <td>{{ $retur->user ? $retur->user->name : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('retur-pembelian.show', $retur->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <form action="{{ route('retur-pembelian.destroy', $retur->id) }}" method="POST" class="d-inline ml-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus retur ini? Stok akan dikembalikan ke gudang.')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data retur pembelian</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <nav aria-label="Pagination Navigation">
                            <ul class="pagination justify-content-center">
                                {{-- Previous Page Link --}}
                                @if ($returPembelians->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $returPembelians->previousPageUrl() }}" rel="prev">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($returPembelians->getUrlRange(1, $returPembelians->lastPage()) as $page => $url)
                                    @if ($page == $returPembelians->currentPage())
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($returPembelians->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $returPembelians->nextPageUrl() }}" rel="next">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                        
                        {{-- Pagination Info --}}
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Showing {{ $returPembelians->firstItem() }} to {{ $returPembelians->lastItem() }} of {{ $returPembelians->total() }} results
                            </small>
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
    .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .btn-group form {
        margin-bottom: 5px;
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
    
    @media (max-width: 768px) {
        .btn-group {
            flex-direction: column;
        }
    }
    
    /* Custom Pagination Styling */
    .pagination {
        margin-bottom: 0;
    }
    
    .page-link {
        color: #495057;
        background-color: #fff;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        margin: 0 2px;
        border-radius: 0.375rem;
        transition: all 0.15s ease-in-out;
        font-weight: 500;
    }
    
    .page-link:hover {
        color: #0056b3;
        background-color: #e9ecef;
        border-color: #adb5bd;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,123,255,0.3);
    }
    
    .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
        cursor: not-allowed;
    }
    
    .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: none;
    }
    
    .pagination .page-link i {
        font-size: 0.875rem;
    }
</style>
@endpush 