@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Preview Data Finance Shopee</span>
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
                                $bankInfo = \App\Models\ShopeeFinancialTransaction::getBankAccountInfo();
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
                            <li>Preview data count: {{ is_array($previewData) ? count($previewData) : 'Not an array' }}</li>
                        </ul>
                    </div>

                    @if($invalidRows > 0)
                    <div class="alert alert-warning">
                        <h5>Daftar Masalah:</h5>
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
                    @endif
                    
                    @php
                    $hasPreviewData = isset($previewData) && is_array($previewData) && count($previewData) > 0;
                    @endphp
                    
                    @if($hasPreviewData)
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
                                    <th>Status</th>
                                    <th>Nominal Harga</th>
                                    <th>Voucher</th>
                                    <th>Komisi</th>
                                    <th>Biaya Admin</th>
                                    <th>Biaya Layanan</th>
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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewData as $order)
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
                                                </td>
                                            @else
                                                <!-- Empty cells to maintain table structure -->
                                                <td style="display: none;"></td>
                                                <td style="display: none;"></td>
                                                <td style="display: none;"></td>
                                            @endif
                                            <td>{{ $invoice['no_invoice'] }}</td>
                                            <td>{{ $invoice['tax_id'] }}</td>
                                            <td>{{ isset($invoice['is_pkp']) && $invoice['is_pkp'] ? 'PKP' : 'Non-PKP' }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_harga'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon1'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon2'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon3'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon4'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon5'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon6'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon7'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon8'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon9'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon10'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon11'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_diskon12'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['adjustment'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['nominal_fix'], 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($invoice['saldo_masuk'], 0, ',', '.') }}</td>
                                            @if($invIndex === 0)
                                                <td rowspan="{{ count($order['invoices']) }}">
                                                    {{ \Carbon\Carbon::parse($order['tanggal_masuk_pembayaran'])->format('d-m-Y') }}
                                                    <div class="small text-muted">{{ $order['hari_masuk_pembayaran'] }}</div>
                                                </td>
                                            @else
                                                <td style="display: none;"></td>
                                            @endif
                                            <td class="text-end {{ $invoice['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($invoice['outstanding'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
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
                        <form action="{{ route('finance.shopee.import-process') }}" method="POST" class="d-inline">
                            @csrf
                            <!-- Use session token instead of sending all data via POST -->
                            <input type="hidden" name="process_token" value="{{ session('shopee_process_token', uniqid()) }}">
                            
                            <button type="submit" class="btn btn-success" {{ $validRows == 0 ? 'disabled' : '' }}>
                                Proses {{ $validRows }} Data Valid
                            </button>
                        </form>
                        <a href="{{ route('finance.shopee.import') }}" class="btn btn-secondary">Kembali</a>
                    </div>
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