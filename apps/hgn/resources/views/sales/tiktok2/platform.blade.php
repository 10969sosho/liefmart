@extends('layouts.app')

@section('title', 'Platform Tiktok2')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Platform Tiktok2</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Menu Penjualan</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('sales.online') }}">Penjualan Online</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Platform Tiktok2</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-keyboard fa-3x mb-3 text-primary"></i>
                                        <h5 class="card-title">Input Manual</h5>
                                        <p class="card-text">Input data penjualan Tiktok2 secara manual</p>
                                        <a href="{{ route('sales.online-input', ['platform' => 'tiktok2']) }}"
                                            class="btn btn-primary mt-2">
                                            <i class="fas fa-plus-circle mr-2"></i> Input Manual
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-excel fa-3x mb-3 text-success"></i>
                                        <h5 class="card-title">Import Excel</h5>
                                        <p class="card-text">Import data penjualan Tiktok2 dari file Excel</p>
                                        <a href="{{ route('sales.tiktok2.import-excel') }}" class="btn btn-success mt-2">
                                            <i class="fas fa-file-import mr-2"></i> Import Excel
                                        </a>
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