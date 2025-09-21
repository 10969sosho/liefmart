@extends('layouts.app')

@section('page-title', 'Preview Import Arus Kas Tokopedia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Preview Data Arus Kas Tokopedia</h4>
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
                                        <li class="mb-2">Data dengan field kosong/tidak valid: <strong class="text-warning">{{ $summary['invalid_data'] }}</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Hanya kolom "Tanggal Masuk Pembayaran", "Description", dan "Nominal (Rp)" yang wajib diisi.
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
                                    <th class="date-col">Date</th>
                                    <th>Mutation (Debit/Credit)</th>
                                    <th>Description</th>
                                    <th>Nominal (Rp)</th>
                                    <th>Balance (Rp)</th>
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
                                        <td class="date-col fw-bold">
                                            @if (!empty($row['Date']))
                                                {{ \Carbon\Carbon::parse($row['Date'])->format('d-m-Y') }}
                                            @endif
                                        </td>
                                        <td>{{ $row['Mutation (Debit/Credit)'] }}</td>
                                        <td>{{ $row['Description'] }}</td>
                                        <td class="fw-bold text-end">{{ number_format((float) $row['Nominal (Rp)'], 0, ',', '.') }}</td>
                                        <td class="fw-bold text-end">{{ number_format((float) $row['Balance (Rp)'], 0, ',', '.') }}</td>
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
                            <form action="{{ route('finance.aruskastokopedia.process') }}" method="POST">
                                @csrf
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('finance.aruskastokopedia.import') }}" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary" {{ empty(array_filter($data, fn($row) => $row['_valid'])) ? 'disabled' : '' }}>
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
    .table th.date-col, .table td.date-col {
        white-space: nowrap;
        min-width: 110px;
        text-align: center;
    }
</style>
@endsection 