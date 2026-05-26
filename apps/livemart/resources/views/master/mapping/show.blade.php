@extends('layouts.app')

@push('styles')
@include('master.mapping.styles')
@endpush

@section('content')
<div class="container-fluid">
    <div class="mapping-card">
        <div class="card-header">
            <h5 class="mb-0">Detail Mapping Produk</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <h6 class="section-heading">Informasi Platform</h6>
                    <table class="table table-info">
                        <tr>
                            <th>Platform</th>
                            <td>
                                <span class="badge" style="background-color: var(--{{ $mapping->platformProduct->platform ? (strtolower($mapping->platformProduct->platform->name) == 'shopee' ? 'warning-color' : (strtolower($mapping->platformProduct->platform->name) == 'tiktok' ? 'dark-color' : 'info-color')) : 'info-color' }}); color: var(--text-color);">
                                    {{ $mapping->platformProduct->platform ? $mapping->platformProduct->platform->name : 'Unknown Platform' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Nama Produk Platform</th>
                            <td>{{ $mapping->platformProduct->platform_product_name }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="section-heading">Informasi Produk Master</h6>
                    <table class="table table-info">
                        <tr>
                            <th>Nama Produk</th>
                            <td>{{ $mapping->product ? $mapping->product->name : 'Product tidak ditemukan (ID: ' . $mapping->product_id . ')' }}</td>
                        </tr>
                        <tr>
                            <th>Quantity Ratio</th>
                            <td>
                                <span class="badge bg-light text-dark">{{ $mapping->quantity }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('master.mapping.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('master.mapping.edit', $mapping->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
</div>
@endsection