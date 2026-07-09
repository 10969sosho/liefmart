{{-- Skeleton Loading for Tables --}}
<div class="skeleton-table">
    <style>
        .skeleton-table { padding: 1rem; }
        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }
        .skeleton-row {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            align-items: center;
        }
        .skeleton-cell {
            height: 16px;
            border-radius: 4px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200px 100%;
            animation: shimmer 1.5s ease-in-out infinite;
        }
        .skeleton-cell:first-child { width: 30px; }
        .skeleton-cell:nth-child(2) { width: 100px; }
        .skeleton-cell:nth-child(3) { width: 100px; }
        .skeleton-cell:nth-child(4) { width: 150px; }
        .skeleton-cell:nth-child(5) { width: 80px; }
        .skeleton-cell:nth-child(6) { width: 80px; }
        .skeleton-cell:nth-child(7) { width: 180px; flex: 1; }
        .skeleton-cell:nth-child(8) { width: 40px; }
        .skeleton-cell:nth-child(9) { width: 80px; }
        .skeleton-cell:nth-child(10) { width: 120px; }
        .skeleton-cell:nth-child(11) { width: 120px; }
        .skeleton-cell:nth-child(12) { width: 120px; }
        .skeleton-cell:nth-child(13) { width: 100px; }
        .skeleton-cell:nth-child(14) { width: 100px; }
        .skeleton-cell:nth-child(15) { width: 100px; }
        .skeleton-cell:nth-child(16) { width: 100px; }
        .skeleton-cell:nth-child(17) { width: 100px; }
        .skeleton-cell:nth-child(18) { width: 100px; }
        .skeleton-cell:nth-child(19) { width: 80px; }
        .skeleton-cell:nth-child(20) { width: 80px; }
        .skeleton-cell:nth-child(21) { width: 80px; }
    </style>
    <div class="d-flex justify-content-between mb-3">
        <div class="skeleton-cell" style="width: 200px; height: 20px;"></div>
        <div class="skeleton-cell" style="width: 150px; height: 20px;"></div>
    </div>
    @for($i = 0; $i < 8; $i++)
        <div class="skeleton-row">
            @for($j = 0; $j < 10; $j++)
                <div class="skeleton-cell" style="width: {{ rand(60, 180) }}px; flex: {{ $j === 6 ? 1 : 'none' }};"></div>
            @endfor
        </div>
    @endfor
</div>
