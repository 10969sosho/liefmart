<div class="stat-card card {{ $color ?? 'primary' }} mb-4">
    <div class="card-body d-flex align-items-center">
        <div class="icon-box rounded-circle d-flex align-items-center justify-content-center me-3"
             style="width: 48px; height: 48px; background-color: {{ $bgColor ?? 'rgba(13, 110, 253, 0.15)' }};">
            <i class="{{ $icon ?? 'fas fa-chart-line' }} {{ $iconColor ?? 'text-primary' }}"></i>
        </div>
        <div>
            <h3 class="mb-0 fs-4 fw-bold">{{ $value ?? '0' }}</h3>
            <p class="mb-0 text-muted fs-sm">{{ $title ?? 'Stat Title' }}</p>
        </div>
    </div>
</div> 