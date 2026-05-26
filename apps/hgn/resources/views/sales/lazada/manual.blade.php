@extends('layouts.app')

@section('title', 'Lazada - Input Manual')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Input Manual Lazada
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('sales.lazada.manual.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal</label>
                                    <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hari">Hari</label>
                                    <select class="form-control" id="hari" name="hari" required>
                                        <option value="">Pilih Hari</option>
                                        <option value="Senin">Senin</option>
                                        <option value="Selasa">Selasa</option>
                                        <option value="Rabu">Rabu</option>
                                        <option value="Kamis">Kamis</option>
                                        <option value="Jumat">Jumat</option>
                                        <option value="Sabtu">Sabtu</option>
                                        <option value="Minggu">Minggu</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status_hari">Status Hari</label>
                                    <select class="form-control" id="status_hari" name="status_hari" required>
                                        <option value="">Pilih Status</option>
                                        <option value="Hari Kerja">Hari Kerja</option>
                                        <option value="Hari Libur">Hari Libur</option>
                                        <option value="Weekend">Weekend</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="no_order">Nomor Pesanan</label>
                                    <input type="text" class="form-control" id="no_order" name="no_order" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="qty">QTY</label>
                                    <input type="number" class="form-control" id="qty" name="qty" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="harga_setelah_diskon">Harga Setelah Diskon</label>
                                    <input type="number" class="form-control" id="harga_setelah_diskon" name="harga_setelah_diskon" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="produk">Produk</label>
                                    <input type="text" class="form-control" id="produk" name="produk" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="varian">Varian</label>
                                    <input type="text" class="form-control" id="varian" name="varian">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('sales.lazada.platform') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
