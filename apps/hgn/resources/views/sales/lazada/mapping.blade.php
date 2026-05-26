@extends('layouts.app')

@section('title', 'Lazada - Mapping Barang')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-link"></i> Mapping Barang Lazada
                    </h3>
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

                    @if($platformProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Platform Product</th>
                                        <th>Variant</th>
                                        <th>Map ke Produk</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($platformProducts as $index => $platformProduct)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $platformProduct->platform_product_name }}</td>
                                            <td>{{ $platformProduct->variant ?? '-' }}</td>
                                            <td>
                                                <form action="{{ route('sales.lazada.mapping.store') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="platform_product_id" value="{{ $platformProduct->id }}">
                                                    <div class="form-group mb-0">
                                                        <select name="product_id" class="form-control" required>
                                                            <option value="">Pilih Produk</option>
                                                            @foreach($products as $product)
                                                                <option value="{{ $product->id }}">
                                                                    {{ $product->nama_produk }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                            </td>
                                            <td>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-link"></i> Map
                                                </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Semua platform product sudah di-mapping atau belum ada platform product.
                        </div>
                    @endif

                    <div class="mt-3">
                        <a href="{{ route('sales.lazada.platform-product') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Platform Product
                        </a>
                        <a href="{{ route('sales.lazada.platform') }}" class="btn btn-primary">
                            <i class="fas fa-home"></i> Ke Halaman Utama Lazada
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
