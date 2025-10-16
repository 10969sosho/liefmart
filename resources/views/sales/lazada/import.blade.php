@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Import Data Excel Lazada') }}</div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Menu Penjualan</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.choose-type') }}">Pilih Tipe Penjualan</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.online') }}">Penjualan Online</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('sales.platform', ['platform' => 'lazada']) }}">Platform Lazada</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Import Excel Lazada</li>
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
                                    <h5 class="card-title">Instruksi Import Excel Lazada</h5>
                                    <hr>
                                    <p>Mohon perhatikan hal-hal berikut saat mengupload file Excel:</p>
                                    <ol>
                                        <li>Format file harus .xlsx atau .xls</li>
                                        <li>File Excel harus memiliki kolom berikut:
                                            <ul>
                                                <li>TANGGAL</li>
                                                <li>HARI</li>
                                                <li>STATUS HARI</li>
                                                <li>NOMOR PESANAN</li>
                                                <li>QTY</li>
                                                <li>HARGA SETELAH DISKON</li>
                                                <li>PRODUK</li>
                                                <li>VARIAN</li>
                                            </ul>
                                        </li>
                                        <li>Pastikan semua produk sudah terdaftar dan di-mapping di sistem</li>
                                        <li><strong>Format Status Hari:</strong>
                                            <ul>
                                                <li>Status tunggal: <code>Hari Kerja</code></li>
                                                <li>Status ganda: <code>Hari Kerja, Weekend</code> atau <code>Hari Libur, Weekend</code></li>
                                                <li>Gunakan koma (,) untuk memisahkan beberapa status</li>
                                            </ul>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('sales.lazada.preview-import') }}" enctype="multipart/form-data" id="lazadaImportForm">
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
                                <a href="{{ route('sales.platform', ['platform' => 'lazada']) }}" class="btn btn-secondary">
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
