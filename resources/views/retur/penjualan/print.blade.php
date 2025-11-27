<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Retur Penjualan Online #{{ $returPenjualan->kode_retur }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .invoice-info div {
            flex: 1;
        }
        
        .table-container {
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 60px;
            margin-bottom: 5px;
        }
        
        .dotted-line {
            border-bottom: 2px dotted #000;
            margin: 20px 0;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
            
            .invoice-container {
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="title">INVOICE RETUR PENJUALAN ONLINE</div>
            <div>{{ $returPenjualan->kode_retur }}</div>
        </div>
        
        @php
            $order = $returPenjualan->order;
            $platform = $order->platform->name ?? 'N/A';
            $orderNumber = $order->order_number ?? 'N/A';
            
            // Get tracking number from order items
            $trackingNumbers = $order->orderItems->pluck('tracking_number')->filter()->unique();
            $resi = $trackingNumbers->count() > 0 ? $trackingNumbers->implode(', ') : '-';
            
            $orderDate = $order->tanggal ? $order->tanggal->format('d-M-Y') : 'N/A';
            
            $totalQtyBox = 0;
            $totalQtyCarton = 0;
            $grandTotal = 0;
        @endphp
        
        <div class="invoice-info">
            <div>
                <strong>No. Order:</strong> {{ $orderNumber }}<br>
                <strong>No. Resi:</strong> {{ $resi }}<br>
                <strong>Platform:</strong> {{ $platform }}<br>
                <strong>Tanggal Order:</strong> {{ $orderDate }}
            </div>
            <div>
                <strong>Kode Retur:</strong> {{ $returPenjualan->kode_retur }}<br>
                <strong>Tanggal Retur:</strong> {{ $returPenjualan->tanggal_retur->format('d-M-Y') }}<br>
                <strong>Status:</strong> {{ strtoupper($returPenjualan->status) }}<br>
                <strong>Dibuat oleh:</strong> {{ $returPenjualan->user->name ?? 'N/A' }}
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Produk</th>
                        <th>Qty Box</th>
                        <th>Qty Carton</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returPenjualan->details as $index => $detail)
                    @php
                        // Use correct retur logic based on mapping quantity
                        if (!$detail->orderItem) {
                            $pricePerProduct = 0;
                        } else {
                            $orderItem = $detail->orderItem;
                            $platformProduct = $orderItem->platformProduct;
                            
                            if (!$platformProduct || !$platformProduct->mappingBarang) {
                                // If no mapping, use original price
                                $pricePerProduct = $orderItem->price_after_discount;
                            } else {
                                // Calculate total quantity in the package from mapping
                                $totalPackageQty = $platformProduct->mappingBarang
                                    ->where('is_active', true)
                                    ->sum('quantity');
                                
                                if ($totalPackageQty > 1) {
                                    // If package contains more than 1 item, divide the price
                                    $pricePerProduct = $orderItem->price_after_discount / $totalPackageQty;
                                } else {
                                    // If package contains only 1 item, use original price
                                    $pricePerProduct = $orderItem->price_after_discount;
                                }
                            }
                        }
                        
                        $totalPriceDetail = $pricePerProduct * $detail->qty;
                        
                        $totalQtyCarton += $detail->qty;
                        $grandTotal += $totalPriceDetail;
                        
                        $productName = $detail->product->name ?? 'N/A';
                        $kondisi = strtoupper($detail->kondisi);
                        $alasan = $detail->alasan ?? '-';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $productName }}</td>
                        <td class="text-center">0</td>
                        <td class="text-center">{{ number_format($detail->qty, 2) }}</td>
                        <td class="text-right">{{ number_format($pricePerProduct, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totalPriceDetail, 0, ',', '.') }}</td>
                        <td>RETUR {{ $kondisi }} - {{ $alasan }}</td>
                    </tr>
                    @endforeach
                    
                    <tr class="total-row">
                        <td colspan="2" class="text-center"><strong>Sub Total :</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalQtyBox, 0) }}</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalQtyCarton, 0) }}</strong></td>
                        <td></td>
                        <td class="text-right"><strong>{{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="dotted-line"></div>
        
        <div class="invoice-info">
            <div>
                <strong>Kode Retur:</strong> {{ $returPenjualan->kode_retur }}<br>
                <strong>Platform:</strong> {{ $platform }}<br>
                <strong>No. Order:</strong> {{ $orderNumber }}
            </div>
            <div>
                <strong>No. Resi:</strong> {{ $resi }}<br>
                <strong>Tanggal:</strong> {{ $returPenjualan->tanggal_retur->format('d-M-Y') }}<br>
            </div>
            <div>
                <strong>Total Produk:</strong> {{ $totalQtyCarton }}<br>
                <strong>Total Nilai:</strong> Rp {{ number_format($grandTotal, 0, ',', '.') }}
            </div>
        </div>
        
        @if($returPenjualan->catatan)
        <div style="margin-top: 15px;">
            <strong>Catatan:</strong><br>
            {{ $returPenjualan->catatan }}
        </div>
        @endif
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Dibuat Oleh</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Disetujui Oleh</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Diterima Oleh</div>
            </div>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html> 