@extends('layouts.app')

@section('title', 'Lazada - Platform Product')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-box"></i> Platform Product Lazada
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addProductModal">
                            <i class="fas fa-plus"></i> Tambah Product
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

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Produk</th>
                                    <th>Variant</th>
                                    <th>Status Mapping</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($platformProducts as $index => $product)
                                    <tr>
                                        <td>{{ $platformProducts->firstItem() + $index }}</td>
                                        <td>{{ $product->platform_product_name }}</td>
                                        <td>{{ $product->variant ?? '-' }}</td>
                                        <td>
                                            @if($product->mappingBarang)
                                                <span class="badge badge-success">Sudah di-mapping</span>
                                            @else
                                                <span class="badge badge-warning">Belum di-mapping</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('sales.lazada.mapping') }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-link"></i> Mapping
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada platform product</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $platformProducts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Product -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Platform Product</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('sales.lazada.platform-product.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="platform_product_name">Nama Produk</label>
                        <input type="text" class="form-control" id="platform_product_name" name="platform_product_name" required>
                    </div>
                    <div class="form-group">
                        <label for="variant">Variant</label>
                        <input type="text" class="form-control" id="variant" name="variant">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
