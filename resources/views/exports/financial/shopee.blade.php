<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shopee Finance Analytics Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 10px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 3px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .number {
            text-align: right;
        }
        
        .center {
            text-align: center;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 10px;
        }
        
        .total-row {
            background-color: #e0e0e0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SHOPEE FINANCE ANALYTICS EXPORT</h1>
        <p>Tanggal Export: {{ date('d/m/Y H:i:s') }}</p>
        <p>Total Records: {{ $transactions->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 8%">Tanggal Order</th>
                <th style="width: 10%">No Order</th>
                <th style="width: 12%">No Invoice</th>
                <th style="width: 5%">Status Pajak</th>
                <th style="width: 4%">QTY</th>
                <th style="width: 7%">HPP (Rp)</th>
                <th style="width: 5%">Voucher (Rp)</th>
                <th style="width: 5%">Komisi (Rp)</th>
                <th style="width: 5%">Biaya Admin (Rp)</th>
                <th style="width: 5%">Biaya Layanan (Rp)</th>
                <th style="width: 5%">Biaya 5 (Rp)</th>
                <th style="width: 5%">Biaya 6 (Rp)</th>
                <th style="width: 5%">Biaya 7 (Rp)</th>
                <th style="width: 5%">Biaya 8 (Rp)</th>
                <th style="width: 5%">Biaya 9 (Rp)</th>
                <th style="width: 5%">Biaya 10 (Rp)</th>
                <th style="width: 5%">Biaya 11 (Rp)</th>
                <th style="width: 5%">Biaya 12 (Rp)</th>
                <th style="width: 5%">Adjustment (Rp)</th>
                <th style="width: 10%">Keterangan Adjustment</th>
                <th style="width: 7%">Nominal Fix (Rp)</th>
                <th style="width: 7%">Saldo Masuk (Rp)</th>
                <th style="width: 8%">Tanggal Bayar</th>
                <th style="width: 7%">Outstanding (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty = 0;
                $totalNominalHarga = 0;
                $totalNominalFix = 0;
                $totalSaldoMasuk = 0;
                $totalOutstanding = 0;
            @endphp
            
            @foreach($transactions as $index => $transaction)
                @php
                    $totalQty += $transaction->qty ?? 0;
                    $totalNominalHarga += $transaction->nominal_harga;
                    $totalNominalFix += $transaction->nominal_fix;
                    $totalSaldoMasuk += $transaction->saldo_masuk;
                    $totalOutstanding += $transaction->outstanding;
                    
                    // Determine tax status based on invoice format
                    $taxStatus = '-';
                    if ($transaction->no_invoice) {
                        if (strpos($transaction->no_invoice, 'INV-PKP') !== false) {
                            $taxStatus = 'PKP';
                        } elseif (strpos($transaction->no_invoice, 'INV-NONPKP') !== false) {
                            $taxStatus = 'NON-PKP';
                        }
                    }
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($transaction->tanggal_order)->format('d/m/Y') }}</td>
                    <td>{{ $transaction->no_order }}</td>
                    <td>{{ $transaction->no_invoice }}</td>
                    <td class="center">{{ $taxStatus }}</td>
                    <td class="number">{{ $transaction->qty ?? 0 }}</td>
                    <td class="number">{{ number_format($transaction->nominal_harga, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon1, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon2, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon3, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon4, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon5, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon6, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon7, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon8, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon9, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon10, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon11, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->nominal_diskon12, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->adjustment, 0, ',', '.') }}</td>
                    <td>{{ $transaction->adjustment_description ?? '-' }}</td>
                    <td class="number">{{ number_format($transaction->nominal_fix, 0, ',', '.') }}</td>
                    <td class="number">{{ number_format($transaction->saldo_masuk, 0, ',', '.') }}</td>
                    <td class="center">{{ $transaction->tanggal_masuk_pembayaran ? \Carbon\Carbon::parse($transaction->tanggal_masuk_pembayaran)->format('d/m/Y') : '-' }}</td>
                    <td class="number">{{ number_format($transaction->outstanding, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            
            <!-- Total Row -->
            <tr class="total-row">
                <td colspan="6" class="center"><strong>TOTAL</strong></td>
                <td class="number"><strong>{{ number_format($totalNominalHarga, 0, ',', '.') }}</strong></td>
                <td colspan="5"></td>
                <td class="number"><strong>{{ number_format($totalNominalFix, 0, ',', '.') }}</strong></td>
                <td class="number"><strong>{{ number_format($totalSaldoMasuk, 0, ',', '.') }}</strong></td>
                <td></td>
                <td class="number"><strong>{{ number_format($totalOutstanding, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis pada {{ date('d/m/Y H:i:s') }}</p>
        <p><strong>Keterangan:</strong></p>
        <ul style="font-size: 8px; margin: 5px 0;">
            <li>Voucher: Voucher ditanggung penjual</li>
            <li>Komisi: Komisi AMS/Affiliate</li>
            <li>Biaya Admin: Biaya administrasi platform Shopee</li>
            <li>Biaya Layanan: Biaya layanan (gratis ongkir + cashback)</li>
            <li>Adjustment: Penyesuaian nilai transaksi</li>
        </ul>
    </div>
</body>
</html> 