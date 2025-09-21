@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Daftar Varian Produk</h6>
                    <a href="{{ route('product-variants.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Tambah Varian Produk
                    </a>
                </div>
                
                <div class="card-body px-0 pt-0 pb-2">
                    @if(session('success'))
                        <div class="alert alert-success mx-4 mt-3" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger mx-4 mt-3" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Varian</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ukuran</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productVariants as $index => $variant)
                                <tr>
                                    <td class="ps-4">
                                        <span class="text-secondary text-xs font-weight-bold">{{ $productVariants->firstItem() + $index }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $variant->name }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-secondary text-xs font-weight-bold">
                                            {{ $variant->productSize->name ?? 'Tidak ada' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm {{ $variant->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $variant->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="{{ route('product-variants.edit', $variant->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('product-variants.show', $variant->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('product-variants.destroy', $variant->id) }}" method="POST" 
                                            class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">Tidak ada data varian produk</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                        <div class="d-flex justify-content-between align-items-center px-4 pt-4">
                            <p class="text-sm text-muted mb-0">
                                Menampilkan {{ $productVariants->firstItem() ?? 0 }} - {{ $productVariants->lastItem() ?? 0 }} dari {{ $productVariants->total() }} data
                            </p>
                            <div class="pagination-container">
                                {{ $productVariants->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Enhanced pagination styling */
    .pagination-container .pagination {
        margin-bottom: 0;
    }
    
    .pagination .page-item .page-link {
        color: #5e72e4;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        margin: 0 2px;
        transition: all 0.2s ease;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #5e72e4;
        border-color: #5e72e4;
        color: white;
        box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
    }
    
    .pagination .page-item .page-link:hover {
        background-color: #f6f9fc;
        transform: translateY(-1px);
    }
    
    .pagination .page-item.active .page-link:hover {
        background-color: #5e72e4;
        transform: none;
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .d-flex.justify-content-between.align-items-center {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .d-flex.justify-content-between.align-items-center > p {
            margin-bottom: 1rem;
        }
        
        .pagination-container {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 15px;
        }
    }
</style>
@endsection 