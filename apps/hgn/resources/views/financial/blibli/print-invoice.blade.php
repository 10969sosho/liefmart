<!DOCTYPE html>
<html>
<head>
    <title>Invoice Blibli</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .invoice {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            width: 100%;
            justify-content: center;
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
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
        }
        .text-right {
            text-align: right;
        }
        .summary {
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice">
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

        <div class="invoice-info">
            <table>
                <tr>
                    <td><strong>No. Invoice:</strong></td>
                    <td>{{ $transaction->no_invoice }}</td>
                    <td><strong>Tanggal:</strong></td>
                    <td>
                        @if(is_string($transaction->tanggal_masuk_pembayaran))
                            {{ \Carbon\Carbon::parse($transaction->tanggal_masuk_pembayaran)->format('d-m-Y') }}
                        @else
                            {{ $transaction->tanggal_masuk_pembayaran ? $transaction->tanggal_masuk_pembayaran->format('d-m-Y') : '-' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>No. Order:</strong></td>
                    <td>{{ $transaction->no_order }}</td>
                    <td><strong>Tanggal Order:</strong></td>
                    <td>
                        @if(is_string($transaction->tanggal_order))
                            {{ \Carbon\Carbon::parse($transaction->tanggal_order)->format('d-m-Y') }}
                        @else
                            {{ $transaction->tanggal_order ? $transaction->tanggal_order->format('d-m-Y') : '-' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>STATUS PAJAK</strong></td>
                    <td><span style="font-weight: bold;">{{ $isPKP ? 'PKP' : 'NON PKP' }}</span></td>
                    <td><strong>MARKETPLACE</strong></td>
                    <td><span style="font-weight: bold;">BLIBLI</span></td>
                </tr>
                <tr>
                    <td><strong>STATUS</strong></td>
                    <td>
                        @if($transaction->outstanding <= 0)
                            <span style="color: green; font-weight: bold;">LUNAS</span>
                        @else
                            <span style="color: red; font-weight: bold;">BELUM LUNAS</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="order-details">
            <h3>Detail Order</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $isOffline = str_contains($transaction->no_invoice, '/BLBNSD-COS/') || 
                                    str_contains($transaction->no_invoice, '/BLBNSD-SKN/');
                        $decimalPlaces = $isOffline ? 3 : 2;
                    @endphp
                    @if($transaction->order && $transaction->order->orderItems)
                        @foreach($transaction->order->orderItems as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->platformProduct->name ?? 'Produk Blibli' }}</td>
                                <td class="text-right">{{ number_format($item->price_after_discount ?? 0, $decimalPlaces) }}</td>
                                <td>{{ $item->quantity ?? 1 }}</td>
                                <td class="text-right">{{ number_format(($item->price_after_discount ?? 0) * ($item->quantity ?? 1), $decimalPlaces) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>1</td>
                            <td>Produk Blibli</td>
                            <td class="text-right">{{ number_format($transaction->nominal_harga ?? 0, $decimalPlaces) }}</td>
                            <td>1</td>
                            <td class="text-right">{{ number_format($transaction->nominal_harga ?? 0, $decimalPlaces) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="summary">
            <table class="table">
                <tr>
                    <td><strong>Nominal Harga:</strong></td>
                    <td class="text-right">{{ number_format($transaction->nominal_harga, $decimalPlaces) }}</td>
                </tr>
                <tr>
                    <td><strong>Biaya Admin:</strong></td>
                    <td class="text-right">{{ number_format($transaction->nominal_diskon1, $decimalPlaces) }}</td>
                </tr>
                <tr>
                    <td><strong>Biaya Layanan:</strong></td>
                    <td class="text-right">{{ number_format($transaction->nominal_diskon2, $decimalPlaces) }}</td>
                </tr>
                @if($transaction->nominal_diskon3 != 0)
                    <tr>
                        <td><strong>Diskon 3:</strong></td>
                        <td class="text-right">{{ number_format($transaction->nominal_diskon3, $decimalPlaces) }}</td>
                    </tr>
                @endif
                @if($transaction->nominal_diskon4 != 0)
                    <tr>
                        <td><strong>Diskon 4:</strong></td>
                        <td class="text-right">{{ number_format($transaction->nominal_diskon4, $decimalPlaces) }}</td>
                    </tr>
                @endif
                @if($transaction->nominal_diskon5 != 0)
                    <tr>
                        <td><strong>Diskon 5:</strong></td>
                        <td class="text-right">{{ number_format($transaction->nominal_diskon5, $decimalPlaces) }}</td>
                    </tr>
                @endif
                @if($transaction->nominal_diskon6 != 0)
                    <tr>
                        <td><strong>Diskon 6:</strong></td>
                        <td class="text-right">{{ number_format($transaction->nominal_diskon6, $decimalPlaces) }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Adjustment:</strong></td>
                    <td class="text-right">{{ number_format($transaction->adjustment, $decimalPlaces) }}</td>
                </tr>
                <tr>
                    <td><strong>Nominal Fix:</strong></td>
                    <td class="text-right">{{ number_format($transaction->nominal_fix, $decimalPlaces) }}</td>
                </tr>
                <tr>
                    <td><strong>Saldo Masuk:</strong></td>
                    <td class="text-right">{{ number_format($transaction->saldo_masuk, $decimalPlaces) }}</td>
                </tr>
                <tr>
                    <td><strong>Outstanding:</strong></td>
                    <td class="text-right">{{ number_format($transaction->outstanding, $decimalPlaces) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Terima kasih atas kerjasamanya.</p>
            
            <div class="payment-info" style="margin-top: 20px; margin-bottom: 20px;">
                <p><strong>Pembayaran transfer ke rekening :</strong><br>
                @php
                    $bankInfo = \App\Models\BlibliFinancialTransaction::getBankAccountInfo();
                @endphp
                {{ $bankInfo['bank_name'] }} {{ $bankInfo['account_number'] }} atas nama {{ $bankInfo['account_name'] }}
                </p>
            </div>
            
            <p>Hormat kami,</p>
            <br><br>
            <p>_______________________</p>
            <p>PT. HARVEST GLOBAL NIAGA</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
    </div>
</body>
</html> 