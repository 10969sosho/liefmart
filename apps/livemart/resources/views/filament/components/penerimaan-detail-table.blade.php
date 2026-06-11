<style>
    .dark #penerimaan-detail-table thead {
        background-color: #1e1e2a !important;
    }
    .dark #penerimaan-detail-table thead th {
        color: #9ca3af !important;
    }
    .dark #penerimaan-detail-table thead th:hover {
        color: #e5e7eb !important;
    }
    .dark #penerimaan-detail-table thead th .sort-icon {
        color: #6b7280 !important;
    }
    .dark #penerimaan-detail-table tbody tr {
        border-bottom-color: #2a2a3d !important;
    }
    .dark #penerimaan-detail-table tbody tr:hover {
        background-color: #252538 !important;
    }
    .dark #penerimaan-detail-table tbody td .font-medium {
        color: #e5e7eb !important;
    }
    .dark #penerimaan-detail-table tbody td {
        color: #d1d5db !important;
    }
    .dark #penerimaan-detail-table tbody td .text-gray-400 {
        color: #6b7280 !important;
    }
    .dark #penerimaan-detail-table tfoot {
        background-color: #1e1e2a !important;
        border-top-color: #374151 !important;
    }
    .dark #penerimaan-detail-table tfoot td {
        color: #9ca3af !important;
    }
    .dark #penerimaan-detail-table tfoot .font-semibold {
        color: #e5e7eb !important;
    }
    .dark #penerimaan-detail-table tbody tr td .bg-blue-50 {
        background-color: rgba(59, 130, 246, 0.2) !important;
        color: #93c5fd !important;
    }
