@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Edit Transaksi Keuangan Tokopedia</h5>
                    <a href="{{ route('finance.tokopedia.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('finance.tokopedia.update', $transaction->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="no_order">No Order</label>
                                    <input type="text" class="form-control" id="no_order" value="{{ $transaction->no_order }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="no_invoice">No Invoice</label>
                                    <input type="text" class="form-control" id="no_invoice" value="{{ $transaction->no_invoice }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_order">Tanggal Order</label>
                                    <input type="date" class="form-control" id="tanggal_order" name="tanggal_order" value="{{ $transaction->tanggal_order }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hari_order">Hari Order</label>
                                    <input type="text" class="form-control" id="hari_order" name="hari_order" value="{{ $transaction->hari_order }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nominal_harga">Nominal Harga</label>
                                    <input type="number" class="form-control" id="nominal_harga" name="nominal_harga" value="{{ $transaction->nominal_harga }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nominal_diskon1">Diskon 1</label>
                                    <input type="number" class="form-control" id="nominal_diskon1" name="nominal_diskon1" value="{{ $transaction->nominal_diskon1 }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nominal_diskon2">Diskon 2</label>
                                    <input type="number" class="form-control" id="nominal_diskon2" name="nominal_diskon2" value="{{ $transaction->nominal_diskon2 }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nominal_diskon3">Diskon 3</label>
                                    <input type="number" class="form-control" id="nominal_diskon3" name="nominal_diskon3" value="{{ $transaction->nominal_diskon3 }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nominal_diskon4">Diskon 4</label>
                                    <input type="number" class="form-control" id="nominal_diskon4" name="nominal_diskon4" value="{{ $transaction->nominal_diskon4 }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nominal_diskon5">Diskon 5</label>
                                    <input type="number" class="form-control" id="nominal_diskon5" name="nominal_diskon5" value="{{ $transaction->nominal_diskon5 }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nominal_fix">Nominal Fix</label>
                                    <input type="number" class="form-control" id="nominal_fix" name="nominal_fix" value="{{ $transaction->nominal_fix }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="saldo_masuk">Saldo Masuk</label>
                                    <input type="number" class="form-control" id="saldo_masuk" name="saldo_masuk" value="{{ $transaction->saldo_masuk }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adjustment">Adjustment</label>
                                    <input type="number" class="form-control" id="adjustment" name="adjustment" value="{{ $transaction->adjustment }}">
                                    <div class="alert alert-warning mt-2 p-2 small">
                                        <p class="mb-1"><i class="fas fa-lightbulb me-1"></i> <strong>Tentang Adjustment:</strong></p>
                                        <p class="mb-0">Nilai positif akan menambah nominal fix, nilai negatif akan mengurangi nominal fix. Adjustment memengaruhi outstanding/sisa pembayaran.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="adjustment_description">Deskripsi Adjustment</label>
                                    <textarea class="form-control" id="adjustment_description" name="adjustment_description" rows="3" placeholder="Contoh: Biaya tambahan pengiriman (+), Diskon loyalitas pelanggan (-), Biaya penanganan khusus (+), Potongan harga karena produk cacat (-)">{{ $transaction->adjustment_description ?? '' }}</textarea>
                                    <div class="text-muted small mt-1">
                                        Jelaskan alasan penyesuaian, contoh: "Penambahan biaya kemasan khusus" atau "Potongan karena keterlambatan pengiriman"
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_masuk_pembayaran">Tanggal Masuk Pembayaran</label>
                                    <input type="date" class="form-control" id="tanggal_masuk_pembayaran" name="tanggal_masuk_pembayaran" value="{{ $transaction->tanggal_masuk_pembayaran }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hari_masuk_pembayaran">Hari Masuk Pembayaran</label>
                                    <input type="text" class="form-control" id="hari_masuk_pembayaran" name="hari_masuk_pembayaran" value="{{ $transaction->hari_masuk_pembayaran }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="outstanding">Outstanding</label>
                                    <input type="number" class="form-control" id="outstanding" name="outstanding" value="{{ $transaction->outstanding }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Persentase Biaya</label>
                                    <div class="card mt-2">
                                        <div class="card-body p-3">
                                            <div class="row mb-2">
                                                <div class="col-6">Diskon 1:</div>
                                                <div class="col-6 text-end text-danger" id="persentase_diskon1">{{ number_format(abs($transaction->persentase_diskon1), 2) }}%</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6">Diskon 2:</div>
                                                <div class="col-6 text-end text-danger" id="persentase_diskon2">{{ number_format(abs($transaction->persentase_diskon2), 2) }}%</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6">Diskon 3:</div>
                                                <div class="col-6 text-end text-danger" id="persentase_diskon3">{{ number_format(abs($transaction->persentase_diskon3), 2) }}%</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6">Diskon 4:</div>
                                                <div class="col-6 text-end text-danger" id="persentase_diskon4">{{ number_format(abs($transaction->persentase_diskon4), 2) }}%</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6">Diskon 5:</div>
                                                <div class="col-6 text-end text-danger" id="persentase_diskon5">{{ number_format(abs($transaction->persentase_diskon5), 2) }}%</div>
                                            </div>
                                            <div class="row fw-bold border-top pt-2 mt-2">
                                                <div class="col-6">Total:</div>
                                                <div class="col-6 text-end text-danger" id="total_persentase">{{ number_format(abs($transaction->total_persentase), 2) }}%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
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
@endsection

