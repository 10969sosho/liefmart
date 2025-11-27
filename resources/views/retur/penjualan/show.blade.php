@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Detail Retur Penjualan</h4>
                    <div class="card-tools">
                        <a href="{{ route('retur-penjualan.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="#" onclick="window.print()" class="btn btn-info">
                            <i class="fas fa-print"></i> Cetak
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

                    @if($returPenjualan->status == 'selesai')
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle mr-2"></i> Retur penjualan ini telah selesai diproses. Jumlah barang pada order telah dikurangi. Barang kondisi BAGUS masuk ke warehouse, kondisi RUSAK masuk ke warehouse rusak, dan kondisi HILANG tidak masuk ke warehouse.
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
                                    <td>: {{ $returPenjualan->kode_retur }}</td>
                                </tr>
                                <tr>
                                    <th>Nomor Order</th>
                                    <td>: {{ $returPenjualan->order->order_number }}</td>
                                </tr>
                                <tr>
                                    <th>Platform</th>
                                    <td>: {{ $returPenjualan->order->platform->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Retur</th>
                                    <td>: {{ $returPenjualan->tanggal_retur->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>: 
                                        @if($returPenjualan->status == 'draft')
                                        <span class="status-badge status-draft">Draft</span>
                                        @elseif($returPenjualan->status == 'selesai')
                                        <span class="status-badge status-selesai">Selesai</span>
                                        @elseif($returPenjualan->status == 'dibatalkan')
                                        <span class="status-badge status-dibatalkan">Dibatalkan</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Catatan</th>
                                    <td>: {{ $returPenjualan->catatan ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Dibuat Oleh</th>
                                    <td>: {{ $returPenjualan->user->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Dibuat</th>
                                    <td>: {{ $returPenjualan->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Update</th>
                                    <td>: {{ $returPenjualan->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <h5>Detail Barang</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Barang</th>
                                    <th class="text-center">Qty Retur</th>
                                    <th class="text-right">Harga Produk</th>
                                    <th class="text-right">Total Harga</th>
                                    <th>Status</th>
                                    <th>Alasan</th>
                                    <th>Tindakan pada Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returPenjualan->details as $index => $detail)
                                @php
                                    // Use correct retur logic based on mapping quantity
                                    if (!$detail->orderItem) {
                                        $pricePerIndividualProduct = 0;
                                    } else {
                                        $orderItem = $detail->orderItem;
                                        $platformProduct = $orderItem->platformProduct;
                                        
                                        if (!$platformProduct || !$platformProduct->mappingBarang) {
                                            // If no mapping, use original price
                                            $pricePerIndividualProduct = $orderItem->price_after_discount;
                                        } else {
                                            // Calculate total quantity in the package from mapping
                                            $totalPackageQty = $platformProduct->mappingBarang
                                                ->where('is_active', true)
                                                ->sum('quantity');
                                            
                                            if ($totalPackageQty > 1) {
                                                // If package contains more than 1 item, divide the price
                                                $pricePerIndividualProduct = $orderItem->price_after_discount / $totalPackageQty;
                                            } else {
                                                // If package contains only 1 item, use original price
                                                $pricePerIndividualProduct = $orderItem->price_after_discount;
                                            }
                                        }
                                    }
                                    
                                    $totalHargaDetail = $pricePerIndividualProduct * $detail->qty;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $detail->product->name ?? 'Produk tidak ditemukan' }}</strong>
                                        @if($detail->product && $detail->product->sku)
                                        <br><small class="text-muted">SKU: {{ $detail->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($detail->qty, 2) }}</td>
                                    <td class="text-right">Rp {{ number_format($pricePerIndividualProduct, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($totalHargaDetail, 0, ',', '.') }}</td>
                                    <td>
                                        @if($detail->kondisi === 'RUSAK')
                                        <span class="item-status status-rusak">RUSAK</span>
                                        @elseif($detail->kondisi === 'HILANG')
                                        <span class="item-status status-hilang">HILANG</span>
                                        @else
                                        <span class="item-status status-baik">BAGUS</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->alasan ?? '-' }}</td>
                                    <td>
                                        @if($detail->kondisi === 'BAGUS')
                                        <span class="text-success">Ditambahkan ke warehouse</span>
                                        @elseif($detail->kondisi === 'RUSAK')
                                        <span class="text-warning">Ditambahkan ke warehouse rusak</span>
                                        @else
                                        <span class="text-danger">Tidak ditambahkan ke warehouse</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">Tidak ada detail retur</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <div class="btn-group">
                            @if($returPenjualan->status == 'draft')
                            <a href="{{ route('retur-penjualan.edit', $returPenjualan->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('retur-penjualan.process', $returPenjualan->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm('Yakin ingin memproses retur ini? Tindakan ini akan menyesuaikan stok sesuai kondisi barang: BAGUS masuk warehouse, RUSAK masuk warehouse rusak, HILANG tidak masuk warehouse.')">
                                    <i class="fas fa-check"></i> Proses Retur
                                </button>
                            </form>
                            <form action="{{ route('retur-penjualan.cancel', $returPenjualan->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin membatalkan retur ini?')">
                                    <i class="fas fa-times"></i> Batalkan
                                </button>
                            </form>
                            @elseif($returPenjualan->status == 'selesai')
                            <a href="{{ route('retur-penjualan.print', $returPenjualan->id) }}" class="btn btn-primary" target="_blank">
                                <i class="fas fa-print"></i> Print Invoice Retur
                            </a>
                            <form action="{{ route('retur-penjualan.reverse', $returPenjualan->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('PERINGATAN: Batal retur akan mengembalikan SEMUA perubahan ke kondisi semula (qty item dan stok warehouse). Yakin ingin membatalkan retur ini?')">
                                    <i class="fas fa-undo"></i> Batal Retur
                                </button>
                            </form>
                            @endif
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
    
    .item-status {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-weight: 600;
        text-align: center;
        min-width: 90px;
        font-size: 0.85rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .status-rusak {
        background-color: #dc3545;
        color: white;
        border: 1px solid #c82333;
    }
    
    .status-baik {
        background-color: #28a745;
        color: white;
        border: 1px solid #218838;
    }

    .status-hilang {
        background-color: #6c757d;
        color: white;
        border: 1px solid #5a6268;
    }

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
</style>
@endpush 