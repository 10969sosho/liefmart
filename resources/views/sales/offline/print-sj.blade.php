<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan #{{ $offlineSale->surat_jalan_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.2;
            color: #333;
        }
        .sj-container {
            max-width: 800px;
            margin: 10px auto;
            padding: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .logo {
            width: 600px;
            height: auto;
            margin-bottom: 5px;
        }
        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            width: 100%;
        }
        .company-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
        }
        .horizontal-line {
            border-top: 1px solid #000;
            margin: 5px 0;
        }
        .sj-title {
            text-align: center;
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .sj-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .sj-info-box {
            width: 45%;
        }
        .sj-details h2, .customer-details h2 {
            font-size: 12px;
            margin: 0 0 5px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            font-weight: bold;
        }
        .content-area {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 3px 4px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        .description-cell {
            text-align: left;
        }
        .description-cell:empty::after,
        .description-cell:has(.empty-dash) {
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .notes {
            margin-top: 15px;
        }
        .notes h2 {
            font-size: 12px;
            margin: 0 0 5px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            font-weight: bold;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 10px;
        }
        .signature-box {
            width: 30%;
            text-align: center;
        }
        .signature-line {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 9px;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            color: #777;
            font-size: 9px;
        }
        .no-print {
            display: block;
        }
        .action-buttons {
            text-align: right;
            margin-bottom: 10px;
        }
        .btn {
            padding: 10px 20px;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-success {
            background-color: #4CAF50;
        }
        .btn-primary {
            background-color: #2196F3;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .btn svg {
            margin-right: 5px;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .sj-container {
                max-width: 100%;
                margin: 0;
                padding: 15px;
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="sj-container">
        <div class="no-print action-buttons">
            <button onclick="printPDF();" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M14 3H2a2 2 0 0 0-2 2v5h3v3h10v-3h3V5a2 2 0 0 0-2-2zm-9 9v-3h6v3H5zm9-4.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
                    <path d="M3 6.5h10.5a.5.5 0 0 1 0 1H3a.5.5 0 0 1 0-1z"/>
                </svg>
                Cetak PDF
            </button>
            <button onclick="printSuratJalan();" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                </svg>
                Cetak Langsung
            </button>
            <button onclick="window.close();" class="btn btn-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
                Tutup
            </button>
        </div>
        
        @php
            // Determine logo based on tax ID from the first item
            $logoFile = 'HGN.jpeg'; // Default logo
            $firstItem = $offlineSale->items->first();
            
            if ($firstItem) {
                // Get tax_id from warehouse stocks through barang keluar
                foreach ($firstItem->barangKeluar as $barangKeluar) {
                    if ($barangKeluar->warehouseStock && $barangKeluar->warehouseStock->tax_id) {
                        $taxId = $barangKeluar->warehouseStock->tax_id;
                        
                        if ($taxId == 3) {
                            // HGN = PKP = PT. HARVEST GLOBAL NIAGA
                            $logoFile = 'HGN.jpeg';
                        } elseif ($taxId == 4) {
                            // LM = Non-PKP = LaMOURAD
                            $logoFile = 'LM.jpeg';
                        } else {
                            // Default untuk tax ID lainnya
                            $isPKP = in_array($taxId, [3, 5, 7]);
                            $logoFile = $isPKP ? 'HGN.jpeg' : 'LM.jpeg';
                        }
                        break; // Use the first tax_id found
                    }
                }
            }
        @endphp

        <div class="header">
            <div class="company-info">
                <img src="{{ asset('images/INV/' . $logoFile) }}" alt="Logo" class="logo">
            </div>
        </div>
        
        <div class="horizontal-line"></div>
        
        <div class="sj-title">SURAT JALAN</div>
        
        <div class="sj-info">
            <div class="sj-info-box sj-details">
                <h2>Informasi Pengiriman</h2>
                <p><strong>No. Surat Jalan:</strong> {{ $offlineSale->surat_jalan_number }}</p>
                <p><strong>No. PO:</strong> {{ $offlineSale->No_PO ?? '-' }}</p>
                <p><strong>Tanggal:</strong> {{ $offlineSale->sale_date->format('d/m/Y') }}</p>
            </div>
            
            <div class="sj-info-box customer-details">
                <h2>Informasi Penerima</h2>
                <p><strong>Nama:</strong> {{ $offlineSale->customer_name }}</p>
                <p><strong>Alamat:</strong> {{ $offlineSale->customerInfo->address ?? 'Alamat tidak tersedia' }}</p>
                <p><strong>Telepon:</strong> {{ $offlineSale->customerInfo->phone ?? 'No. Telepon tidak tersedia' }}</p>
            </div>
        </div>
        
        <div class="horizontal-line"></div>
        
        <div class="content-area">
        <table>
            <thead>
                <tr>
                    <th class="text-center" width="40">No.</th>
                    <th class="text-center" width="100">Barcode</th>
                    <th class="text-center">Nama Barang</th>
                    <th class="text-center" width="60">Jumlah</th>
                    <th class="text-center" width="80">Satuan</th>
                    <th class="text-center" width="120">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($offlineSale->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item->product->barcode ?? '-' }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">{{ $item->product->satuan->name ?? 'Pcs' }}</td>
                    <td class="description-cell">
                        @if($item->notes && trim($item->notes) !== '')
                            {{ $item->notes }}
                        @else
                            <span class="empty-dash">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right"><strong>Total Barang:</strong></td>
                    <td class="text-center"><strong>{{ $offlineSale->items->sum('quantity') }} pcs</strong></td>
                </tr>
            </tfoot>
        </table>
        </div>
        
        <div class="horizontal-line"></div>
        
        @if ($offlineSale->notes)
        <div class="notes">
            <h2>Catatan</h2>
            <p>{{ $offlineSale->notes }}</p>
        </div>
        @endif
        
        <div class="signatures">
            <div class="signature-box">
                <p>Pengirim</p>
                <div class="signature-line">
                    (.................................)
                </div>
            </div>
            
            <div class="signature-box">
                <p>Pengangkut</p>
                <div class="signature-line">
                    (.................................)
                </div>
            </div>
            
            <div class="signature-box">
                <p>Penerima</p>
                <div class="signature-line">
                    (.................................)
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Barang yang sudah diterima tidak dapat dikembalikan kecuali ada perjanjian tertulis.</p>
            <p>Harap periksa barang sebelum menandatangani tanda terima.</p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Function to print surat jalan
        function printSuratJalan() {
            window.print();
        }
        
        // Function to generate PDF
        function printPDF() {
            // Options for PDF
            const options = {
                margin: 10,
                filename: 'Surat_Jalan_{{ $offlineSale->surat_jalan_number }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Use HTML to PDF library
            const element = document.body;
            html2pdf().from(element).set(options).save();
        }
    </script>
</body>
</html> 