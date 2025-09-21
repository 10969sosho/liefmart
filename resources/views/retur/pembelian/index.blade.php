@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Daftar Retur Pembelian</h4>
                    <div class="card-tools">
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
                                    <th>User</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returPembelians as $retur)
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
                                    <td colspan="7" class="text-center">Tidak ada data retur pembelian</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $returPembelians->links() }}
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
</style>
@endpush 