</style>
<div class="overflow-x-auto" style="max-height: 400px; overflow-y: auto;">
    <table class="w-full text-sm" id="penerimaan-detail-table">
        <thead class="bg-gray-50" style="position: sticky; top: 0; z-index: 1;">
            <tr>
                <th class="px-2 py-2 text-left font-medium text-gray-500 cursor-pointer hover:text-gray-700 sortable" data-sort="string" style="width: 35%; min-width: 180px;">
                    <span class="inline-flex items-center gap-1">
                        Barang
                        <svg class="sort-icon w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </span>
                </th>
                <th class="px-2 py-2 text-center font-medium text-gray-500 cursor-pointer hover:text-gray-700 sortable" data-sort="number" style="width: 8%; min-width: 50px;">
                    <span class="inline-flex items-center gap-1 justify-center">
                        Qty
                        <svg class="sort-icon w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </span>
                </th>
                <th class="px-2 py-2 text-right font-medium text-gray-500 cursor-pointer hover:text-gray-700 sortable" data-sort="number" style="width: 12%; min-width: 90px;">
                    <span class="inline-flex items-center gap-1 justify-end">
                        Harga
                        <svg class="sort-icon w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </span>
                </th>
                <th class="px-2 py-2 text-center font-medium text-gray-500" style="width: 15%; min-width: 120px;">Diskon</th>
                <th class="px-2 py-2 text-right font-medium text-gray-500 cursor-pointer hover:text-gray-700 sortable" data-sort="number" style="width: 15%; min-width: 110px;">
                    <span class="inline-flex items-center gap-1 justify-end">
                        Harga Stlh Diskon
                        <svg class="sort-icon w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </span>
                </th>
                <th class="px-2 py-2 text-right font-medium text-gray-500 cursor-pointer hover:text-gray-700 sortable" data-sort="number" style="width: 12%; min-width: 90px;">
                    <span class="inline-flex items-center gap-1 justify-end">
                        Subtotal
                        <svg class="sort-icon w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $penerimaan = $getRecord();
                $details = $penerimaan->details ?? collect();
                $totalItem = $details->count();
            @endphp
            @forelse($details as $detail)
                @php
                    $harga = (float) $detail->harga_hpp;
                    $diskonList = [];

                    for ($i = 1; $i <= 5; $i++) {
                        $persen = (float) ($detail->{'diskon_persen_' . $i} ?? 0);
                        $nominal = (float) ($detail->{'diskon_nominal_' . $i} ?? 0);

                        if ($persen > 0) {
                            $harga -= $harga * $persen / 100;
                            $diskonList[] = $persen . '%';
                        }
                        if ($nominal > 0) {
                            $harga -= $nominal;
                            $diskonList[] = 'Rp ' . number_format(round($nominal), 0, ',', '.');
                        }
                    }

                    $harga = max(0, $harga);
                @endphp
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-2 py-2">
                        <div class="font-medium text-gray-900 truncate">{{ $detail->product->name ?? '-' }}</div>
                    </td>
                    <td class="px-2 py-2 text-center">{{ $detail->qty }}</td>
                    <td class="px-2 py-2 text-right whitespace-nowrap">
                        <span class="sort-value" style="display:none">{{ $detail->harga_hpp }}</span>
                        @if($detail->is_free)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Free</span>
                        @else
                            Rp {{ number_format($detail->harga_hpp, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="px-2 py-2 text-center">
                        @if($detail->is_free)
                            <span class="text-gray-400">-</span>
                        @elseif(count($diskonList) > 0)
                            <div class="flex flex-wrap gap-0.5 justify-center">
                                @foreach($diskonList as $diskon)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">{{ $diskon }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-right font-medium whitespace-nowrap">
                        <span class="sort-value" style="display:none">{{ $harga }}</span>
                        @if($detail->is_free)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Free</span>
                        @else
                            Rp {{ number_format($harga, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="px-2 py-2 text-right font-medium whitespace-nowrap">
                        <span class="sort-value" style="display:none">{{ $detail->subtotal }}</span>
                        Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-2 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Belum ada barang</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="border-t-2 border-gray-200 bg-gray-50" style="position: sticky; bottom: 0;">
            <tr>
                <td colspan="5" class="px-2 py-2 text-right font-semibold text-gray-700">Total:</td>
                <td class="px-2 py-2 text-right font-semibold text-gray-900 whitespace-nowrap">
                    Rp {{ number_format(round($penerimaan->total_harga ?? 0), 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="6" class="px-2 py-1 text-xs text-gray-500">Total Item: {{ $totalItem }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.getElementById('penerimaan-detail-table');
                if (!table) return;

                const tbody = table.querySelector('tbody');
                const headers = table.querySelectorAll('.sortable');

                headers.forEach(header => {
                    header.addEventListener('click', function () {
                        const sortType = this.dataset.sort;
                        const columnIndex = Array.from(this.parentElement.children).indexOf(this);
                        const isAsc = this.dataset.direction !== 'asc';
                        const rows = Array.from(tbody.querySelectorAll('tr'));

                        if (rows.length === 0) return;

                        headers.forEach(h => {
                            h.dataset.direction = '';
                            h.querySelector('.sort-icon').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>';
                        });

                        this.dataset.direction = isAsc ? 'asc' : 'desc';
                        this.querySelector('.sort-icon').innerHTML = isAsc
                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>'
                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>';

                        rows.sort((a, b) => {
                            const aCell = a.querySelectorAll('td')[columnIndex];
                            const bCell = b.querySelectorAll('td')[columnIndex];
                            if (!aCell || !bCell) return 0;

                            let aVal, bVal;

                            const aSort = aCell.querySelector('.sort-value');
                            const bSort = bCell.querySelector('.sort-value');
                            aVal = sortType === 'number'
                                ? parseFloat(aSort?.textContent?.trim()) || 0
                                : (aCell.textContent || '').trim().toLowerCase();
                            bVal = sortType === 'number'
                                ? parseFloat(bSort?.textContent?.trim()) || 0
                                : (bCell.textContent || '').trim().toLowerCase();

                            if (aVal < bVal) return isAsc ? -1 : 1;
                            if (aVal > bVal) return isAsc ? 1 : -1;
                            return 0;
                        });

                        rows.forEach(row => tbody.appendChild(row));
                    });
                });
            });
        </script>
    @endpush
@endonce
