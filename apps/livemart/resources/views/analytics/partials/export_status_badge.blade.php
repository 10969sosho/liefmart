<span class="badge bg-{{ $status === 'completed' ? 'success' : ($status === 'failed' ? 'danger' : ($status === 'processing' ? 'primary' : 'secondary')) }}">
    {{ ucfirst($status) }}
    @if($status === 'processing')
        <span class="spinner-border spinner-border-sm ms-1" role="status" style="width: 0.65rem; height: 0.65rem;"></span>
    @endif
</span>
