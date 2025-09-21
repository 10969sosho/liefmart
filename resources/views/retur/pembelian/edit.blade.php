@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit Retur Pembelian</h4>
                    <div class="card-tools">
                        <a href="{{ route('retur-pembelian.show', $returPembelian->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    <form action="{{ route('retur-pembelian.update', $returPembelian->id) }}" method="POST" id="returForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="card card-outline card-info mb-4">
                            <div class="card-header">
                                <h5 class="card-title">Informasi Penerimaan (PO)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td width="150">Nomor PO</td>
                                                <td width="10">:</td>
                                                <td>{{ $returPembelian->penerimaan->nomor_po }}</td>
                                            </tr>
                                            <tr>
                                                <td>Tanggal Penerimaan</td>
                                                <td>:</td>
                                                <td>{{ $returPembelian->penerimaan->tanggal_penerimaan->format('d/m/Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td>Kategori</td>
                                                <td>:</td>
                                                <td>{{ $returPembelian->penerimaan->mainCategory->name ?? '-' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td width="150">Status</td>
                                                <td width="10">:</td>
                                                <td>
                                                    @if($returPembelian->penerimaan->status == 'Located')
                                                    <span class="badge badge-success">Located</span>
                                                    @elseif($returPembelian->penerimaan->status == 'Unlocated')
                                                    <span class="badge badge-warning">Unlocated</span>
                                                    @else
                                                    {{ $returPembelian->penerimaan->status }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Lokasi</td>
                                                <td>:</td>
                                                <td>{{ $returPembelian->penerimaan->lokasi->nama ?? '-' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="tanggal_retur">Tanggal Retur:</label>
                                    <input type="date" name="tanggal_retur" id="tanggal_retur" class="form-control @error('tanggal_retur') is-invalid @enderror" value="{{ old('tanggal_retur', $returPembelian->tanggal_retur->format('Y-m-d')) }}" required>
                                    @error('tanggal_retur')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="catatan">Catatan:</label>
                            <textarea name="catatan" id="catatan" class="form-control @error('catatan') is-invalid @enderror" rows="3">{{ old('catatan', $returPembelian->catatan) }}</textarea>
                            @error('catatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <h5>Detail Barang</h5>
                        
                        <div id="detail-container" class="mt-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="detail-table">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th>Qty Diterima</th>
                                            <th>Stok Tersedia</th>
                                            <th>Satuan</th>
                                            <th>Qty Retur</th>
                                            <th>Alasan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-container">
                                        @foreach($returPembelian->penerimaan->details as $index => $item)
                                        @php
                                            $detail = $returPembelian->details->where('penerimaan_detail_id', $item->id)->first();
                                            $availableStock = $item->available_stock ?? 0;
                                            if ($detail) {
                                                // Add current return qty to available stock because we're editing
                                                $availableStock += $detail->qty;
                                            }
                                            $stockClass = $availableStock > 0 ? 'text-success font-weight-bold' : 'text-danger font-weight-bold';
                                        @endphp
                                        <tr>
                                            <td>{{ $item->product->name }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td class="{{ $stockClass }}">{{ $availableStock }}</td>
                                            <td>{{ $item->satuan ? $item->satuan->name : '-' }}</td>
                                            <td>
                                                <input type="hidden" name="details[{{ $index }}][penerimaan_detail_id]" value="{{ $item->id }}">
                                                <input type="hidden" name="details[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                <input type="hidden" name="details[{{ $index }}][satuan_id]" value="{{ $item->satuan_id }}">
                                                <input type="number" name="details[{{ $index }}][qty]" class="form-control form-control-sm qty-input" min="0" max="{{ $availableStock }}" step="0.01" value="{{ $detail ? $detail->qty : 0 }}" {{ $availableStock <= 0 ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <input type="text" name="details[{{ $index }}][alasan]" class="form-control form-control-sm" placeholder="Alasan retur" value="{{ $detail ? $detail->alasan : '' }}" {{ $availableStock <= 0 ? 'disabled' : '' }}>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle mr-2"></i> Anda dapat memilih stok spesifik yang akan diretur berdasarkan penerimaan detail dan tanggal ED.
                            </div>
                        </div>

                        <div class="form-group mt-4 text-right">
                            <button type="submit" class="btn btn-primary" id="submit-btn">
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

@push('styles')
<style>
    .text-success {
        color: #28a745;
    }
    .text-danger {
        color: #dc3545;
    }
    .font-weight-bold {
        font-weight: bold;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Form submission validation
        $('#returForm').submit(function(e) {
            // Check if any items have a quantity > 0
            let anyItemsSelected = false;
            $('.qty-input').each(function() {
                if (parseFloat($(this).val()) > 0) {
                    anyItemsSelected = true;
                    
                    // Check if alasan is provided for items with qty > 0
                    let alasan = $(this).closest('tr').find('input[type="text"]').val();
                    
                    if (!alasan) {
                        e.preventDefault();
                        alert('Alasan retur harus diisi untuk semua item yang diretur!');
                        anyItemsSelected = false;
                        return false; // Break the loop
                    }
                }
            });
            
            if (!anyItemsSelected) {
                e.preventDefault();
                alert('Anda harus memasukkan jumlah retur minimal 1 barang');
                return false;
            }
            
            if (!confirm('Apakah Anda yakin ingin mengubah retur pembelian ini?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
</script>
@endpush 