<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Penjualan #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #333;
        }
        .invoice-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
        .invoice-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .company-info {
            margin-bottom: 10px;
        }
        .company-info h1 {
            margin: 0;
            font-size: 24px;
            color: #444;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-details-box {
            width: 48%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .invoice-details-box h4 {
            margin-top: 0;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            border-top: 2px solid #ddd;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #777;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-shopee {
            background-color: #FF6720;
        }
        .badge-tiktok {
            background-color: #000000;
        }
        .no-print {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .print-btn {
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-info">
                <h1>Nota Penjualan</h1>
                <p>PT YOUR COMPANY NAME</p>
            </div>
        </div>
        
        <div class="invoice-details">
            <div class="invoice-details-box">
                <h4>Informasi Pesanan</h4>
                <table style="border: none; width: 100%;">
                    <tr>
                        <td style="border: none; padding: 2px; width: 40%;">Platform</td>
                        <td style="border: none; padding: 2px;">:
                            @if($order->platform)
                                <span class="badge badge-{{ $order->platform->name }}">
                                    {{ $order->platform->name }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Nomor Order</td>
                        <td style="border: none; padding: 2px;">: <strong>{{ $order->order_number }}</strong></td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Tanggal</td>
                        <td style="border: none; padding: 2px;">: 
                            @if($order->tanggal) 
                                {{ \Carbon\Carbon::parse($order->tanggal)->format('d-m-Y') }}
                            @else 
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Hari</td>
                        <td style="border: none; padding: 2px;">: {{ $order->hari }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Status Hari</td>
                        <td style="border: none; padding: 2px;">: 
                            @if($order->status_hari)
                                @php
                                    $statuses = explode(',', $order->status_hari);
                                    $statuses = array_map('trim', $statuses);
                                @endphp
                                @foreach($statuses as $status)
                                    <span class="badge" style="background-color: #17a2b8; margin-right: 2px;">{{ $status }}</span>
                                @endforeach
                            @else
                                <span style="color: #999;">-</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="invoice-details-box">
                <h4>Informasi Pengiriman</h4>
                <table style="border: none; width: 100%;">
                    <tr>
                        <td style="border: none; padding: 2px; width: 40%;">No. Resi</td>
                        <td style="border: none; padding: 2px;">: 
                            @if($order->orderItems->first() && $order->orderItems->first()->tracking_number)
                                {{ $order->orderItems->first()->tracking_number }}
                            @else
                                <span style="color: #999;">Belum ada nomor resi</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px;">Status</td>
                        <td style="border: none; padding: 2px;">: 
                            @if($order->status == 'completed')
                                Selesai
                            @elseif($order->status == 'pending')
                                Tertunda
                            @elseif($order->status == 'canceled')
                                Dibatalkan
                            @else
                                {{ $order->status }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Produk</th>
                    <th style="width: 15%;">Variasi</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 15%;" class="text-end">Harga</th>
                    <th style="width: 15%;" class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @forelse($order->orderItems as $index => $item)
                    @php 
                        $subtotal = $item->price_after_discount * $item->quantity;
                        $total += $subtotal;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            @if($item->platformProduct)
                                {{ $item->platformProduct->platform_product_name }}
                            @else
                                <span style="color: #999;">Data produk tidak tersedia</span>
                            @endif
                        </td>
                        <td>
                            @if($item->platformProduct && $item->platformProduct->variant)
                                {{ $item->platformProduct->variant }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->price_after_discount, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada item</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>Nota ini sah dan diproses oleh komputer.<br>
            Silakan hubungi customer service kami jika terdapat pertanyaan tentang nota ini.</p>
            <p>Dicetak pada: {{ now()->format('d M Y H:i:s') }}</p>
        </div>
    </div>
    
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Cetak Nota</button>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto print when page loads
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html> 