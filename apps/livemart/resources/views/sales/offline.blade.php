@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Penjualan Offline') }}</div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12 text-center">
                            <h4>Silahkan pilih menu penjualan offline</h4>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <i class="fas fa-cart-plus fa-4x mb-3"></i>
                                    <h5 class="card-title">Buat Penjualan Baru</h5>
                                    <p class="card-text">Input transaksi penjualan offline baru</p>
                                    <a href="{{ route('sales.offline.create') }}" class="btn btn-primary">Buat Penjualan</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-4x mb-3"></i>
                                    <h5 class="card-title">Daftar Penjualan</h5>
                                    <p class="card-text">Lihat semua transaksi penjualan offline</p>
                                    <a href="{{ route('sales.offline.list') }}" class="btn btn-primary">Lihat Daftar</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('sales.choose-type') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 