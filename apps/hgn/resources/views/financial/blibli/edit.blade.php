@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit Transaksi Keuangan Blibli</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('finance.blibli.update', $transaction->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="order_number" class="form-label">No. Order</label>
                            <input type="text" id="order_number" class="form-control" value="{{ $transaction->order_number }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="invoice_number" class="form-label">No. Invoice</label>
                            <input type="text" id="invoice_number" class="form-control" value="{{ $transaction->invoice_number }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="transaction_date" class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" id="transaction_date" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : '' }}" required>
                            @error('transaction_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <input type="text" id="payment_method" name="payment_method" class="form-control @error('payment_method') is-invalid @enderror" value="{{ $transaction->payment_method }}" required>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ $transaction->amount }}" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fee" class="form-label">Biaya</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="fee" name="fee" class="form-control @error('fee') is-invalid @enderror" value="{{ $transaction->fee }}">
                                    @error('fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tax" class="form-label">Pajak</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="tax" name="tax" class="form-control @error('tax') is-invalid @enderror" value="{{ $transaction->tax }}">
                                    @error('tax')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="total_amount" class="form-label">Total <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" id="total_amount" name="total_amount" class="form-control @error('total_amount') is-invalid @enderror" value="{{ $transaction->total_amount }}" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="pending" {{ $transaction->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $transaction->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="failed" {{ $transaction->status == 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ $transaction->notes }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('finance.blibli.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto calculate total amount
    document.addEventListener('DOMContentLoaded', function() {
        const amount = document.getElementById('amount');
        const fee = document.getElementById('fee');
        const tax = document.getElementById('tax');
        const total = document.getElementById('total_amount');
        
        const calculateTotal = function() {
            const amountValue = parseFloat(amount.value) || 0;
            const feeValue = parseFloat(fee.value) || 0;
            const taxValue = parseFloat(tax.value) || 0;
            
            total.value = (amountValue + feeValue + taxValue).toFixed(2);
        };
        
        amount.addEventListener('input', calculateTotal);
        fee.addEventListener('input', calculateTotal);
        tax.addEventListener('input', calculateTotal);
    });
</script>
@endpush 