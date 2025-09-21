@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Pilih Tipe Penjualan') }}</div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12 text-center">
                            <h4>Silahkan pilih tipe penjualan yang ingin diinput</h4>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <i class="fas fa-store fa-4x mb-3"></i>
                                    <h5 class="card-title">Penjualan Offline</h5>
                                    <p class="card-text">Input transaksi penjualan dari toko fisik</p>
                                    <a href="{{ route('sales.offline') }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <i class="fas fa-globe fa-4x mb-3"></i>
                                    <h5 class="card-title">Penjualan Online</h5>
                                    <p class="card-text">Input transaksi penjualan dari platform online</p>
                                    <a href="{{ route('sales.online') }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection