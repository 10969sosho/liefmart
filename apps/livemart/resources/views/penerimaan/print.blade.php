<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Penerimaan Barang - {{ $penerimaan->kode_penerimaan }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
        }

        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            font-size: 11px;
        }

        .header-info div {
            flex: 1;
        }

        .header-info .center {
            text-align: center;
        }

        .header-info .right {
            text-align: right;
        }

        .goods-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .goods-table th,
        .goods-table td {
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: center;
            font-size: 11px;
        }

        .goods-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .goods-table td.left {
            text-align: left;
        }

        .goods-table td.right {
            text-align: right;
        }

        .total-row {
            border-top: 2px solid #000;
            font-weight: bold;
        }

        .summary-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .summary-box {
            border: 1px solid #000;
            padding: 10px;
            width: 45%;
        }

        .summary-box h4 {
            font-size: 12px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: bold;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .footer-info {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin: 40px 0 5px 0;
            height: 1px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .container {
                max-width: none;
                margin: 0;
                padding: 10mm;
            }
            
            @page {
                margin: 0;
                size: A4;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Surat Penerimaan Barang</h1>
        </div>

        <!-- Header Information -->
        <div class="header-info">
            <div>
                <strong>No.Inv :</strong> {{ $penerimaan->kode_penerimaan }}<br>
                <strong>No.Container:</strong> {{ $penerimaan->nomor_po }}
            </div>
            <div class="center">
                <strong>Tgl ETA :</strong> {{ $penerimaan->tanggal_penerimaan->format('d-M-Y') }}<br>
                <strong>Tgl ETD :</strong> {{ $penerimaan->tanggal_penerimaan->format('d-M-Y') }}
            </div>
            <div class="right">
                <strong>Vessel :</strong> {{ $penerimaan->mainCategory->name ?? 'N/A' }}<br>
                <strong>Keterangan :</strong> {{ $penerimaan->catatan ?? '-' }}
            </div>
        </div>

        <!-- Goods Table -->
        <table class="goods-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%;">Nama Produk</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 10%;">Satuan</th>
                    <th style="width: 15%;">Harga HPP</th>
                    <th style="width: 10%;">Diskon</th>
                    <th style="width: 15%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalQty = 0;
                    $grandTotal = 0;
                @endphp
                @foreach($penerimaan->details as $index => $detail)
                    @php
                        // Calculate total discount for this item
                        $totalDiskonNominal = 
                            ($detail->diskon_nominal_1 ?? 0) + 
                            ($detail->diskon_nominal_2 ?? 0) + 
                            ($detail->diskon_nominal_3 ?? 0) + 
                            ($detail->diskon_nominal_4 ?? 0) + 
                            ($detail->diskon_nominal_5 ?? 0);
                        
                        $totalDiskonPersen = 
                            ($detail->diskon_persen_1 ?? 0) + 
                            ($detail->diskon_persen_2 ?? 0) + 
                            ($detail->diskon_persen_3 ?? 0) + 
                            ($detail->diskon_persen_4 ?? 0) + 
                            ($detail->diskon_persen_5 ?? 0);
                        
                        // Add to totals
                        $totalQty += $detail->qty;
                        $grandTotal += $detail->subtotal;
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>{{ $detail->product->name ?? 'N/A' }}</td>
                        <td style="text-align: center;">{{ number_format($detail->qty, 0, ',', '.') }}</td>
                        <td style="text-align: center;">{{ $detail->satuan->name ?? 'N/A' }}</td>
                        <td style="text-align: right;">Rp {{ number_format($detail->harga_hpp, 2, ',', '.') }}</td>
                        <td style="text-align: right;">
                            @if($totalDiskonNominal > 0)
                                Rp {{ number_format($totalDiskonNominal, 0, ',', '.') }}
                            @elseif($totalDiskonPersen > 0)
                                {{ number_format($totalDiskonPersen, 2) }}%
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: right;">Rp {{ number_format($detail->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                
                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="2" style="text-align: right; font-weight: bold;">TOTAL</td>
                    <td style="font-weight: bold; text-align: center;">{{ number_format($totalQty, 0, ',', '.') }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="font-weight: bold; text-align: right;">Rp {{ number_format(round($grandTotal), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-box">
                <h4>Informasi Penerimaan</h4>
                <div class="summary-item">
                    <span>Kategori:</span>
                    <span>{{ $penerimaan->mainCategory->name ?? 'N/A' }}</span>
                </div>
                <div class="summary-item">
                    <span>Metode Pembayaran:</span>
                    <span>{{ $penerimaan->metode_pembayaran }}</span>
                </div>
                @if($penerimaan->metode_pembayaran == 'Jatuh Tempo' && $penerimaan->tanggal_jatuh_tempo)
                <div class="summary-item">
                    <span>Jatuh Tempo:</span>
                    <span>{{ $penerimaan->tanggal_jatuh_tempo->format('d-M-Y') }}</span>
                </div>
                @endif
                <div class="summary-item">
                    <span>Status:</span>
                    <span>{{ $penerimaan->status }}</span>
                </div>
            </div>

            <div class="summary-box">
                <h4>Ringkasan Nilai</h4>
                <div class="summary-item">
                    <span>Total Item:</span>
                    <span>{{ $penerimaan->details->count() }} item</span>
                </div>
                <div class="summary-item">
                    <span>Total Qty:</span>
                    <span>{{ number_format($totalQty, 0, ',', '.') }}</span>
                </div>
                @php
                    $totalDiskonKeseluruhan = 0;
                    foreach($penerimaan->details as $detail) {
                        $totalDiskonKeseluruhan += 
                            ($detail->diskon_nominal_1 ?? 0) + 
                            ($detail->diskon_nominal_2 ?? 0) + 
                            ($detail->diskon_nominal_3 ?? 0) + 
                            ($detail->diskon_nominal_4 ?? 0) + 
                            ($detail->diskon_nominal_5 ?? 0);
                    }
                @endphp
                @if($totalDiskonKeseluruhan > 0)
                <div class="summary-item">
                    <span>Total Diskon:</span>
                    <span>Rp {{ number_format($totalDiskonKeseluruhan, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="summary-item">
                    <span>Total Nilai:</span>
                    <span>Rp {{ number_format(round($grandTotal), 0, ',', '.') }}</span>
                </div>
                @if($penerimaan->taxCategory)
                <div class="summary-item">
                    <span>Kategori Pajak:</span>
                    <span>{{ $penerimaan->taxCategory->name }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div>Dibuat Oleh</div>
                <div class="signature-line"></div>
                <div>Admin</div>
            </div>
            <div class="signature-box">
                <div>Diperiksa Oleh</div>
                <div class="signature-line"></div>
                <div>Supervisor</div>
            </div>
            <div class="signature-box">
                <div>Disetujui Oleh</div>
                <div class="signature-line"></div>
                <div>Manager</div>
            </div>
        </div>

        <!-- Footer Information -->
        <div class="footer-info">
            <p>Dokumen ini dicetak pada {{ now()->format('d-M-Y H:i:s') }}</p>
            <p>Sistem Manajemen Gudang - {{ config('app.name', 'Dashboard App') }}</p>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html> 