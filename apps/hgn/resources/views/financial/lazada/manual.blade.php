@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Input Manual Data Keuangan Lazada</span>
                    <a href="{{ route('finance.lazada.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('finance.lazada.manual.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="order_id" class="form-label">Nomor Pesanan <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="order_id" name="order_id" required>
                                    <option value="">-- Pilih Nomor Pesanan --</option>
                                    @foreach($orders as $order)
                                        <option value="{{ $order->id }}" {{ (request('order_id') == $order->id || old('order_id') == $order->id) ? 'selected' : '' }}>{{ $order->order_number }} - {{ $order->customer_name }} - {{ $order->tanggal ? $order->tanggal->format('d/m/Y') : 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="tanggal_masuk_pembayaran" class="form-label">Tanggal Masuk Pembayaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_masuk_pembayaran" name="tanggal_masuk_pembayaran" value="{{ old('tanggal_masuk_pembayaran', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="hari_masuk_pembayaran" class="form-label">Hari Masuk Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select" id="hari_masuk_pembayaran" name="hari_masuk_pembayaran" required>
                                    <option value="Senin" {{ old('hari_masuk_pembayaran') == 'Senin' ? 'selected' : '' }}>Senin</option>
                                    <option value="Selasa" {{ old('hari_masuk_pembayaran') == 'Selasa' ? 'selected' : '' }}>Selasa</option>
                                    <option value="Rabu" {{ old('hari_masuk_pembayaran') == 'Rabu' ? 'selected' : '' }}>Rabu</option>
                                    <option value="Kamis" {{ old('hari_masuk_pembayaran') == 'Kamis' ? 'selected' : '' }}>Kamis</option>
                                    <option value="Jumat" {{ old('hari_masuk_pembayaran') == 'Jumat' ? 'selected' : '' }}>Jumat</option>
                                    <option value="Sabtu" {{ old('hari_masuk_pembayaran') == 'Sabtu' ? 'selected' : '' }}>Sabtu</option>
                                    <option value="Minggu" {{ old('hari_masuk_pembayaran') == 'Minggu' ? 'selected' : '' }}>Minggu</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nominal_harga" class="form-label">Nominal Harga (Gross) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_harga" name="nominal_harga" value="{{ old('nominal_harga', 0) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="saldo_masuk" class="form-label">Saldo Masuk (Net) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="saldo_masuk" name="saldo_masuk" value="{{ old('saldo_masuk', 0) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="adjustment" class="form-label">Adjustment / Penyesuaian</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="adjustment" name="adjustment" value="{{ old('adjustment', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon1" class="form-label">Biaya Payment Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon1" name="nominal_diskon1" value="{{ old('nominal_diskon1', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon2" class="form-label">Biaya Komisi</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon2" name="nominal_diskon2" value="{{ old('nominal_diskon2', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon3" class="form-label">Biaya Max Shipping</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon3" name="nominal_diskon3" value="{{ old('nominal_diskon3', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon4" class="form-label">Biaya Free Shipping Max</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon4" name="nominal_diskon4" value="{{ old('nominal_diskon4', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon5" class="form-label">Biaya Voucher</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon5" name="nominal_diskon5" value="{{ old('nominal_diskon5', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nominal_diskon6" class="form-label">Biaya Lainnya</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal_diskon6" name="nominal_diskon6" value="{{ old('nominal_diskon6', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-secondary me-2">Reset</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto fill day based on date
        const dateInput = document.getElementById('tanggal_masuk_pembayaran');
        const daySelect = document.getElementById('hari_masuk_pembayaran');
        
        dateInput.addEventListener('change', function() {
            const date = new Date(this.value);
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const dayName = days[date.getDay()];
            
            for(let i = 0; i < daySelect.options.length; i++) {
                if(daySelect.options[i].value === dayName) {
                    daySelect.selectedIndex = i;
                    break;
                }
            }
        });

        // Trigger change event to set initial day if date is already set
        if(dateInput.value) {
            const event = new Event('change');
            dateInput.dispatchEvent(event);
        }
    });
</script>
@endsection
