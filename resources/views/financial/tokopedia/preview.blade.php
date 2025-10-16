@extends('layouts.app')

@section('title', 'Preview Data Keuangan Tokopedia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Preview Data Keuangan Tokopedia</h3>
                    <div class="card-tools">
                        <a href="{{ route('finance.tokopedia.import') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
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

                    <div class="alert alert-info">
                        <h5>Statistik Data:</h5>
                        <ul>
                            <li>Total data: {{ $totalRows }}</li>
                            <li>Data valid: {{ $validRows }}</li>
                            <li>Data tidak valid: {{ $invalidRows }}</li>
                        </ul>
                    </div>

                    @if($invalidRows > 0)
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Masalah Ditemukan ({{ $invalidRows }} masalah)</h5>
                        <p>Baris-baris berikut memiliki masalah:</p>
                        <div class="issues-container">
                            <ul>
                                @if(isset($issues) && is_array($issues) && count($issues) > 0)
                                    @foreach($issues as $row => $rowIssues)
                                        <li>
                                            <strong>Baris #{{ $row }}:</strong>
                                            <ul>
                                                @foreach($rowIssues as $issue)
                                                    <li>{{ $issue }}</li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @endforeach
                                @else
                                    <li>Ada masalah validasi tetapi detil error tidak tersedia.</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    @endif

                    @if(!empty($previewData))
                    <div class="mb-3">
                        <h5>Preview Data Yang Akan Disimpan:</h5>
                        <p class="text-muted small">Data berikut adalah format yang akan disimpan ke database.</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal Order</th>
                                    <th>Hari Order</th>
                                    <th>No. Order</th>
                                    <th>No. Invoice</th>
                                    <th>Tax ID</th>
                                    <th>QTY</th>
                                    <th>Nominal Harga</th>
                                    <th>Voucher Ditanggung Penjual</th>
                                    <th>Komisi AMS/Affiliate</th>
                                    <th>Biaya Admin</th>
                                    <th>Biaya Layanan</th>
                                    <th>Diskon 5</th>
                                    <th>Diskon 6</th>
                                    <th>Adjustment</th>
                                    <th>Nominal Fix</th>
                                    <th>Saldo Masuk</th>
                                    <th>Tanggal Pembayaran</th>
                                    <th>Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewData as $index => $row)
                                @if(isset($data[$index]['_valid']) && $data[$index]['_valid'])
                                <tr class="{{ $index % 2 == 0 ? 'table-primary' : '' }}">
                                    <td>
                                        @if(isset($row['tanggal_order']) && $row['tanggal_order'] !== 'N/A')
                                            @php
                                                $date = is_string($row['tanggal_order']) 
                                                    ? \Carbon\Carbon::parse($row['tanggal_order']) 
                                                    : $row['tanggal_order'];
                                                echo $date->format('d-m-Y');
                                            @endphp
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $row['hari_order'] ?? '-' }}</td>
                                    <td>{{ $row['no_order'] ?? '-' }}</td>
                                    <td>{{ $row['no_invoice'] ?? '-' }}</td>
                                    <td>{{ $row['tax_id'] ?? '-' }}</td>
                                    <td class="text-end">{{ isset($row['qty']) ? number_format($row['qty'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_harga']) ? number_format($row['nominal_harga'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_diskon1']) ? number_format($row['nominal_diskon1'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_diskon2']) ? number_format($row['nominal_diskon2'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_diskon3']) ? number_format($row['nominal_diskon3'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_diskon4']) ? number_format($row['nominal_diskon4'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_diskon5']) ? number_format($row['nominal_diskon5'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_diskon6']) ? number_format($row['nominal_diskon6'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['adjustment']) ? number_format($row['adjustment'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['nominal_fix']) ? number_format($row['nominal_fix'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ isset($row['saldo_masuk']) ? number_format($row['saldo_masuk'], 0, ',', '.') : '-' }}</td>
                                    <td>
                                        @if(isset($row['tanggal_masuk_pembayaran']) && $row['tanggal_masuk_pembayaran'] !== 'N/A')
                                            @php
                                                $paymentDate = is_string($row['tanggal_masuk_pembayaran']) 
                                                    ? \Carbon\Carbon::parse($row['tanggal_masuk_pembayaran']) 
                                                    : $row['tanggal_masuk_pembayaran'];
                                                echo $paymentDate->format('d-m-Y');
                                            @endphp
                                            <div class="small text-muted">{{ $row['hari_masuk_pembayaran'] ?? '' }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end {{ isset($row['outstanding']) && $row['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ isset($row['outstanding']) ? number_format($row['outstanding'], 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-danger">
                        <h5>Tidak Ada Data Valid untuk Ditampilkan</h5>
                        <p>Semua data memiliki masalah validasi. Silakan periksa daftar masalah di atas.</p>
                    </div>
                    @endif
                    
                    <div class="mt-3">
                        <form action="{{ route('finance.tokopedia.import-process') }}" method="POST" class="d-inline">
                            @csrf
                            <!-- Use session token instead of sending all data via POST -->
                            <input type="hidden" name="process_token" value="{{ session('tokopedia_process_token', uniqid()) }}">
                            
                            <button type="submit" class="btn btn-success" {{ $validRows == 0 ? 'disabled' : '' }}>
                                <i class="fas fa-check"></i> Proses {{ $validRows }} Data Valid
                            </button>
                            
                            <a href="{{ route('finance.tokopedia.import') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Fix for better visualization */
    .table-responsive {
        max-height: 600px;
        overflow-y: auto;
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
    
    /* Issues container with fixed height and scroll */
    .issues-container {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 15px;
        background-color: #fff;
        margin-top: 10px;
    }
    
    .issues-container ul {
        margin-bottom: 0;
    }
    
    .issues-container li {
        margin-bottom: 8px;
    }
    
    .issues-container li:last-child {
        margin-bottom: 0;
    }
    
    /* Custom scrollbar for issues container */
    .issues-container::-webkit-scrollbar {
        width: 8px;
    }
    
    .issues-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .issues-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .issues-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endpush