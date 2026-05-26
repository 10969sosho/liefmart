@extends('layouts.app')

@section('title', 'Edit Rekening Bank')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="ds-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-university me-2"></i> Edit Rekening Bank
                        </h5>
                        <a href="{{ route('bank-accounts.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('bank-accounts.update', $bankAccount) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_name">Nama Bank <span class="text-danger">*</span></label>
                                    <select class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" required>
                                        <option value="">-- Pilih Bank --</option>
                                        <option value="BCA" {{ old('bank_name', $bankAccount->bank_name) == 'BCA' ? 'selected' : '' }}>BCA (Bank Central Asia)</option>
                                        <option value="BNI" {{ old('bank_name', $bankAccount->bank_name) == 'BNI' ? 'selected' : '' }}>BNI (Bank Negara Indonesia)</option>
                                        <option value="BRI" {{ old('bank_name', $bankAccount->bank_name) == 'BRI' ? 'selected' : '' }}>BRI (Bank Rakyat Indonesia)</option>
                                        <option value="Mandiri" {{ old('bank_name', $bankAccount->bank_name) == 'Mandiri' ? 'selected' : '' }}>Bank Mandiri</option>
                                        <option value="CIMB Niaga" {{ old('bank_name', $bankAccount->bank_name) == 'CIMB Niaga' ? 'selected' : '' }}>CIMB Niaga</option>
                                        <option value="Danamon" {{ old('bank_name', $bankAccount->bank_name) == 'Danamon' ? 'selected' : '' }}>Bank Danamon</option>
                                        <option value="Permata" {{ old('bank_name', $bankAccount->bank_name) == 'Permata' ? 'selected' : '' }}>Bank Permata</option>
                                        <option value="BTN" {{ old('bank_name', $bankAccount->bank_name) == 'BTN' ? 'selected' : '' }}>BTN (Bank Tabungan Negara)</option>
                                        <option value="BSI" {{ old('bank_name', $bankAccount->bank_name) == 'BSI' ? 'selected' : '' }}>BSI (Bank Syariah Indonesia)</option>
                                        <option value="other" {{ !in_array(old('bank_name', $bankAccount->bank_name), ['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB Niaga', 'Danamon', 'Permata', 'BTN', 'BSI']) ? 'selected' : '' }}>Bank Lainnya...</option>
                                    </select>
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group d-none" id="other_bank_group">
                                    <label for="other_bank">Nama Bank Lainnya <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="other_bank" placeholder="Masukkan nama bank" value="{{ !in_array($bankAccount->bank_name, ['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB Niaga', 'Danamon', 'Permata', 'BTN', 'BSI']) ? $bankAccount->bank_name : '' }}">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_number">Nomor Rekening <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $bankAccount->account_number) }}" placeholder="Contoh: 1234567890" required>
                                    <small class="form-text text-muted">Masukkan nomor rekening tanpa spasi atau karakter khusus</small>
                                    @error('account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="account_name">Atas Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror" id="account_name" name="account_name" value="{{ old('account_name', $bankAccount->account_name) }}" placeholder="Nama pemilik rekening" required>
                            <small class="form-text text-muted">Masukkan nama sesuai yang tertera pada buku tabungan</small>
                            @error('account_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Contoh: Rekening operasional untuk penerimaan pembayaran customer">{{ old('description', $bankAccount->description) }}</textarea>
                            <small class="form-text text-muted">Informasi tambahan tentang rekening bank ini (opsional)</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" 
                                {{ old('is_active', $bankAccount->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Aktifkan sebagai rekening utama</label>
                            </div>
                            <div class="alert alert-warning mt-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Catatan:</strong> Jika diaktifkan, rekening ini akan muncul di invoice dan rekening aktif lainnya akan dinonaktifkan secara otomatis.
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group d-flex justify-content-between">
                            <a href="{{ route('bank-accounts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle bank name dropdown
        $('#bank_name').change(function() {
            if ($(this).val() === 'other') {
                $('#other_bank_group').removeClass('d-none');
                $('#other_bank').attr('required', true);
            } else {
                $('#other_bank_group').addClass('d-none');
                $('#other_bank').attr('required', false);
            }
        });
        
        // Handle form submission for "other" bank
        $('form').submit(function(e) {
            if ($('#bank_name').val() === 'other') {
                $('#bank_name').val($('#other_bank').val());
            }
        });
        
        // Trigger change event on page load (for when form reloads with validation errors)
        $('#bank_name').trigger('change');
    });
</script>
@endpush
@endsection 