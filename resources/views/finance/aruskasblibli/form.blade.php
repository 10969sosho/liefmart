@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">{{ isset($transaction) ? 'Edit' : 'Tambah' }} Arus Kas Blibli</h1>
            <p class="text-muted">{{ isset($transaction) ? 'Perbarui' : 'Buat' }} transaksi arus kas untuk platform Blibli</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ isset($transaction) ? route('finance.aruskasblibli.update', $transaction) : route('finance.aruskasblibli.store') }}" method="POST">
                        @csrf
                        @if(isset($transaction))
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label for="transaction_date" class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" name="transaction_date" value="{{ isset($transaction) ? $transaction->transaction_date->format('Y-m-d') : old('transaction_date') }}" required>
                            @error('transaction_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="transaction_type" class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                            <select class="form-select @error('transaction_type') is-invalid @enderror" id="transaction_type" name="transaction_type" required>
                                <option value="">-- Pilih Jenis Transaksi --</option>
                                <option value="income" {{ (isset($transaction) && $transaction->transaction_type == 'income') || old('transaction_type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                                <option value="expense" {{ (isset($transaction) && $transaction->transaction_type == 'expense') || old('transaction_type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                            </select>
                            @error('transaction_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ isset($transaction) ? $transaction->description : old('description') }}" required>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Nominal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ isset($transaction) ? $transaction->amount : old('amount') }}" min="0" step="0.01" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reference_number" class="form-label">Nomor Referensi</label>
                            <input type="text" class="form-control @error('reference_number') is-invalid @enderror" id="reference_number" name="reference_number" value="{{ isset($transaction) ? $transaction->reference_number : old('reference_number') }}">
                            @error('reference_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="pending" {{ (isset($transaction) && $transaction->status == 'pending') || old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ (isset($transaction) && $transaction->status == 'completed') || old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="failed" {{ (isset($transaction) && $transaction->status == 'failed') || old('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ isset($transaction) ? $transaction->notes : old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('finance.aruskasblibli.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> {{ isset($transaction) ? 'Perbarui' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-3">
                    <h5 class="card-title fw-bold mb-3">Panduan Pengisian</h5>
                    <ul class="small">
                        <li>Tanggal transaksi harus diisi dengan format tanggal yang valid.</li>
                        <li>Jenis transaksi: Pilih "Pemasukan" untuk uang masuk dan "Pengeluaran" untuk uang keluar.</li>
                        <li>Nominal harus diisi dengan nilai angka positif.</li>
                        <li>Status transaksi menunjukkan kondisi transaksi saat ini.</li>
                    </ul>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i> Data dengan tanda <span class="text-danger">*</span> wajib diisi.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 