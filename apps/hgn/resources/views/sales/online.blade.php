@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Penjualan Online') }}</div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Menu Penjualan</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.choose-type') }}">Pilih Tipe Penjualan</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Penjualan Online</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12 text-center">
                            <h4>Pilih Platform E-commerce</h4>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <img src="{{ asset('images/logo/shopee.png') }}" alt="Shopee" class="img-fluid mb-3" style="max-height: 80px;">
                                    <h5 class="card-title">Shopee Lamourad</h5>
                                    <a href="{{ route('sales.platform', ['platform' => 'shopee']) }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <img src="{{ asset('images/logo/tokopedia.png') }}" alt="Tokopedia" class="img-fluid mb-3" style="max-height: 80px;">
                                    <h5 class="card-title">Tokopedia</h5>
                                    <a href="{{ route('sales.platform', ['platform' => 'tokopedia']) }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <img src="{{ asset('images/logo/tiktok.png') }}" alt="TikTok" class="img-fluid mb-3" style="max-height: 80px;">
                                    <h5 class="card-title">Tiktok Lamourad</h5>
                                    <a href="{{ route('sales.platform', ['platform' => 'tiktok']) }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <img src="{{ asset('images/logo/blibli.png') }}" alt="Blibli" class="img-fluid mb-3" style="max-height: 80px;">
                                    <h5 class="card-title">Blibli</h5>
                                    <a href="{{ route('sales.platform', ['platform' => 'blibli']) }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Platform Cards -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <img src="{{ asset('images/logo/lazada.png') }}" alt="Lazada" class="img-fluid mb-3" style="max-height: 80px;">
                                    <h5 class="card-title">Lazada Lamourad</h5>
                                    <a href="{{ route('sales.platform', ['platform' => 'lazada']) }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <img src="{{ asset('images/logo/tiktok.png') }}" alt="TikTok2" class="img-fluid mb-3" style="max-height: 80px;">
                                    <h5 class="card-title">Tiktok Trubleu</h5>
                                    <a href="{{ route('sales.platform', ['platform' => 'tiktok2']) }}" class="btn btn-primary">Pilih</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <img src="{{ asset('images/logo/shopee.png') }}" alt="Shopee2" class="img-fluid mb-3" style="max-height: 80px;">
                                    <h5 class="card-title">Shopee Trubleu</h5>
                                    <a href="{{ route('sales.platform', ['platform' => 'shopee2']) }}" class="btn btn-primary">Pilih</a>
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