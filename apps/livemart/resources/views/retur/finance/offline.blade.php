@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="ds-card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Finance Retur Penjualan Offline</h4>
                    <a href="{{ route('retur-offline.show', $retur->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <tr>
                            <th width="220">Kode Retur</th>
                            <td>{{ $retur->kode_retur }}</td>
                        </tr>
                        <tr>
                            <th>Nomor Surat Jalan</th>
                            <td>{{ $retur->offlineSale->surat_jalan_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Total Awal</th>
                            <td>Rp {{ number_format($originalTotal, 0, ',', '.') }}</td>
                        </tr>
                    </table>

                    <form action="{{ route('retur-offline.finance.process', $retur->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="refund_amount">Nominal Refund</label>
                            <input type="number" min="0" step="1" name="refund_amount" id="refund_amount" class="form-control @error('refund_amount') is-invalid @enderror" value="{{ old('refund_amount', $originalTotal) }}" required>
                            @error('refund_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="additional_deduction">Potongan Tambahan</label>
                            <input type="number" min="0" step="1" name="additional_deduction" id="additional_deduction" class="form-control @error('additional_deduction') is-invalid @enderror" value="{{ old('additional_deduction', 0) }}">
                            @error('additional_deduction')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="notes">Catatan</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Proses Finance
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
