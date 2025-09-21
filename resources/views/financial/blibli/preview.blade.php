@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="card-title mb-0">Preview Data Pembayaran Blibli</h5>
                    <a href="{{ route('finance.blibli.import') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Import
                    </a>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-1"></i> 
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-1"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-university mr-2"></i> Informasi Rekening Aktif</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $bankInfo = \App\Models\BlibliFinancialTransaction::getBankAccountInfo();
                            @endphp
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Bank:</strong> {{ $bankInfo['bank_name'] }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Nomor Rekening:</strong> {{ $bankInfo['account_number'] }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Atas Nama:</strong> {{ $bankInfo['account_name'] }}</p>
                                </div>
                            </div>
                            @if(!$bankInfo['has_active'])
                                <div class="alert alert-warning mb-0 mt-2">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> Belum ada rekening bank yang diatur sebagai aktif. 
                                    <a href="{{ route('bank-accounts.index') }}" class="alert-link">Atur rekening aktif sekarang</a>.
                                </div>
                            @else
                                <p class="small text-muted mb-0">Informasi rekening ini akan ditampilkan di semua invoice yang dicetak.</p>
                            @endif
                        </div>
                    </div>

                    @if(count($data) > 0)
                        <div class="alert alert-info">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Statistik Data:</h5>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="bg-white p-3 rounded shadow-sm text-center">
                                        <h3 class="mb-0">{{ isset($transactionSummary['total_rows_scanned']) ? $transactionSummary['total_rows_scanned'] : $totalRows }}</h3>
                                        <p class="mb-0 text-muted small">Total baris dipindai</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-white p-3 rounded shadow-sm text-center">
                                        <h3 class="mb-0">{{ isset($transactionSummary['valid_rows']) ? $transactionSummary['valid_rows'] : 'N/A' }}</h3>
                                        <p class="mb-0 text-muted small">Baris valid</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-success bg-opacity-25 p-3 rounded shadow-sm text-center">
                                        <h3 class="mb-0">{{ $validRows }}</h3>
                                        <p class="mb-0 text-muted small">Transaksi valid</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-danger bg-opacity-25 p-3 rounded shadow-sm text-center">
                                        <h3 class="mb-0">{{ $invalidRows }}</h3>
                                        <p class="mb-0 text-muted small">Transaksi invalid</p>
                                    </div>
                                </div>
                            </div>
                            
                            @if(isset($transactionSummary))
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="card border-0">
                                            <div class="card-body p-0">
                                                <h6 class="card-title mb-3">Ringkasan Detail:</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Total Baris</th>
                                                                <th>Baris Valid</th>
                                                                <th>Baris Invalid</th>
                                                                <th>Baris Diabaikan</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>{{ $transactionSummary['total_rows_scanned'] }}</td>
                                                                <td>{{ $transactionSummary['valid_rows'] }}</td>
                                                                <td>{{ $transactionSummary['invalid_rows'] }}</td>
                                                                <td>{{ $transactionSummary['ignored_rows'] }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @php
                            $invalidCount = count(array_filter($data, function($row) { return !$row['_valid']; }));
                            $duplicateCount = count(array_filter($previewData, function($row) { return isset($row['is_duplicate']) && $row['is_duplicate']; }));
                        @endphp
                        
                        @if($invalidCount > 0 || $duplicateCount > 0)
                            <div class="alert alert-warning">
                                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Perhatian:</h5>
                                @if($invalidCount > 0)
                                    <p>Beberapa transaksi tidak dapat diimpor. Transaksi dengan status "Error" tidak akan dimasukkan ke database.</p>
                                @endif
                                @if($duplicateCount > 0)
                                    <p><strong>{{ $duplicateCount }} transaksi terdeteksi duplikat</strong> (sudah ada di database). Transaksi duplikat ditandai dengan badge kuning dan akan dilewati selama proses import.</p>
                                @endif
                            </div>
                        @endif
                    @endif

                    @if(!empty($issues))
                        <div class="alert alert-danger">
                            <h5 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Masalah yang ditemukan:</h5>
                            <ul>
                                @foreach($issues as $rowNumber => $rowIssues)
                                    <li>Baris {{ $rowNumber }}:
                                        <ul>
                                            @foreach($rowIssues as $issue)
                                                <li>{{ $issue }}</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('finance.blibli.import-process') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal Order</th>
                                        <th>Hari Order</th>
                                        <th>No. Order</th>
                                        <th>No. Invoice</th>
                                        <th>Tax ID</th>
                                        <th>Status</th>
                                        <th>QTY</th>
                                        <th>Nominal Harga</th>
                                        <th>Biaya Admin</th>
                                        <th>Biaya Layanan</th>
                                        <th>Biaya 3</th>
                                        <th>Biaya 4</th>
                                        <th>Biaya 5</th>
                                        <th>Biaya 6</th>
                                        <th>Biaya 7</th>
                                        <th>Biaya 8</th>
                                        <th>Biaya 9</th>
                                        <th>Biaya 10</th>
                                        <th>Biaya 11</th>
                                        <th>Biaya 12</th>
                                        <th>Adjustment</th>
                                        <th>Nominal Fix</th>
                                        <th>Saldo Masuk</th>
                                        <th>Tanggal Pembayaran</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(count($previewData) > 0)
                                        @foreach($previewData as $orderIndex => $order)
                                            @foreach($order['invoices'] as $invIndex => $invoice)
                                                <tr @if($invIndex === 0) class="table-primary" @endif>
                                                    @if($invIndex === 0)
                                                        <td rowspan="{{ count($order['invoices']) }}">
                                                            {{ \Carbon\Carbon::parse($order['tanggal_order'])->format('d-m-Y') }}
                                                        </td>
                                                        <td rowspan="{{ count($order['invoices']) }}">
                                                            {{ $order['hari_order'] }}
                                                        </td>
                                                        <td rowspan="{{ count($order['invoices']) }}">
                                                            {{ $order['no_order'] }}
                                                            @if(count($order['invoices']) > 1)
                                                                <div class="small text-muted">({{ count($order['invoices']) }} invoice)</div>
                                                            @endif
                                                            @if(isset($order['is_duplicate']) && $order['is_duplicate'])
                                                                <div class="mt-1">
                                                                    <span class="badge bg-warning text-dark">
                                                                        <i class="fas fa-exclamation-triangle me-1"></i> Duplicate
                                                                    </span>
                                                                    <div class="small text-danger">{{ $order['warning'] }}</div>
                                                                </div>
                                                            @endif
                                                        </td>
                                                    @else
                                                        <!-- Empty cells to maintain table structure -->
                                                        <td style="display: none;"></td>
                                                        <td style="display: none;"></td>
                                                        <td style="display: none;"></td>
                                                    @endif
                                                    <td>
                                                        {{ $invoice['no_invoice'] }}
                                                        @php
                                                            $taxId = null;
                                                            if (strpos($invoice['no_invoice'], 'HPNSDA-OLK/01') !== false) {
                                                                $taxId = 1; // PKP - Coffee
                                                            } elseif (strpos($invoice['no_invoice'], 'HPNSDA-OLK/02') !== false) {
                                                                $taxId = 2; // Non PKP - Coffee
                                                            } elseif (strpos($invoice['no_invoice'], 'HGNSDA-OL/01') !== false) {
                                                                $taxId = 3; // PKP - Skincare
                                                            } elseif (strpos($invoice['no_invoice'], 'HGNSDA-OL/02') !== false) {
                                                                $taxId = 4; // Non PKP - Skincare
                                                            } else {
                                                                // Extract last two digits if possible
                                                                if (preg_match('/\/(\d{2})/', $invoice['no_invoice'], $matches)) {
                                                                    $taxId = (int)$matches[1];
                                                                }
                                                            }
                                                            $isPKP = in_array($taxId, [1, 3, 5, 7]);
                                                        @endphp
                                                    </td>
                                                    <td>
                                                        @if($taxId)
                                                            <span class="badge {{ $isPKP ? 'bg-primary' : 'bg-secondary' }}">
                                                                {{ $isPKP ? 'PKP' : 'Non-PKP' }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ number_format($invoice['qty'] ?? 0, 0, ',', '.') }}</td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_harga'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_harga'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon1'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon1'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon2'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon2'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon3'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon3'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon4'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon4'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon5'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon5'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon6'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon6'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon7'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon7'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon8'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon8'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon9'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon9'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon10'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon10'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon11'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon11'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_diskon12'] ?? 0, 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_diskon12'] ?? 0, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['adjustment'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['adjustment'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($taxId, [5, 6, 7, 8]))
                                                            {{ number_format($invoice['nominal_fix'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['nominal_fix'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if(in_array($invoice['tax_id'], [5, 6, 7, 8]))
                                                            {{ number_format($invoice['saldo_masuk'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['saldo_masuk'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    @if($invIndex === 0)
                                                        <td rowspan="{{ count($order['invoices']) }}">
                                                            {{ \Carbon\Carbon::parse($order['tanggal_masuk_pembayaran'])->format('d-m-Y') }}
                                                            <div class="small text-muted">{{ $order['hari_masuk_pembayaran'] }}</div>
                                                        </td>
                                                    @else
                                                        <td style="display: none;"></td>
                                                    @endif
                                                    <td class="text-end {{ $invoice['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">
                                                        @if(in_array($invoice['tax_id'], [5, 6, 7, 8]))
                                                            {{ number_format($invoice['outstanding'], 3, ',', '.') }}
                                                        @else
                                                            {{ number_format($invoice['outstanding'], 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Valid</span>
                                                        <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][_valid]" value="true">
                                                        <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][no_order]" value="{{ $order['no_order'] }}">
                                                        <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][tax_id]" value="{{ $invoice['tax_id'] }}">
                                                        <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][TANGGAL MASUK PEMBAYARAN]" value="{{ $order['tanggal_masuk_pembayaran'] }}">
                                                        <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][HARI MASUK PEMBAYARAN]" value="{{ $order['hari_masuk_pembayaran'] }}">
                                                        <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][JUMLAH MASUK PEMBAYARAN]" value="{{ $order['saldo_masuk'] }}">
                                                        <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][proportion]" value="{{ count($order['invoices']) > 0 ? (1 / count($order['invoices'])) : 1 }}">
                                                        @if(isset($data[$orderIndex]['BIAYA ADMIN']))
                                                            <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][BIAYA ADMIN]" value="{{ $data[$orderIndex]['BIAYA ADMIN'] }}">
                                                        @endif
                                                        @if(isset($data[$orderIndex]['BIAYA LAYANAN']))
                                                            <input type="hidden" name="data[{{ $orderIndex }}_{{ $invIndex }}][BIAYA LAYANAN]" value="{{ $data[$orderIndex]['BIAYA LAYANAN'] }}">
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="26" class="text-center">Tidak ada data yang dapat diimpor</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 d-flex justify-content-between">
                            <a href="{{ route('finance.blibli.import') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                            @if(count($data) > 0 && $validRows > 0)
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Proses {{ $validRows }} Transaksi
                                </button>
                            @endif
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
    /* Fix for rowspans and better visualization */
    .table-responsive {
        overflow-x: auto;
    }
    
    .table th {
        vertical-align: middle;
        background-color: #f8f9fa;
    }
    
    .text-end {
        text-align: right;
    }
    
    /* Highlight primary rows */
    .table-primary {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }
</style>
@endpush 