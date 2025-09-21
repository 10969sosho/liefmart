@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center pb-0">
                    <h6 class="mb-0">Data Brand</h6>
                    <a href="{{ route('brands.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Tambah Brand
                    </a>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    @if(session('success'))
                        <div class="alert alert-success mx-4 mt-3">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger mx-4 mt-3">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive disable-fixed-scrollbar p-0" style="max-height: 65vh; overflow-y: auto; overflow-x: auto;">
                        <table class="table align-items-center mb-0">
                            <thead style="position: sticky; top: 0; z-index: 1;">
                                <tr class="bg-white">
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Brand</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Kategori Utama</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($brands as $index => $brand)
                                <tr>
                                    <td class="ps-4">
                                        <p class="text-xs font-weight-bold mb-0">{{ $brands->firstItem() + $index }}</p>
                                    </td>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $brand->name }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ \Illuminate\Support\Str::limit($brand->description, 50) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-secondary text-xs font-weight-bold">
                                            @if($brand->mainCategory)
                                                {{ $brand->mainCategory->name }}
                                            @elseif($brand->main_category_id)
                                                Category ID: {{ $brand->main_category_id }} (Not Found)
                                            @else
                                                Tidak ada
                                            @endif
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-sm {{ $brand->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $brand->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="{{ route('brands.edit', $brand->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('brands.show', $brand->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('brands.destroy', $brand->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data brand</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center px-4 pt-4">
                        <p class="text-sm text-muted mb-0">
                            Menampilkan {{ $brands->firstItem() ?? 0 }} - {{ $brands->lastItem() ?? 0 }} dari {{ $brands->total() }} data
                        </p>
                        <div class="pagination-container">
                            {{ $brands->links('pagination::bootstrap-5') }}
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