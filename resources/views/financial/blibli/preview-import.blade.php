@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4>Preview Import Data Keuangan Blibli</h4>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-{{ session('status_type') ?? 'info' }}">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if(isset($summary))
                        <div class="alert alert-info">
                            <h5>Ringkasan Data:</h5>
                            <ul>
                                <li>Total Baris: {{ $summary['total_records'] ?? 0 }}</li>
                                <li>Data Valid: {{ count($summary['valid_data'] ?? []) }}</li>
                                <li>Data Duplikat: {{ count($summary['duplicates'] ?? []) }}</li>
                                <li>Data Tidak Valid: {{ count($summary['invalid_data'] ?? []) }}</li>
                            </ul>
                        </div>

                        @if(!empty($summary['valid_data']))
                            <form action="{{ route('blibli.financial.process') }}" method="POST">
                                @csrf
                                <div class="card mb-4">
                                    <div class="card-header bg-success text-white">
                                        <h5>Data Valid ({{ count($summary['valid_data']) }} baris)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive disable-fixed-scrollbar" style="max-height: 65vh; overflow-y: auto !important; overflow-x: auto !important; display: block; width: 100%;">
                                            <table class="table table-bordered table-striped" style="min-width: 1000px; width: auto !important;">
                                                <thead style="position: sticky; top: 0; z-index: 1;">
                                                    <tr class="bg-white">
                                                        <th>No. Invoice</th>
                                                        <th>No. Order</th>
                                                        <th>Tanggal</th>
                                                        <th>Tipe</th>
                                                        <th>Metode Pembayaran</th>
                                                        <th>Jumlah</th>
                                                        <th>Fee</th>
                                                        <th>Pajak</th>
                                                        <th>Total</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($summary['valid_data'] as $index => $data)
                                                        <tr>
                                                            <td>{{ $data['invoice_number'] }}</td>
                                                            <td>{{ $data['order_number'] }}</td>
                                                            <td>{{ $data['transaction_date'] }}</td>
                                                            <td>{{ $data['transaction_type'] ?? '-' }}</td>
                                                            <td>{{ $data['payment_method'] ?? '-' }}</td>
                                                            <td>{{ number_format($data['amount'] ?? 0, 0, ',', '.') }}</td>
                                                            <td>{{ number_format($data['fee'] ?? 0, 0, ',', '.') }}</td>
                                                            <td>{{ number_format($data['tax'] ?? 0, 0, ',', '.') }}</td>
                                                            <td>{{ number_format($data['total_amount'] ?? 0, 0, ',', '.') }}</td>
                                                            <td>{{ $data['status'] ?? 'Pending' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Proses Import
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Tidak ada data valid untuk diimport.
                            </div>
                        @endif

                        @if(!empty($summary['duplicates']))
                            <div class="card mt-4 mb-4">
                                <div class="card-header bg-warning">
                                    <h5>Data Duplikat ({{ count($summary['duplicates']) }} baris)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive disable-fixed-scrollbar" style="max-height: 65vh; overflow-y: auto !important; overflow-x: auto !important; display: block; width: 100%;">
                                        <table class="table table-bordered" style="min-width: 1000px; width: auto !important;">
                                            <thead style="position: sticky; top: 0; z-index: 1;">
                                                <tr class="bg-white">
                                                    <th>Baris</th>
                                                    <th>No. Invoice</th>
                                                    <th>No. Order</th>
                                                    <th>Tanggal</th>
                                                    <th>Alasan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($summary['duplicates'] as $data)
                                                    <tr>
                                                        <td>{{ $data['row'] }}</td>
                                                        <td>{{ $data['data']['invoice_number'] ?? '-' }}</td>
                                                        <td>{{ $data['data']['order_number'] ?? '-' }}</td>
                                                        <td>{{ $data['data']['transaction_date'] ?? '-' }}</td>
                                                        <td>{{ $data['reason'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(!empty($summary['invalid_data']))
                            <div class="card mt-4">
                                <div class="card-header bg-danger text-white">
                                    <h5>Data Tidak Valid ({{ count($summary['invalid_data']) }} baris)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive disable-fixed-scrollbar" style="max-height: 65vh; overflow-y: auto !important; overflow-x: auto !important; display: block; width: 100%;">
                                        <table class="table table-bordered" style="min-width: 1000px; width: auto !important;">
                                            <thead style="position: sticky; top: 0; z-index: 1;">
                                                <tr class="bg-white">
                                                    <th>Baris</th>
                                                    <th>Data</th>
                                                    <th>Alasan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($summary['invalid_data'] as $data)
                                                    <tr>
                                                        <td>{{ $data['row'] }}</td>
                                                        <td>
                                                            @if(isset($data['data']) && is_array($data['data']))
                                                                <ul class="list-unstyled">
                                                                    @foreach($data['data'] as $key => $value)
                                                                        <li><strong>{{ $key }}:</strong> {{ $value }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @else
                                                                <span class="text-muted">Data tidak tersedia</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($data['issues']) && is_array($data['issues']))
                                                                <ul>
                                                                    @foreach($data['issues'] as $issue)
                                                                        <li>{{ $issue }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @elseif(isset($data['reason']))
                                                                {{ $data['reason'] }}
                                                            @else
                                                                Data tidak valid
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Tidak ada data untuk ditampilkan. Silahkan upload file kembali.
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('blibli.financial.import') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Disable fixed-table-scroll.js untuk halaman ini
document.addEventListener('DOMContentLoaded', function() {
    // Setiap container dengan class disable-fixed-scrollbar
    // akan di-skip oleh fixed-table-scroll.js
    const tableContainers = document.querySelectorAll('.table-responsive');
    tableContainers.forEach(container => {
        container.classList.add('disable-fixed-scrollbar');
    });
});
</script>
@endpush 