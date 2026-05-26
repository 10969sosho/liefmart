<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $transaction->no_invoice }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            width: 300px;
            height: auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .company-info {
            display: flex;
            align-items: center;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-left: 15px;
        }
        
        .company-website {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            text-align: center;
        }
        
        .horizontal-line {
            border-top: 1px solid #000;
            margin: 10px 0;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        
        .customer-info {
            width: 50%;
        }
        
        .invoice-info {
            width: 40%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        thead th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .barcode {
            font-family: "Courier New", monospace;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        
        .grand-total {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        
        .signature {
            width: 30%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            margin-bottom: 10px;
        }
        
        .payment-info {
            margin-top: 30px;
            font-style: italic;
        }
        
        /* Print styles */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .invoice-container {
                max-width: 100%;
                margin: 0;
                padding: 15px;
                border: none;
                box-shadow: none;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="no-print" style="text-align: right; margin-bottom: 10px;">
            <button onclick="window.print();" style="background: #008000; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" style="vertical-align: middle; margin-right: 5px;" viewBox="0 0 16 16">
                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                </svg>
                Cetak Invoice
            </button>
        </div>
        
        <div class="header">
            <div class="company-info" style="width: 100%; display: flex; justify-content: center; align-items: center;">
                <img src="{{ asset('images/INV/' . ($logoFile ?? 'HGN.jpeg')) }}" alt="Logo" class="logo" style="width: 500px; height: auto;">
            </div>
        </div>
        
        <div class="horizontal-line"></div>
        
        <div class="invoice-details">
            <div class="invoice-info">
                <div><strong>TANGGAL INVOICE</strong> : {{ $transaction->tanggal_order ? $transaction->tanggal_order->format('d F Y') : '-' }}</div>
                <div><strong>NO. INVOICE</strong> : {{ $transaction->no_invoice }}</div>
                <div><strong>NO. ORDER</strong> : {{ $transaction->no_order }}</div>
                <div><strong>STATUS PAJAK</strong> : <span style="font-weight: bold;">{{ $isPKP ? 'PKP' : 'NON PKP' }}</span></div>
                <div><strong>STATUS</strong> : 
                    @if($transaction->outstanding <= 0)
                        <span style="color: green; font-weight: bold;">LUNAS</span>
                    @else
                        <span style="color: red; font-weight: bold;">BELUM LUNAS</span>
                    @endif
                </div>
            </div>
            
            <div class="customer-info">
                <div><strong>MARKETPLACE</strong> : SHOPEE</div>
                <div><strong>TANGGAL PEMBAYARAN</strong> : {{ $transaction->tanggal_masuk_pembayaran ? $transaction->tanggal_masuk_pembayaran->format('d F Y') : '-' }}</div>
            </div>
        </div>
        
        <div class="horizontal-line"></div>
        
        <table>
            <thead>
                <tr>
                    <th width="40" class="text-center">NO</th>
                    <th class="text-center">NAMA PRODUK</th>
                    <th width="100" class="text-center">HARGA (RP)</th>
                    <th width="100" class="text-center">TOTAL (RP)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>{{ $productName ?? 'Produk Shopee' }}</td>
                    <td class="text-right">{{ number_format($transaction->nominal_harga, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($transaction->nominal_harga, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="horizontal-line"></div>
        
        <div class="totals">
            <div class="total-row">
                <div><strong>SUBTOTAL :</strong></div>
                <div>{{ number_format($transaction->nominal_harga, 0, ',', '.') }}</div>
            </div>
            
            @if($transaction->nominal_diskon1 != 0)
            <div class="total-row">
                <div>Voucher Ditanggung Penjual:</div>
                <div>{{ number_format($transaction->nominal_diskon1, 0, ',', '.') }}</div>
            </div>
            @endif
            @if($transaction->nominal_diskon2 != 0)
            <div class="total-row">
                <div>Komisi AMS/Affiliate:</div>
                <div>{{ number_format($transaction->nominal_diskon2, 0, ',', '.') }}</div>
            </div>
            @endif
            @if($transaction->nominal_diskon3 != 0)
            <div class="total-row">
                <div>Biaya Admin:</div>
                <div>{{ number_format($transaction->nominal_diskon3, 0, ',', '.') }}</div>
            </div>
            @endif
            @if($transaction->nominal_diskon4 != 0)
            <div class="total-row">
                <div>Biaya Layanan:</div>
                <div>{{ number_format($transaction->nominal_diskon4, 0, ',', '.') }}</div>
            </div>
            @endif
            @if($transaction->nominal_diskon5 != 0)
            <div class="total-row">
                <div>Diskon 5:</div>
                <div>{{ number_format($transaction->nominal_diskon5, 0, ',', '.') }}</div>
            </div>
            @endif
            @if($transaction->nominal_diskon6 != 0)
            <div class="total-row">
                <div>Diskon 6:</div>
                <div>{{ number_format($transaction->nominal_diskon6, 0, ',', '.') }}</div>
            </div>
            @endif
            @if($transaction->adjustment != 0)
            <div class="total-row">
                <div>Adjustment:</div>
                <div>{{ number_format($transaction->adjustment, 0, ',', '.') }}</div>
            </div>
            @endif
            
            <div class="total-row grand-total">
                <div><strong>NOMINAL FIX :</strong></div>
                <div><strong>{{ number_format($transaction->nominal_fix, 0, ',', '.') }}</strong></div>
            </div>
            
            <div class="total-row">
                <div>Dibayar:</div>
                <div>{{ number_format($transaction->saldo_masuk, 0, ',', '.') }}</div>
            </div>
            
            <div class="total-row grand-total">
                <div><strong>SISA :</strong></div>
                <div><strong>{{ number_format($transaction->outstanding, 0, ',', '.') }}</strong></div>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <div style="text-align: right;"><strong>TOTAL Rp. </strong> <span style="border: 1px solid #000; padding: 5px 10px;">{{ number_format($transaction->nominal_fix, 0, ',', '.') }}</span></div>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <div>Tanda Terima,</div>
                <div class="signature-line"></div>
                <div>( )</div>
            </div>
            
            <div class="signature" style="text-align: right;">
                <div>Hormat Kami,</div>
                <div class="signature-line"></div>
                <div>( PT. {{ $isPKP ? 'HARVEST GLOBAL NIAGA' : 'LUMBUNG MASYARAKAT' }} )</div>
            </div>
        </div>
        
        <div class="payment-info">
            <p>Pembayaran transfer ke rekening :<br>
            @php
                $bankInfo = \App\Models\ShopeeFinancialTransaction::getBankAccountInfo();
            @endphp
            {{ $bankInfo['bank_name'] }} {{ $bankInfo['account_number'] }} atas nama {{ $bankInfo['account_name'] }}
            </p>
            <p>Terima kasih atas kepercayaan Anda berbelanja di platform Shopee kami.</p>
        </div>
    </div>
</body>
</html> 