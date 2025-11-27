@extends('layouts.app')

@section('page-title', 'Preview Import Arus Kas TikTok2')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Preview Data Arus Kas TikTok2</h4>
                </div>

                <div class="card-body">
                    @if (!empty($issues))
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Perhatian!</h5>
                            <p>Terdapat {{ count($issues) }} baris data yang memiliki masalah. Data yang bermasalah tidak akan diimpor.</p>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Ringkasan Data</h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">Total data: <strong>{{ $summary['total'] }}</strong></li>
                                        <li class="mb-2">Data valid: <strong class="text-success">{{ $summary['valid'] }}</strong></li>
                                        <li class="mb-2">Data tidak valid: <strong class="text-danger">{{ $summary['invalid'] }}</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Detail Masalah</h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">Duplikasi dalam file impor: <strong class="text-warning">{{ $summary['duplicate_in_import'] }}</strong></li>
                                        <li class="mb-2">Sudah ada di database: <strong class="text-warning">{{ $summary['already_exists'] }}</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Semua kolom pada data TikTok wajib diisi.
                    </div>

                    <div class="mb-3">
                        <span class="badge bg-success me-2">Valid</span> Data valid yang akan diimpor
                        <span class="badge bg-danger ms-3 me-2">Invalid</span> Data tidak valid dan akan dilewati
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="8%">Status</th>
                                    <th class="bg-primary text-white">Tanggal Pembayaran</th>
                                    <th class="bg-primary text-white">Deskripsi</th>
                                    <th class="bg-primary text-white">No. Pesanan</th>
                                    <th class="bg-primary text-white">Tanggal Pesanan</th>
                                    <th class="bg-primary text-white">Pembayaran</th>
                                    <th class="bg-primary text-white">Saldo Akhir</th>
                                    <th width="20%">Masalah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $index => $row)
                                    <tr class="{{ $row['_valid'] ? '' : 'table-danger' }}">
                                        <td>{{ $row['_row_number'] }}</td>
                                        <td>
                                            @if ($row['_valid'])
                                                <span class="badge bg-success">Valid</span>
                                            @else
                                                <span class="badge bg-danger">Invalid</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold">
                                            @php
                                                $tanggalPembayaran = $row['raw_tanggal_pembayaran'] ?? $row['Tanggal Pembayaran'];
                                                // Format tanggal jika ada
                                                if ($tanggalPembayaran && $tanggalPembayaran !== '-') {
                                                    try {
                                                        // Handle Excel serial date (numeric format)
                                                        if (is_numeric($tanggalPembayaran)) {
                                                            $tanggalPembayaran = \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                                                                ->addDays($tanggalPembayaran)
                                                                ->format('d/m/Y');
                                                        } else {
                                                            // Try to parse and format existing date
                                                            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
                                                            $formatted = false;
                                                            foreach ($formats as $format) {
                                                                try {
                                                                    $tanggalPembayaran = \Carbon\Carbon::createFromFormat($format, $tanggalPembayaran)->format('d/m/Y');
                                                                    $formatted = true;
                                                                    break;
                                                                } catch (\Exception $e) {
                                                                    continue;
                                                                }
                                                            }
                                                            if (!$formatted) {
                                                                $tanggalPembayaran = \Carbon\Carbon::parse($tanggalPembayaran)->format('d/m/Y');
                                                            }
                                                        }
                                                    } catch (\Exception $e) {
                                                        // Keep original if formatting fails
                                                    }
                                                }
                                            @endphp
                                            {{ $tanggalPembayaran }}
                                        </td>
                                        <td>{{ $row['Deskripsi'] }}</td>
                                        <td class="fw-bold">{{ $row['No. Pesanan'] }}</td>
                                        <td>
                                            @php
                                                $tanggalPesanan = $row['raw_tanggal_pesanan'] ?? ($row['Tanggal Pesanan'] ?? '-');
                                                // Format tanggal pesanan jika ada dan bukan '-'
                                                if ($tanggalPesanan && $tanggalPesanan !== '-') {
                                                    try {
                                                        // Handle Excel serial date (numeric format)
                                                        if (is_numeric($tanggalPesanan)) {
                                                            $tanggalPesanan = \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                                                                ->addDays($tanggalPesanan)
                                                                ->format('d/m/Y');
                                                        } else {
                                                            // Try to parse and format existing date
                                                            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
                                                            $formatted = false;
                                                            foreach ($formats as $format) {
                                                                try {
                                                                    $tanggalPesanan = \Carbon\Carbon::createFromFormat($format, $tanggalPesanan)->format('d/m/Y');
                                                                    $formatted = true;
                                                                    break;
                                                                } catch (\Exception $e) {
                                                                    continue;
                                                                }
                                                            }
                                                            if (!$formatted) {
                                                                $tanggalPesanan = \Carbon\Carbon::parse($tanggalPesanan)->format('d/m/Y');
                                                            }
                                                        }
                                                    } catch (\Exception $e) {
                                                        // Keep original if formatting fails
                                                    }
                                                }
                                            @endphp
                                            {{ $tanggalPesanan }}
                                        </td>
                                        <td class="fw-bold text-end">
                                            @php
                                                $pembayaran = $row['raw_pembayaran'] ?? $row['Pembayaran'];
                                                $pembayaran = is_numeric($pembayaran) ? $pembayaran : (float)str_replace(['.', ','], ['', '.'], $pembayaran);
                                            @endphp
                                            {{ number_format($pembayaran, 0, ',', '.') }}
                                        </td>
                                        <td class="fw-bold text-end">
                                            @php
                                                $saldoAkhir = $row['raw_saldo_akhir'] ?? $row['Saldo Akhir'];
                                                $saldoAkhir = is_numeric($saldoAkhir) ? $saldoAkhir : (float)str_replace(['.', ','], ['', '.'], $saldoAkhir);
                                            @endphp
                                            {{ number_format($saldoAkhir, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @if (!$row['_valid'])
                                                <ul class="mb-0 ps-3 small">
                                                    @foreach ($row['_issues'] as $issue)
                                                        <li>{{ $issue }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 card bg-light shadow-sm border-0">
                        <div class="card-body">
                            <form id="processForm" action="{{ route('finance.aruskastiktok2.process') }}" method="POST">
                                @csrf
                                <input type="hidden" name="import_session_id" value="{{ $import_session_id ?? '' }}" id="importSessionId">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('finance.aruskastiktok2.import') }}" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                    <div>
                                        <button type="submit" id="saveButton" class="btn btn-primary" {{ empty(array_filter($data, fn($row) => $row['_valid'])) ? 'disabled' : '' }}>
                                            <i class="fas fa-save"></i> Simpan Data Valid
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table th {
        font-weight: 600;
        vertical-align: middle;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-weight: 500;
        padding: 0.5em 0.75em;
    }
    .text-muted {
        opacity: 0.7;
    }
    .bg-primary.text-white {
        background-color: #4F46E5 !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('processForm');
    const saveButton = document.getElementById('saveButton');
    
    if (form && saveButton) {
        saveButton.addEventListener('click', function() {
            console.log('=== TIKTOK SAVE BUTTON CLICKED ===');
            console.log('Import session ID:', '{{ $import_session_id ?? "NOT_SET" }}');
        });
        
        form.addEventListener('submit', function(e) {
            console.log('=== TIKTOK FORM SUBMIT START ===');
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            console.log('Button disabled:', saveButton.disabled);
            console.log('Valid data count:', {{ $summary['valid'] ?? 0 }});
            console.log('Total data count:', {{ $summary['total'] ?? 0 }});
            console.log('Import session ID:', '{{ $import_session_id ?? "NOT_SET" }}');
            
            if (saveButton.disabled) {
                console.log('Submit prevented - button is disabled');
                e.preventDefault();
                return false;
            }
            
            // Disable button to prevent double submission
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            
            console.log('Form submitted successfully');
        });
    }
});
</script>

@endsection 