@push('scripts')
<script>
    // Calculate nominal_fix when discounts change
    function calculateNominalFix() {
        const nominalHarga = parseFloat(document.getElementById('nominal_harga').value) || 0;
        const diskon1 = parseFloat(document.getElementById('nominal_diskon1').value) || 0;
        const diskon2 = parseFloat(document.getElementById('nominal_diskon2').value) || 0;
        const diskon3 = parseFloat(document.getElementById('nominal_diskon3').value) || 0;
        const diskon4 = parseFloat(document.getElementById('nominal_diskon4').value) || 0;
        const diskon5 = parseFloat(document.getElementById('nominal_diskon5').value) || 0;

        const nominalFix = nominalHarga - diskon1 - diskon2 - diskon3 - diskon4 - diskon5;
        document.getElementById('nominal_fix').value = nominalFix;

        // Calculate and update percentages
        if (nominalHarga > 0) {
            const persentaseDiskon1 = Math.abs((diskon1 / nominalHarga) * 100).toFixed(2);
            const persentaseDiskon2 = Math.abs((diskon2 / nominalHarga) * 100).toFixed(2);
            const persentaseDiskon3 = Math.abs((diskon3 / nominalHarga) * 100).toFixed(2);
            const persentaseDiskon4 = Math.abs((diskon4 / nominalHarga) * 100).toFixed(2);
            const persentaseDiskon5 = Math.abs((diskon5 / nominalHarga) * 100).toFixed(2);
            
            // Update percentages in the UI if elements exist
            if (document.getElementById('persentase_diskon1')) {
                document.getElementById('persentase_diskon1').textContent = persentaseDiskon1 + '%';
                document.getElementById('persentase_diskon2').textContent = persentaseDiskon2 + '%';
                document.getElementById('persentase_diskon3').textContent = persentaseDiskon3 + '%';
                document.getElementById('persentase_diskon4').textContent = persentaseDiskon4 + '%';
                document.getElementById('persentase_diskon5').textContent = persentaseDiskon5 + '%';
                
                const totalPersentase = (parseFloat(persentaseDiskon1) + parseFloat(persentaseDiskon2) + 
                                        parseFloat(persentaseDiskon3) + parseFloat(persentaseDiskon4) + 
                                        parseFloat(persentaseDiskon5)).toFixed(2);
                document.getElementById('total_persentase').textContent = totalPersentase + '%';
            }
        }

        calculateOutstanding();
    }

    // Calculate outstanding when saldo_masuk or adjustment changes
    function calculateOutstanding() {
        const nominalFix = parseFloat(document.getElementById('nominal_fix').value) || 0;
        const saldoMasuk = parseFloat(document.getElementById('saldo_masuk').value) || 0;
        const adjustment = parseFloat(document.getElementById('adjustment').value) || 0;

        const outstanding = saldoMasuk - nominalFix + adjustment;
        document.getElementById('outstanding').value = outstanding;
    }

    // Add event listeners for calculation
    document.getElementById('nominal_harga').addEventListener('input', calculateNominalFix);
    document.getElementById('nominal_diskon1').addEventListener('input', calculateNominalFix);
    document.getElementById('nominal_diskon2').addEventListener('input', calculateNominalFix);
    document.getElementById('nominal_diskon3').addEventListener('input', calculateNominalFix);
    document.getElementById('nominal_diskon4').addEventListener('input', calculateNominalFix);
    document.getElementById('nominal_diskon5').addEventListener('input', calculateNominalFix);
    document.getElementById('saldo_masuk').addEventListener('input', calculateOutstanding);
    document.getElementById('adjustment').addEventListener('input', calculateOutstanding);
</script>
@endpush 