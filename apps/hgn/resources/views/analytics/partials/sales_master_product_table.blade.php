@if(count($productRows) > 0)
    <!-- Calculation Method Info -->
    <div class="alert alert-info mb-4">
        <h5 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Informasi Perhitungan</h5>
        <p><strong>Perhitungan Gross Profit (Sederhana):</strong></p>
        <ul>
            <li><strong>Total Saldo Masuk:</strong> Total uang masuk dari semua order</li>
            <li><strong>Total Saldo Masuk - PPN:</strong> Total saldo masuk ÷ 1.11 (menghilangkan PPN 11%)</li>
            <li><strong>Total Modal:</strong> Total biaya modal untuk semua produk</li>
            <li><strong>Gross Profit:</strong> (Total Saldo Masuk - PPN) - Total Modal</li>
            <li><strong>Margin:</strong> (Gross Profit ÷ Total Saldo Masuk - PPN) × 100%</li>
        </ul>
    </div>
    
    <!-- Master Products Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th style="width: 8%;">Tanggal</th>
                    <th style="width: 7%;">No Pesanan</th>
                    <th style="width: 7%;">No Invoice</th>
                    <th style="min-width: 300px;">Nama Produk (Platform)</th>
                    <th style="width: 7%;">Variasi</th>
                    <th class="text-end" style="width: 5%;">QTY (Platform)</th>
                    <th style="width: 8%;">SKU</th>
                    <th style="min-width: 300px;">Master Barang</th>
                    <th class="text-end" style="width: 5%;">QTY</th>
                    <th class="text-end" style="width: 8%;">Saldo Masuk (Rp)</th>
                    <th class="text-end" style="width: 8%;">Saldo Masuk - PPN (Rp)</th>
                    <th class="text-end" style="width: 8%;">Harga Pricelist (Rp)</th>
                    <th class="text-end" style="width: 8%;">Pricelist × QTY (Rp)</th>
                    <th class="text-end" style="width: 8%;">Total Pricelist (Rp)</th>
                    <th class="text-end" style="width: 7%;">% dalam Order</th>
                    <th class="text-end" style="width: 8%;">Masuk per Produk (Rp)</th>
                    <th class="text-end" style="width: 8%;">Masuk per Produk - PPN (Rp)</th>
                    <th class="text-end" style="width: 8%;">Harga Modal (Rp)</th>
                    <th class="text-end" style="width: 7%;">Profit per PCS (Rp)</th>
                    <th class="text-end" style="width: 7%;">Gross Profit (Rp)</th>
                    <th class="text-end" style="width: 7%;">Margin per pcs (%)</th>
                    <th class="text-end" style="width: 7%;">Margin per item (%)</th>
                    <th style="width: 5%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productRows as $row)
                    @php
                        $rowClass = '';
                        $profitPerPcs = (float)($row['profit_per_pcs'] ?? 0);
                        $price = (float)($row['price'] ?? 0);
                        if($profitPerPcs < 0) { $rowClass = 'table-danger'; }
                        elseif($price == 0) { $rowClass = 'table-warning'; }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>{{ $row['order_date_formatted'] ?? ($row['order_date'] ? date('d/m/Y', strtotime($row['order_date'])) : '-') }}</td>
                        <td>{{ $row['order_number'] ?? '-' }}</td>
                        <td>{{ $row['invoice_number'] ?? '-' }}</td>
                        <td style="min-width: 300px;">{{ $row['platform_product_name'] ?? '-' }}</td>
                        <td>{{ $row['platform_product_variant'] ?? '-' }}</td>
                        <td class="text-end">{{ number_format((float)($row['platform_quantity'] ?? 0), 0, ',', '.') }}</td>
                        <td>{{ $row['sku'] ?? '-' }}</td>
                        <td style="min-width: 300px;"><strong>{{ $row['product_name'] ?? '-' }}</strong></td>
                        <td class="text-end">{{ number_format((float)($row['quantity'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['order_total_payment'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['order_total_payment_without_ppn'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['price'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['pricelist_total'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['total_order_value_from_products'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['proportion_percent'] ?? 0), 2, ',', '.') }}%</td>
                        <td class="text-end">{{ number_format((float)($row['payment_per_product_per_pcs'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['payment_per_product_without_ppn'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float)($row['unit_cost'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $profitPerPcs < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($profitPerPcs, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ ((float)($row['gross_profit_total'] ?? 0)) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)($row['gross_profit_total'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ ((float)($row['margin_per_pcs'] ?? 0)) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)($row['margin_per_pcs'] ?? 0), 2, ',', '.') }}%</td>
                        <td class="text-end fw-bold {{ ((float)($row['margin_per_item'] ?? 0)) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)($row['margin_per_item'] ?? 0), 2, ',', '.') }}%</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-detail-row='@json($row)'>
                                <i class="bi bi-calculator"></i> Detail
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <td colspan="8"><strong>TOTAL ({{ number_format($summary['total_rows']) }} rows, {{ number_format($summary['total_products']) }} barang keluar)</strong></td>
                    <td class="text-end"><strong>{{ number_format($summary['total_quantity'], 0, ',', '.') }}</strong></td>
                    <td class="text-end"><strong>{{ $summary['total_revenue_formatted'] ?? number_format($summary['total_revenue'], 0, ',', '.') }}</strong></td>
                    <td class="text-end"><strong>{{ $summary['total_revenue_without_ppn_formatted'] ?? number_format($summary['total_revenue_without_ppn'], 0, ',', '.') }}</strong></td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end"><strong>{{ $summary['total_capital_formatted'] ?? number_format($summary['total_capital'], 0, ',', '.') }}</strong></td>
                    <td class="text-end">-</td>
                    <td class="text-end"><strong>{{ $summary['total_gross_profit_formatted'] ?? number_format($summary['total_gross_profit'], 0, ',', '.') }}</strong></td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted small">
            Menampilkan {{ $productRows->firstItem() ?? 0 }} - {{ $productRows->lastItem() ?? 0 }} dari {{ number_format($summary['total_rows']) }} data
        </div>
        <div>
            {{ $productRows->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    </div>
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        @if(request('page') && request('page') > 1)
            Tidak ada data pada halaman {{ request('page') }}.
        @else
            Tidak ada data yang tersedia untuk kriteria filter yang dipilih.
        @endif
    </div>
@endif

