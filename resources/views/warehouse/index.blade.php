@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Daftar Item Unlocated</h4>
                    <div>
                        <a href="{{ route('warehouse.create') }}" class="btn btn-success">
                            <i class="fas fa-exchange-alt"></i> Transfer Semua Barang
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    <!-- Filter Form -->
                    <form action="{{ route('warehouse.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari..." name="search" value="{{ request('search') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" placeholder="Kode Penerimaan" name="kode_penerimaan" value="{{ request('kode_penerimaan') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" placeholder="Nama Produk" name="nama_produk" value="{{ request('nama_produk') }}">
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                                    Filter Lanjutan <i class="fas fa-caret-down"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="collapse mt-3" id="advancedFilters">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div>
                                        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                                        <a href="{{ route('warehouse.index') }}" class="btn btn-secondary">Reset</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Penerimaan</th>
                                    <th>Nama Produk</th>
                                    <th>Jumlah</th>
                                    <th>Satuan</th>
                                    <th>Tanggal Penerimaan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($unlocatedItems as $index => $item)
                                <tr>
                                    <td>{{ ($unlocatedItems->currentPage() - 1) * $unlocatedItems->perPage() + $loop->iteration }}</td>
                                    <td>{{ $item->penerimaan->nomor_po }}</td>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ number_format($item->remaining_qty, 0) }}</td>
                                    <td>{{ $item->satuan ? $item->satuan->name : 'N/A' }}</td>
                                    <td>{{ $item->penerimaan->tanggal_penerimaan->format('d/m/y') }}</td>
                                    <td>
                                        <a href="{{ route('warehouse.create', ['id' => $item->id]) }}" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-exchange-alt"></i> Pindahkan
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada barang yang tersedia</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing {{ $unlocatedItems->firstItem() ?? 0 }} to {{ $unlocatedItems->lastItem() ?? 0 }} of {{ $unlocatedItems->total() }} results
                        </div>
                        <div>
                            @if ($unlocatedItems->hasPages())
                                <ul class="pagination pagination-sm m-0">
                                    <li class="page-item {{ $unlocatedItems->onFirstPage() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $unlocatedItems->previousPageUrl() }}" tabindex="-1">« Previous</a>
                                    </li>
                                    
                                    @if($unlocatedItems->currentPage() > 3)
                                        <li class="page-item"><a class="page-link" href="{{ $unlocatedItems->url(1) }}">1</a></li>
                                        @if($unlocatedItems->currentPage() > 4)
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        @endif
                                    @endif
                                    
                                    @foreach(range(max(1, $unlocatedItems->currentPage() - 2), min($unlocatedItems->lastPage(), $unlocatedItems->currentPage() + 2)) as $page)
                                        <li class="page-item {{ $unlocatedItems->currentPage() == $page ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $unlocatedItems->url($page) }}">{{ $page }}</a>
                                        </li>
                                    @endforeach
                                    
                                    @if($unlocatedItems->currentPage() < $unlocatedItems->lastPage() - 2)
                                        @if($unlocatedItems->currentPage() < $unlocatedItems->lastPage() - 3)
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        @endif
                                        <li class="page-item"><a class="page-link" href="{{ $unlocatedItems->url($unlocatedItems->lastPage()) }}">{{ $unlocatedItems->lastPage() }}</a></li>
                                    @endif
                                    
                                    <li class="page-item {{ $unlocatedItems->hasMorePages() ? '' : 'disabled' }}">
                                        <a class="page-link" href="{{ $unlocatedItems->nextPageUrl() }}">Next »</a>
                                    </li>
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection