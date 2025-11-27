@extends('layouts.app')

@push('styles')
<style>
    .table th {
        background-color: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: #495057;
    }
    
    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
        color: white !important;
    }
    
    .badge-info {
        background-color: #17a2b8 !important;
        color: white !important;
    }
    
    .badge-success {
        background-color: #28a745 !important;
        color: white !important;
    }
    
    .badge-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }
    
    .badge-secondary {
        background-color: #6c757d !important;
        color: white !important;
    }
    
    .badge-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }
    
    .text-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .btn-group .btn {
        margin-right: 2px;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .card-header .card-title {
        color: white;
    }
    
    .card-header .text-muted {
        color: rgba(255, 255, 255, 0.8) !important;
    }
    
    /* Fix text visibility */
    .table td {
        color: #212529 !important;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
    
    /* Better pagination */
    .pagination {
        justify-content: center;
        margin-top: 20px;
    }
    
    .pagination .page-link {
        color: #007bff;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        margin: 0 2px;
        border-radius: 0.25rem;
    }
    
    .pagination .page-link:hover {
        color: #0056b3;
        background-color: #e9ecef;
        border-color: #adb5bd;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
    
    /* Nama barang dengan word wrap */
    .product-name-cell {
        white-space: normal !important;
        word-wrap: break-word;
        word-break: break-word;
        max-width: 300px;
        min-width: 200px;
        line-height: 1.4;
        vertical-align: top;
        padding: 0.25rem 0.5rem !important; /* Kurangi padding */
    }
    
    /* Table responsive untuk nama panjang */
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Styling untuk nama barang yang panjang (trim leading spaces, preserve internal spaces) */
    .product-name {
        display: block;
        max-width: 100%;
        word-wrap: break-word;
        word-break: break-word;
        white-space: pre-wrap; /* preserve multiple spaces dan line breaks */
        line-height: 1.4;
        padding: 0.1rem 0; /* Kurangi padding */
        text-indent: 0; /* Pastikan tidak ada indent */
    }
    
    /* Trim leading spaces dengan CSS */
    .product-name::before {
        content: '';
        display: block;
        height: 0;
        overflow: hidden;
    }
    
    /* Variant dengan word wrap juga */
    .variant-cell {
        white-space: normal !important;
        word-wrap: break-word;
        word-break: break-word;
        max-width: 200px;
        min-width: 150px;
        line-height: 1.4;
        vertical-align: top;
        padding: 0.25rem 0.5rem !important; /* Kurangi padding */
    }
    
    /* Table responsive improvements */
    .table {
        table-layout: fixed;
        width: 100%;
    }
    
    /* Ensure table cells don't break layout */
    .table td, .table th {
        border: 1px solid #dee2e6;
        padding: 0.4rem 0.5rem; /* Kurangi padding dari 0.75rem */
        vertical-align: top;
    }
    
    /* Badge improvements for long text */
    .badge {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        display: inline-block;
        max-width: 100%;
        line-height: 1.2;
    }
    
    /* Hapus indikator spasi ganda; cukup tampilkan apa adanya */
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-1">Master Barang Platform</h3>
                            <p class="text-muted mb-0">Total: {{ $platformProducts->total() }} barang platform</p>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('barang-platform.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Barang Platform
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Platform</label>
                                    <select name="platform" class="form-select">
                                        <option value="">Semua Platform</option>
                                        @foreach($platforms as $platform)
                                            <option value="{{ $platform->name }}" {{ request('platform') == $platform->name ? 'selected' : '' }}>
                                                {{ $platform->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Nama Barang</label>
                                    <input type="text" name="search" class="form-control" placeholder="Cari nama barang..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Variant</label>
                                    <input type="text" name="variant" class="form-control" placeholder="Cari variant..." value="{{ request('variant') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Per Halaman</label>
                                    <select name="per_page" class="form-select">
                                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                        <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <a href="{{ route('barang-platform.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-undo"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="12%">Platform</th>
                                    <th width="40%">Nama Barang</th>
                                    <th width="18%">Variant</th>
                                    <th width="8%">Mapping</th>
                                    <th width="10%">Digunakan di Sales</th>
                                    <th width="7%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($platformProducts as $index => $product)
                                    <tr>
                                        <td>{{ $platformProducts->firstItem() + $index }}</td>
                                        <td>
                                            @if($product->platform)
                                                <span class="badge badge-info text-white">{{ $product->platform->name }}</span>
                                            @else
                                                <span class="badge badge-danger text-white">Platform tidak ditemukan</span>
                                            @endif
                                        </td>
                                        <td class="product-name-cell">
                                            <div class="product-name" title="{{ $product->platform_product_name }}">
                                                {{ $product->platform_product_name }}
                                            </div>
                                        </td>
                                        <td class="variant-cell">
                                            @if($product->variant)
                                                <span class="badge badge-secondary text-white" style="white-space: normal; word-wrap: break-word; display: inline-block; max-width: 100%;">{{ $product->variant }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($product->mappingBarang->count() > 0)
                                                <span class="badge badge-success text-white">
                                                    <i class="fas fa-link"></i> {{ $product->mappingBarang->count() }}
                                                </span>
                                            @else
                                                <span class="badge badge-warning text-dark">
                                                    <i class="fas fa-unlink"></i> 0
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $usedInSales = \DB::table('order_items')
                                                    ->where('platform_product_id', $product->id)
                                                    ->exists();
                                            @endphp
                                            @if($usedInSales)
                                                <span class="badge badge-success text-white">
                                                    <i class="fas fa-check"></i> Ya
                                                </span>
                                            @else
                                                <span class="badge badge-secondary text-white">
                                                    <i class="fas fa-times"></i> Belum
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('barang-platform.edit', $product->id) }}" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('barang-platform.destroy', $product->id) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Yakin ingin menghapus?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="pagination-info">
                            <span class="text-muted">
                                Menampilkan {{ $platformProducts->firstItem() ?? 0 }} sampai {{ $platformProducts->lastItem() ?? 0 }} 
                                dari {{ $platformProducts->total() }} hasil
                            </span>
                        </div>
                        <div class="pagination-wrapper">
                            {{ $platformProducts->appends(request()->query())->links('pagination.custom') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to trim leading spaces but preserve internal spaces
    function trimLeadingSpaces() {
        const productNames = document.querySelectorAll('.product-name');
        
        productNames.forEach(function(element) {
            const originalText = element.textContent;
            
            // Trim leading spaces but keep internal spaces
            const trimmedText = originalText.replace(/^\s+/, '');
            
            // Only update if there were leading spaces
            if (trimmedText !== originalText) {
                element.textContent = trimmedText;
            }
        });
    }
    
    // Run the function when page loads
    trimLeadingSpaces();
    
    // Also run when table content changes (for pagination, etc.)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                trimLeadingSpaces();
            }
        });
    });
    
    // Observe the table for changes
    const table = document.querySelector('.table');
    if (table) {
        observer.observe(table, { childList: true, subtree: true });
    }
});
</script>
@endpush
