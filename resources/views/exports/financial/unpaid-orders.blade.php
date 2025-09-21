<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Order Belum Ada Pembayaran</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .summary-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }
        .main-table th,
        .main-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }
        .main-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .platform-badge {
            background-color: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .status-completed {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .age-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .age-0-7 { background-color: #28a745; color: white; }
        .age-8-14 { background-color: #17a2b8; color: white; }
        .age-15-21 { background-color: #ffc107; color: black; }
        .age-22-30 { background-color: #fd7e14; color: white; }
        .age-30-plus { background-color: #dc3545; color: white; }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Data Order Belum Ada Pembayaran</h1>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Summary Section -->
    <div class="summary">
        <h3>Ringkasan</h3>
        <table class="summary-table">
            <tr>
                <th>Total Order</th>
                <th>Total Nilai</th>
                <th>Platform Terlibat</th>
                <th>Order 30+ Hari</th>
            </tr>
            <tr>
                <td class="text-center">{{ number_format($summary['total_orders']) }}</td>
                <td class="text-right">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</td>
                <td class="text-center">{{ count($summary['platform_breakdown']) }}</td>
                <td class="text-center">{{ $summary['age_breakdown']['30+_days'] ?? 0 }}</td>
            </tr>
        </table>

        <!-- Platform Breakdown -->
        <h4>Breakdown per Platform</h4>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Jumlah Order</th>
                    <th>Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['platform_breakdown'] as $platform => $data)
                <tr>
                    <td>{{ $platform }}</td>
                    <td class="text-center">{{ number_format($data['count']) }}</td>
                    <td class="text-right">Rp {{ number_format($data['value'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Age Breakdown -->
        <h4>Breakdown per Usia Order</h4>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Usia Order</th>
                    <th>Jumlah Order</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>0-7 hari</td>
                    <td class="text-center">{{ $summary['age_breakdown']['0-7_days'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>8-14 hari</td>
                    <td class="text-center">{{ $summary['age_breakdown']['8-14_days'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>15-21 hari</td>
                    <td class="text-center">{{ $summary['age_breakdown']['15-21_days'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>22-30 hari</td>
                    <td class="text-center">{{ $summary['age_breakdown']['22-30_days'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td>30+ hari</td>
                    <td class="text-center"><strong>{{ $summary['age_breakdown']['30+_days'] ?? 0 }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Main Data Table -->
    <h3>Data Order Belum Ada Pembayaran</h3>
    <table class="main-table">
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Platform</th>
                <th>Tanggal Order</th>
                <th>Total Items</th>
                <th>Total Quantity</th>
                <th>Total Order Value</th>
                <th>Status</th>
                <th>Usia Order</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allOrders as $order)
                @php
                    $totalItems = $order->orderItems->count();
                    $totalQuantity = $order->orderItems->sum('quantity');
                    
                    // Calculate price from order_items table using price_after_discount column
                    // For returned orders, we need to calculate using original quantity (current qty + returned qty)
                    $itemsTotal = $order->orderItems->sum(function($item) {
                        $currentQty = $item->quantity;
                        
                        // Get returned quantity for this item
                        $returnedQty = \App\Models\ReturPenjualanDetail::where('order_item_id', $item->id)
                            ->whereHas('returPenjualan', function($q) { 
                                $q->whereIn('status', ['draft', 'selesai']); 
                            })
                            ->sum('qty');
                        
                        // Original quantity = current quantity + returned quantity
                        $originalQty = $currentQty + $returnedQty;
                        
                        return $item->price_after_discount * $originalQty;
                    });
                    
                    // Add shipping cost to get total order value
                    $totalValue = $itemsTotal + ($order->shipping_cost ?? 0);
                    $daysSinceOrder = $order->tanggal ? $order->tanggal->diffInDays(now()) : 0;
                    
                    // Check if this order has full return
                    $totalOrderQty = $order->orderItems->sum('quantity');
                    $totalReturQty = 0;
                    foreach($order->orderItems as $orderItem) {
                        $itemReturQty = \App\Models\ReturPenjualanDetail::where('order_item_id', $orderItem->id)
                            ->whereHas('returPenjualan', function($q) { 
                                $q->whereIn('status', ['draft', 'selesai']); 
                            })
                            ->sum('qty');
                        $totalReturQty += (float) ($itemReturQty ?? 0);
                    }
                    $isFullReturn = $totalReturQty >= $totalOrderQty && $totalReturQty > 0;
                @endphp
                <tr>
                    <td><strong>{{ $order->order_number }}</strong></td>
                    <td>
                        <span class="platform-badge">{{ $order->platform->name ?? 'Unknown' }}</span>
                    </td>
                    <td>{{ $order->tanggal ? $order->tanggal->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $totalItems }}</td>
                    <td class="text-center">{{ $totalQuantity }}</td>
                    <td class="text-right">
                        <strong>Rp {{ number_format($totalValue, 0, ',', '.') }}</strong>
                    </td>
                    <td>
                        @if($isFullReturn)
                            <span class="status-badge" style="background-color: #dc3545; color: white;">Retur</span>
                        @else
                            <span class="status-badge status-{{ $order->status == 'completed' ? 'completed' : 'pending' }}">
                                {{ $order->status ?? 'Belum Lunas' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($daysSinceOrder <= 7)
                            <span class="age-badge age-0-7">{{ $daysSinceOrder }} hari</span>
                        @elseif($daysSinceOrder <= 14)
                            <span class="age-badge age-8-14">{{ $daysSinceOrder }} hari</span>
                        @elseif($daysSinceOrder <= 21)
                            <span class="age-badge age-15-21">{{ $daysSinceOrder }} hari</span>
                        @elseif($daysSinceOrder <= 30)
                            <span class="age-badge age-22-30">{{ $daysSinceOrder }} hari</span>
                        @else
                            <span class="age-badge age-30-plus">{{ $daysSinceOrder }} hari</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($allOrders->count() == 0)
        <div style="text-align: center; margin-top: 50px; color: #666;">
            <p>Tidak ada order yang belum ada pembayaran</p>
            <p>Semua order sudah memiliki data pembayaran atau tidak ada order yang memenuhi kriteria filter.</p>
        </div>
    @endif
</body>
</html> 