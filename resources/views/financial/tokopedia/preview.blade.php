@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="card-title mb-0">Preview Data Pembayaran Tokopedia</h5>
                    <a href="{{ route('finance.tokopedia.import') }}" class="btn btn-light btn-sm">
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
                                $bankInfo = \App\Models\TokopediaFinancialTransaction::getBankAccountInfo();
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
                                        <h3 class="mb-0">{{ isset($transactionSummary['transaction_rows']) ? $transactionSummary['transaction_rows'] : 'N/A' }}</h3>
                                        <p class="mb-0 text-muted small">Baris transaksi ditemukan</p>
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
                                                                <th>Baris Transaksi</th>
                                                                <th>Baris Diskon</th>
                                                                <th>Baris Diabaikan</th>
                                                                <th>Order Ditemukan</th>
                                                                <th>Transaksi Diproses</th>
                                                                <th>Transaksi Valid</th>
                                                                <th>Transaksi Invalid</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>{{ $transactionSummary['total_rows_scanned'] }}</td>
                                                                <td>{{ $transactionSummary['transaction_rows'] }}</td>
                                                                <td>{{ $transactionSummary['discount_rows'] }}</td>
                                                                <td>{{ $transactionSummary['ignored_rows'] }}</td>
                                                                <td>{{ $transactionSummary['orders_found'] }}</td>
                                                                <td>{{ $transactionSummary['processed_transactions'] }}</td>
                                                                <td>{{ $transactionSummary['valid_transactions'] }}</td>
                                                                <td>{{ $transactionSummary['invalid_transactions'] }}</td>
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

                        @if(count(array_filter($data, function($row) { return !$row['_valid']; })) > 0)
                            <div class="alert alert-warning">
                                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Perhatian:</h5>
                                <p>Beberapa transaksi tidak dapat diimpor. Transaksi dengan status "Error" tidak akan dimasukkan ke database.</p>
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

                    <form action="{{ route('finance.tokopedia.import-process') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        @foreach($previewHeaders as $header)
                                            <th>{{ $headerLabels[$header] ?? $header }}</th>
                                        @endforeach
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(count($data) > 0)
                                        @foreach($data as $index => $row)
                                            <tr class="{{ $row['_valid'] ? 'table-success' : 'table-danger' }}">
                                                @foreach($previewHeaders as $header)
                                                    <td>
                                                        @if(isset($row[$header]))
                                                            @if(in_array($header, ['nominal_harga', 'nominal_diskon1', 'nominal_diskon2', 'nominal_diskon3', 'nominal_diskon4', 'nominal_diskon5', 'nominal_diskon6', 'nominal_diskon7', 'nominal_diskon8', 'nominal_diskon9', 'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12', 'nominal_fix', 'saldo_masuk', 'outstanding']))
                                                                {{ number_format($row[$header] ?? 0, 0, ',', '.') }}
                                                            @else
                                                                {{ $row[$header] ?? '-' }}
                                                            @endif
                                                            <input type="hidden" name="data[{{ $index }}][{{ $header }}]" value="{{ $row[$header] ?? 0 }}">
                                                        @else
                                                            @if(in_array($header, ['nominal_diskon5', 'nominal_diskon6', 'nominal_diskon7', 'nominal_diskon8', 'nominal_diskon9', 'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12']))
                                                                0
                                                                <input type="hidden" name="data[{{ $index }}][{{ $header }}]" value="0">
                                                            @else
                                                                -
                                                                <input type="hidden" name="data[{{ $index }}][{{ $header }}]" value="">
                                                            @endif
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td>
                                                    <input type="hidden" name="data[{{ $index }}][_valid]" value="{{ $row['_valid'] ? 'true' : 'false' }}">
                                                    @if($row['_valid'])
                                                        <span class="badge bg-success">Valid</span>
                                                    @else
                                                        <span class="badge bg-danger">Error</span>
                                                        @if(isset($issues[$row['_row']]))
                                                            <ul class="small mb-0">
                                                                @foreach($issues[$row['_row']] as $issue)
                                                                    <li>{{ $issue }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="{{ count($previewHeaders) + 1 }}" class="text-center">
                                                <div class="my-5">
                                                    <i class="fas fa-file-excel fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">Tidak ada data yang dapat diimpor</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="{{ route('finance.tokopedia.import') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary" {{ count(array_filter($data, function($row) { return $row['_valid']; })) == 0 ? 'disabled' : '' }}>
                                <i class="fas fa-file-import me-1"></i> Import {{ count(array_filter($data, function($row) { return $row['_valid']; })) }} Data Valid
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
    .table th, .table td {
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
    }
    
    .table th:first-child, .table td:first-child {
        text-align: left;
    }
    
    .table th:last-child, .table td:last-child {
        text-align: center;
    }
    
    .table-sm td {
        padding: 0.5rem;
        vertical-align: middle;
        font-size: 0.875rem;
    }
    
    .table-sm th {
        padding: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .badge {
        font-weight: 500;
    }
    
    .table-responsive {
        max-height: 600px;
        overflow-y: auto;
    }
    
    .table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table-success {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }
    
    .table-danger {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }
    
    /* Numeric columns right-aligned */
    .table td:nth-child(n+5):nth-child(-n+24) {
        text-align: right;
    }
    
    .table th:nth-child(n+5):nth-child(-n+24) {
        text-align: right;
    }
</style>
@endpush