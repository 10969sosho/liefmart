@forelse($profitData as $index => $item)
    <tr>
        <td class="text-center">{{ (($page - 1) * $perPage) + $loop->iteration }}</td>
        <td>{{ $item['sale_date'] ? \Carbon\Carbon::parse($item['sale_date'])->format('d/m/Y') : '-' }}</td>
        <td>{{ $item['payment_date'] ? \Carbon\Carbon::parse($item['payment_date'])->format('d/m/Y') : '-' }}</td>
        <td>{{ $item['customer_name'] }}</td>
        <td>{{ $item['po_number'] }}</td>
        <td>{{ $item['invoice_number'] }}</td>
        <td>{{ $item['product_name'] }}</td>
        <td class="text-center">{{ number_format($item['quantity'], 0) }}</td>
        <td>{{ $item['sku'] }}</td>
        <td class="text-end">Rp {{ number_format($item['payment_per_invoice'], 0, ',', '.') }}</td>
        <td class="text-end">Rp {{ number_format($item['payment_per_invoice_without_ppn'], 0, ',', '.') }}</td>
        <td class="text-end">Rp {{ number_format($item['payment_per_product_without_ppn'], 0, ',', '.') }}</td>
        <td class="text-end">Rp {{ number_format($item['payment_per_pcs_without_ppn'], 0, ',', '.') }}</td>
        <td class="text-end">Rp {{ number_format($item['cost_price'], 0, ',', '.') }}</td>
        <td class="text-end">Rp {{ number_format($item['total_cost_price'], 0, ',', '.') }}</td>
        <td class="text-end {{ $item['profit_per_unit'] >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($item['profit_per_unit'], 0, ',', '.') }}
        </td>
        <td class="text-end {{ $item['profit_per_product'] >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($item['profit_per_product'], 0, ',', '.') }}
        </td>
        <td class="text-end {{ $item['profit_per_invoice'] >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($item['profit_per_invoice'], 0, ',', '.') }}
        </td>
        <td class="text-center {{ $item['margin_per_unit'] >= 0 ? 'text-success' : 'text-danger' }}">
            {{ number_format($item['margin_per_unit'], 2) }}%
        </td>
        <td class="text-center {{ $item['margin_per_product'] >= 0 ? 'text-success' : 'text-danger' }}">
            {{ number_format($item['margin_per_product'], 2) }}%
        </td>
        <td class="text-center {{ $item['margin_per_invoice'] >= 0 ? 'text-success' : 'text-danger' }}">
            {{ number_format($item['margin_per_invoice'], 2) }}%
        </td>
    </tr>
@empty
    <tr>
        <td colspan="21" class="text-center py-4">
            <div class="d-flex flex-column align-items-center">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <h5 class="fw-normal">Belum ada data profit</h5>
                <p class="text-muted">Tidak ada data penjualan offline dalam periode yang dipilih</p>
            </div>
        </td>
    </tr>
@endforelse
