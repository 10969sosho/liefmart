@extends('layouts.app')

@section('title', 'Import Financial Data Lazada')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Import Data Keuangan Lazada') }}</div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('finance.lazada.index') }}">Transaksi Keuangan Lazada</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Import Excel</li>
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
                                    <h5 class="card-title">Instruksi Import Excel Keuangan Lazada</h5>
                                    <hr>
                                    <p>Mohon perhatikan hal-hal berikut saat mengupload file Excel:</p>
                                    <ol>
                                        <li>Format file harus .xlsx atau .xls</li>
                                        <li>File Excel harus memiliki kolom berikut:
                                            <ul>
                                                <li><strong>NOMOR PESANAN</strong> - Nomor pesanan yang terkait (wajib)</li>
                                                <li><strong>TANGGAL MASUK PEMBAYARAN</strong> - Tanggal pembayaran masuk (wajib)</li>
                                                <li><strong>HARI MASUK PEMBAYARAN</strong> - Hari pembayaran masuk (wajib)</li>
                                                <li><strong>JUMLAH MASUK PEMBAYARAN</strong> - Jumlah uang yang masuk (wajib)</li>
                                                <li><strong>BIAYA PROSES FIX</strong> - Biaya proses tetap (opsional)</li>
                                                <li><strong>GRATIS ONGKIR</strong> - Biaya gratis ongkir (opsional)</li>
                                                <li><strong>BIAYA ADMIN</strong> - Biaya administrasi (opsional)</li>
                                                <li><strong>BIAYA TRANSAKSI</strong> - Biaya transaksi (opsional)</li>
                                                <li><strong>DISKON 5 - DISKON 12</strong> - Diskon tambahan (opsional)</li>
                                            </ul>
                                        </li>
                                        <li>Pastikan nomor pesanan sudah ada di sistem</li>
                                        <li><strong>Format Tanggal:</strong> DD Mon YYYY (contoh: 11 Oct 2025)</li>
                                        <li><strong>Format Nominal:</strong> Gunakan angka tanpa format mata uang</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('finance.lazada.preview') }}" enctype="multipart/form-data" id="lazadaFinancialImportForm">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="custom-file">
                                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".xls,.xlsx,.csv" required>
                                    @error('file')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6">
                                <a href="{{ route('finance.lazada.index') }}" class="btn btn-secondary">
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
