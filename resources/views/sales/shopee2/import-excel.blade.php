@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Import Data Excel Shopee Liefmarket') }}</div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Menu Penjualan</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.choose-type') }}">Pilih Tipe Penjualan</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.online') }}">Penjualan Online</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.platform', ['platform' => 'shopee2']) }}">Platform Shopee Liefmarket</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Import Excel Shopee Liefmarket</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            @php
                                $error = session('error');
                            @endphp
                            @if(is_array($error))
                                <ul class="mb-0 ps-3">
                                    @foreach($error as $err)
                                        @if(is_array($err))
                                            @foreach($err as $suberr)
                                                <li>{{ $suberr }}</li>
                                            @endforeach
                                        @else
                                            <li>{{ $err }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                {{ $error }}
                            @endif
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Instruksi Import Excel Shopee</h5>
                                    <hr>
                                    <p>Mohon perhatikan hal-hal berikut saat mengupload file Excel:</p>
                                    <ol>
                                        <li>Format file harus .xlsx atau .xls</li>
                                        <li>File Excel harus memiliki kolom berikut:
                                            <ul>
                                                <li>NOMOR PESANAN</li>
                                                <li>NOMOR RESI</li>
                                                <li>HARI</li>
                                                <li>STATUS HARI <span class="text-info">(bisa lebih dari satu, dipisahkan dengan koma. Contoh: "Libur, Weekend")</span></li>
                                                <li>TANGGAL</li>
                                                <li>NAMA PRODUK</li>
                                                <li>VARIASI</li>
                                                <li>QTY</li>
                                                <li>HARGA SETELAH DISKON</li>
                                            </ul>
                                        </li>
                                        <li>Pastikan semua produk sudah terdaftar dan di-mapping di sistem</li>
                                        <li><strong>Format Status Hari:</strong>
                                            <ul>
                                                <li>Status tunggal: <code>Normal</code></li>
                                                <li>Status ganda: <code>Normal, Weekend</code> atau <code>Libur, Hari Raya</code></li>
                                                <li>Gunakan koma (,) untuk memisahkan beberapa status</li>
                                            </ul>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('sales.shopee2.preview-import') }}" enctype="multipart/form-data" id="shopee2ImportForm">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="custom-file">
                                    <input type="file" class="form-control @error('excel_file') is-invalid @enderror" id="excel_file" name="excel_file" accept=".xls,.xlsx,.csv" required>
                                    @error('excel_file')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6">
                                <a href="{{ route('sales.platform', ['platform' => 'shopee2']) }}" class="btn btn-secondary">
                                    {{ __('Kembali') }}
                                </a>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-primary" id="previewButton">
                                    {{ __('Preview Data') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection