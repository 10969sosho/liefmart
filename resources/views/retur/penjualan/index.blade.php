@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Daftar Retur Penjualan</h4>
                    <div class="card-tools d-flex gap-2">
                        <a href="{{ route('retur-penjualan.export') }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="{{ route('retur-penjualan.create') }}" class="btn btn-primary">
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
                                    <th>Nomor Order</th>
                                    <th>No. Resi</th>
                                    <th>Platform</th>
                                    <th>Tanggal Retur</th>
                                    <th>Status</th>
                                    <th>User</th>
                                    <th>Total Produk</th>
                                    <th>Total Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returPenjualans as $retur)
                                @php
                                    // Get tracking number from order items
                                    $trackingNumbers = $retur->order->orderItems->pluck('tracking_number')->filter()->unique();
                                    $resi = $trackingNumbers->count() > 0 ? $trackingNumbers->implode(', ') : '-';
                                    
                                    $totalProduk = $retur->details->sum('qty');
                                    // Calculate total price using corrected individual product prices
                                    $totalHarga = $retur->details->sum(function($d) {
                                        if (!$d->orderItem) return 0;
                                        
                                        // Get the platform product and its mappings
                                        $platformProduct = $d->orderItem->platformProduct;
                                        if (!$platformProduct || !$platformProduct->mappingBarang) return 0;
                                        
                                        // Calculate total quantity in the package
                                        $totalPackageQty = $platformProduct->mappingBarang->sum('quantity');
                                        
                                        // Calculate price per individual product
                                        $pricePerIndividualProduct = $totalPackageQty > 0 ? 
                                            $d->orderItem->price_after_discount / $totalPackageQty : 
                                            $d->orderItem->price_after_discount;
                                        
                                        return $pricePerIndividualProduct * $d->qty;
                                    });
                                @endphp
                                <tr>
                                    <td>{{ $retur->kode_retur }}</td>
                                    <td>{{ $retur->order->order_number }}</td>
                                    <td>{{ $resi }}</td>
                                    <td>{{ $retur->order->platform->name ?? '-' }}</td>
                                    <td>{{ $retur->tanggal_retur->format('d/m/Y') }}</td>
                                    <td>
                                        @if($retur->status == 'draft')
                                        <span class="status-badge status-draft">Draft</span>
                                        @elseif($retur->status == 'selesai')
                                        <span class="status-badge status-selesai">Selesai</span>
                                        @elseif($retur->status == 'dibatalkan')
                                        <span class="status-badge status-dibatalkan">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td>{{ $retur->user->name }}</td>
                                    <td>{{ $totalProduk }}</td>
                                    <td>Rp {{ number_format($totalHarga, 0, ',', '.') }} <!-- Updated: {{ time() }} --></td>
                                    <td>
                                        <a href="{{ route('retur-penjualan.show', $retur->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        @if($retur->status == 'draft')
                                        <div class="btn-group mt-1">
                                            <a href="{{ route('retur-penjualan.edit', $retur->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form action="{{ route('retur-penjualan.process', $retur->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Yakin ingin memproses retur ini?')">
                                                    <i class="fas fa-check"></i> Proses
                                                </button>
                                            </form>
                                            <form action="{{ route('retur-penjualan.cancel', $retur->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin membatalkan retur ini?')">
                                                    <i class="fas fa-times"></i> Batalkan
                                                </button>
                                            </form>
                                        </div>
                                        @elseif($retur->status == 'selesai')
                                        <form action="{{ route('retur-penjualan.reverse', $retur->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('PERINGATAN: Batal retur akan mengembalikan SEMUA perubahan ke kondisi semula (qty item dan stok warehouse). Yakin ingin membatalkan retur ini?')">
                                                <i class="fas fa-undo"></i> Batal Retur
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="10" class="p-0">
                                        <div class="table-responsive m-0">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Nama Produk</th>
                                                        <th>Harga Produk</th>
                                                        <th>Qty</th>
                                                        <th>Total Harga</th>
                                                        <th>Kondisi</th>
                                                        <th>Alasan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($retur->details as $detail)
                                                    @php
                                                        // Calculate correct price per individual product for paket
                                                        if (!$detail->orderItem) {
                                                            $pricePerProduct = 0;
                                                        } else {
                                                            $platformProduct = $detail->orderItem->platformProduct;
                                                            if (!$platformProduct || !$platformProduct->mappingBarang) {
                                                                $pricePerProduct = $detail->orderItem->price_after_discount;
                                                            } else {
                                                                // Calculate total quantity in the package
                                                                $totalPackageQty = $platformProduct->mappingBarang->sum('quantity');
                                                                
                                                                // Calculate price per individual product
                                                                $pricePerProduct = $totalPackageQty > 0 ? 
                                                                    $detail->orderItem->price_after_discount / $totalPackageQty : 
                                                                    $detail->orderItem->price_after_discount;
                                                            }
                                                        }
                                                        
                                                        $totalPriceDetail = $pricePerProduct * $detail->qty;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $detail->orderItem->platformProduct->platform_product_name ?? $detail->product->name ?? '-' }}</td>
                                                        <td>Rp {{ number_format($pricePerProduct, 0, ',', '.') }}</td>
                                                        <td>{{ $detail->qty }}</td>
                                                        <td>Rp {{ number_format($totalPriceDetail, 0, ',', '.') }}</td>
                                                        <td>{{ $detail->kondisi }}</td>
                                                        <td>{{ $detail->alasan ?? '-' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center">Tidak ada data retur penjualan</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <nav aria-label="Pagination Navigation">
                            <ul class="pagination justify-content-center">
                                {{-- Previous Page Link --}}
                                @if ($returPenjualans->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $returPenjualans->previousPageUrl() }}" rel="prev">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($returPenjualans->getUrlRange(1, $returPenjualans->lastPage()) as $page => $url)
                                    @if ($page == $returPenjualans->currentPage())
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
                                @if ($returPenjualans->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $returPenjualans->nextPageUrl() }}" rel="next">
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
                                Showing {{ $returPenjualans->firstItem() }} to {{ $returPenjualans->lastItem() }} of {{ $returPenjualans->total() }} results
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
    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        text-align: center;
        min-width: 100px;
        font-size: 0.9rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .status-draft {
        background-color: #ffc107;
        color: #212529;
        border: 1px solid #e0a800;
    }
    
    .status-selesai {
        background-color: #28a745;
        color: white;
        border: 1px solid #218838;
    }
    
    .status-dibatalkan {
        background-color: #dc3545;
        color: white;
        border: 1px solid #c82333;
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