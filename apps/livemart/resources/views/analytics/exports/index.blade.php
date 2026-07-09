@extends('layouts.app')

@section('title', 'Riwayat Export')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Riwayat Export</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('analytics.index') }}">Analytics</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Riwayat Export</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">Daftar Export</h5>
            <span class="badge bg-primary rounded-pill" id="exportCount">{{ $exports->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tipe Export</th>
                            <th>Status</th>
                            <th>File</th>
                            <th>Ukuran</th>
                            <th>Diminta</th>
                            <th>Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exports as $index => $export)
                            <tr id="export-row-{{ $export->id }}">
                                <td>{{ $exports->firstItem() + $index }}</td>
                                <td>
                                    <span class="fw-medium">{{ $export->type_label }}</span>
                                </td>
                                <td>
                                    @include('analytics.partials.export_status_badge', ['status' => $export->status])
                                </td>
                                <td>
                                    @if($export->file_name)
                                        <small class="text-muted">{{ $export->file_name }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    @if($export->file_size)
                                        <small class="text-muted">{{ number_format($export->file_size / 1024, 1) }} KB</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $export->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if($export->completed_at)
                                        <small class="text-muted">{{ $export->completed_at->format('d/m/Y H:i') }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    @if($export->status === 'completed')
                                        <a href="{{ route('analytics.exports.download', $export->id) }}" 
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    @elseif($export->status === 'failed')
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="showError('{{ addslashes($export->error_message) }}')">
                                            <i class="fas fa-exclamation-circle me-1"></i> Error
                                        </button>
                                    @else
                                        <span class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-file-export fa-3x text-muted mb-3"></i>
                                        <h5 class="fw-normal">Belum ada export</h5>
                                        <p class="text-muted">Export akan muncul disini setelah anda melakukan export dari halaman analytics</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($exports->hasPages())
            <div class="card-footer">
                {{ $exports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh status for pending/processing exports
    document.addEventListener('DOMContentLoaded', function() {
        const processingRows = document.querySelectorAll('[id^="export-row-"]');
        processingRows.forEach(row => {
            const statusBadge = row.querySelector('.badge');
            if (statusBadge && (statusBadge.textContent.trim() === 'Processing' || statusBadge.textContent.trim() === 'Pending')) {
                const rowId = row.id.replace('export-row-', '');
                startPolling(rowId);
            }
        });
    });

    function startPolling(exportId) {
        const pollInterval = setInterval(function() {
            fetch('{{ route("analytics.exports.status", "") }}/' + exportId)
                .then(res => res.json())
                .then(data => {
                    const row = document.getElementById('export-row-' + exportId);
                    if (!row) {
                        clearInterval(pollInterval);
                        return;
                    }

                    // Update status badge
                    const statusCell = row.querySelector('td:nth-child(3)');
                    if (statusCell) {
                        statusCell.innerHTML = getStatusBadge(data.status);
                    }

                    // Update action button
                    const actionCell = row.querySelector('td:last-child');
                    if (actionCell) {
                        if (data.status === 'completed') {
                            actionCell.innerHTML = '<a href="{{ route("analytics.exports.download", "") }}/' + exportId + '" class="btn btn-sm btn-success"><i class="fas fa-download me-1"></i> Download</a>';
                            clearInterval(pollInterval);
                        } else if (data.status === 'failed') {
                            actionCell.innerHTML = '<button type="button" class="btn btn-sm btn-outline-danger" onclick="showError(\'' + (data.error_message || '') + '\')"><i class="fas fa-exclamation-circle me-1"></i> Error</button>';
                            clearInterval(pollInterval);
                        }
                    }

                    // Update completion time
                    const completedCell = row.querySelector('td:nth-child(7)');
                    if (completedCell && data.completed_at) {
                        completedCell.innerHTML = '<small class="text-muted">' + data.completed_at + '</small>';
                    }

                    // Update file info
                    const fileCell = row.querySelector('td:nth-child(4)');
                    if (fileCell && data.file_name) {
                        fileCell.innerHTML = '<small class="text-muted">' + data.file_name + '</small>';
                    }
                })
                .catch(() => {
                    clearInterval(pollInterval);
                });
        }, 3000); // Poll every 3 seconds
    }

    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-secondary">Pending</span>',
            'processing': '<span class="badge bg-primary">Processing <span class="spinner-border spinner-border-sm ms-1" role="status"></span></span>',
            'completed': '<span class="badge bg-success">Completed</span>',
            'failed': '<span class="badge bg-danger">Failed</span>',
        };
        return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
    }

    function showError(message) {
        alert('Error: ' + message);
    }
</script>
@endpush
