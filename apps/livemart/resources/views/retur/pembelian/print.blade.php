<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Retur Pembelian #{{ $returPembelian->kode_retur }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        
        .invoice-container {
            max-width: 210mm; /* F4 width */
            min-height: 297mm; /* F4 height */
            margin: 0 auto;
            padding: 15mm;
            border: 1px solid #000;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            text-decoration: underline;
        }
        
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .invoice-info div {
            flex: 1;
        }
        
        .content-area {
            flex: 1;
        }
        
        .table-container {
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: left;
            font-size: 8px;
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
        
        .notes {
            margin-top: 20px;
            font-size: 10px;
        }
        
        .signature-section {
            margin-top: auto;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 40px;
            margin-bottom: 3px;
        }
        
        .dotted-line {
            border-top: 2px dotted #000;
            margin: 20px 0;
        }
        
        @media print {
            @page {
                size: F4;
                margin: 15mm;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            .invoice-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="title">Purchase Retur</div>
        </div>
        
        @php
            $penerimaan = $returPembelian->penerimaan;
            $nomorPO = $penerimaan->nomor_po ?? 'N/A';
            $tanggalPenerimaan = $penerimaan->tanggal_penerimaan ? $penerimaan->tanggal_penerimaan->format('d-M-Y') : 'N/A';
            $tanggalRetur = $returPembelian->tanggal_retur->format('d-M-Y');
            $kodeRetur = $returPembelian->kode_retur;
            
            // Get tax_category_id from penerimaan
            $taxId = $penerimaan->tax_category_id ?? null;
        @endphp
        
        <div class="content-area">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th style="width: 30%;">Nama Barang</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 8%;">Retur</th>
                        <th style="width: 8%;">Kirim</th>
                        <th style="width: 10%;">Harga</th>
                        <th style="width: 10%;">Diskon</th>
                        <th style="width: 12%;">Total Harga</th>
                        <th style="width: 15%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $counter = 1;
                        $totalQtyOriginal = 0;
                        $totalRetur = 0;
                        $totalKirim = 0;
                        $grandTotalTable = 0;
                    @endphp
                    
                    @foreach($returPembelian->details as $detail)
                        @php
                            $penerimaanDetail = $detail->penerimaanDetail;
                            // Calculate harga per unit after tiered discounts
                            $hargaHpp = 0;
                            if ($penerimaanDetail) {
                                if ($penerimaanDetail->qty > 0 && $penerimaanDetail->subtotal > 0) {
                                    $hargaHpp = $penerimaanDetail->subtotal / $penerimaanDetail->qty;
                                } else {
                                    // Fallback: calculate from harga_hpp with discounts
                                    $hargaHpp = $penerimaanDetail->harga_hpp;
                                    for ($i = 1; $i <= 5; $i++) {
                                        $diskonPersen = $penerimaanDetail->{"diskon_persen_$i"} ?? 0;
                                        if ($diskonPersen > 0) {
                                            $hargaHpp = $hargaHpp * (1 - $diskonPersen / 100);
                                        }
                                    }
                                    for ($i = 1; $i <= 5; $i++) {
                                        $diskonNominal = $penerimaanDetail->{"diskon_nominal_$i"} ?? 0;
                                        if ($diskonNominal > 0 && $penerimaanDetail->qty > 0) {
                                            $hargaHpp = $hargaHpp - ($diskonNominal / $penerimaanDetail->qty);
                                        }
                                    }
                                }
                            }
                            
                            // Get original qty from penerimaan detail
                            $originalQty = $penerimaanDetail ? $penerimaanDetail->qty : 0;
                            $returQty = $detail->qty;
                            $kirimQty = $originalQty - $returQty; // Qty yang tidak diretur (kirim)
                            
                            // Calculate total price for returned items
                            $totalPrice = $hargaHpp * $returQty;
                            
                            // Add to totals
                            $totalQtyOriginal += $originalQty;
                            $totalRetur += $returQty;
                            $totalKirim += $kirimQty;
                            $grandTotalTable += $totalPrice;
                        @endphp
                        
                        <tr>
                            <td class="text-center">{{ $counter++ }}</td>
                            <td>{{ $detail->product->name }}</td>
                            <td class="text-center">{{ number_format($originalQty, 0) }}</td>
                            <td class="text-center">{{ number_format($returQty, 0) }}</td>
                            <td class="text-center">{{ number_format($kirimQty, 0) }}</td>
                            <td class="text-right">{{ number_format($hargaHpp, 0, ',', '.') }}</td>
                            <td class="text-right">-</td>
                            <td class="text-right">{{ number_format($totalPrice, 0, ',', '.') }}</td>
                            <td>{{ $detail->alasan ?? '-' }}</td>
                        </tr>
                    @endforeach
                    
                    <tr class="total-row">
                        <td colspan="2" class="text-center"><strong>Total :</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalQtyOriginal, 0) }}</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalRetur, 0) }}</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalKirim, 0) }}</strong></td>
                        <td></td>
                        <td></td>
                        <td class="text-right"><strong>{{ number_format($grandTotalTable, 0, ',', '.') }}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
        
        @php
            // Calculate DPP, PPN, and Grand Total
            // grandTotalTable adalah total retur (DPP retur)
            $dpp = \App\Helpers\NumberFormatter::calculateDPP($grandTotalTable);
            $ppn = 0;
            $grandTotal = $dpp;
            
            if ($taxId == 3) {
                // PKP: Calculate PPN
                // DPP = grandTotalTable (total retur)
                // DPP 11/12 = DPP * (11/12)
                // PPN = DPP 11/12 * 12% = DPP * 0.11
                $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
                $ppn = \App\Helpers\NumberFormatter::calculatePPN($dpp11_12);
                $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($dpp, $ppn);
            } else {
                // Non-PKP: No PPN
                $dpp11_12 = 0;
                $ppn = 0;
                $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($dpp);
            }
            // grandTotal adalah nominal retur (pembayaran) = DPP + PPN
        @endphp
        
        <div class="table-container" style="margin-top: 10px;">
            <table style="width: 50%; margin-left: auto;">
                <tr>
                    <th style="width: 60%;">DPP (Dasar Pengenaan Pajak)</th>
                    <td class="text-right" style="width: 40%;"><strong>{{ number_format($dpp, 0, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <th>PPN (11%)</th>
                    <td class="text-right"><strong>{{ number_format($ppn, 0, ',', '.') }}</strong></td>
                </tr>
                <tr class="total-row">
                    <th>TOTAL (DPP + PPN)</th>
                    <td class="text-right"><strong>{{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="dotted-line"></div>
        
        <div class="invoice-info">
            <div>
                <strong>No.Retur :</strong> {{ $kodeRetur }}<br>
                <strong>No. PO :</strong> {{ $nomorPO }}<br>
                <strong>Tgl Input :</strong> {{ $tanggalPenerimaan }}<br>
                <strong>Tgl Cetak :</strong> {{ now()->format('d-M-Y') }}
            </div>
            <div>
                <strong>Kas / Kredit :</strong> KAS - BESOK LUNAS<br>
                <strong>Ref.Retur :</strong> {{ $kodeRetur }}<br>
            </div>
            <div>
                <strong>Sales :</strong> ADMIN<br>
                <strong>Status Bayar :</strong> LUNAS
            </div>
        </div>
        
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

