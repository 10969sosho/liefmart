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
        }
        .sj-container {
            max-width: 800px;
            margin: 10px auto;
            padding: 10px;
            border: 2px solid #FF9800;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .sj-header {
            text-align: center;
            margin-bottom: 15px;
            color: #FF9800;
        }
        .sj-header h1 {
            margin: 0;
            font-size: 18px;
        }
        .company-details {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #FF9800;
            padding-bottom: 10px;
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
            color: #FF9800;
        }
        .content-area {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
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
            background-color: #FFF3E0;
            color: #E65100;
        }
        .text-right {
            text-align: right;
        }
        .notes {
            margin-top: 15px;
            padding: 10px;
            background-color: #FFF3E0;
            border-left: 4px solid #FF9800;
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
            border-top: 1px solid #FF9800;
            padding-top: 2px;
            font-size: 9px;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            color: #777;
            font-size: 9px;
            border-top: 1px solid #FF9800;
            padding-top: 10px;
        }
        .warning-box {
            background-color: #FFF3E0;
            border: 1px solid #FF9800;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="sj-container">
        <div class="sj-header">
            <h1>SURAT JALAN PRODUK KONSUMSI (NON-PKP)</h1>
            <p>No. SJ: {{ $offlineSale->surat_jalan_number }}</p>
        </div>
        
        <div class="company-details">
            <h2>PT. PAK RUDI</h2>
            <p>Jl. Contoh No. 123, Jakarta</p>
            <p>Telp: (021) 123-4567 | Email: info@pakrudi.com</p>
        </div>
        
        <div class="sj-info">
            <div class="sj-info-box sj-details">
                <h2>Informasi Pengiriman</h2>
                <p><strong>No. PO:</strong> {{ $offlineSale->No_PO ?? '-' }}</p>
                <p><strong>Tanggal:</strong> {{ $offlineSale->sale_date->format('d/m/Y') }}</p>
                <p><strong>Jenis Barang:</strong> Produk Konsumsi (Non-PKP)</p>
            </div>
            
            <div class="sj-info-box customer-details">
                <h2>Informasi Penerima</h2>
                <p><strong>Nama:</strong> {{ $offlineSale->customer_name }}</p>
                <p><strong>Alamat:</strong> {{ $offlineSale->customerInfo->address ?? 'Alamat tidak tersedia' }}</p>
                <p><strong>Telepon:</strong> {{ $offlineSale->customerInfo->phone ?? 'No. Telepon tidak tersedia' }}</p>
            </div>
        </div>
        
        <div class="warning-box">
            <strong>PERHATIAN:</strong> Produk konsumsi memiliki masa kadaluarsa. Harap periksa tanggal kadaluarsa sebelum menerima barang.
        </div>
        
        <div class="content-area">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Barcode</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Tanggal Kadaluarsa</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($offlineSale->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->barcode ?? '-' }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->product->satuan->name ?? 'Pcs' }}</td>
                    <td>
                        @if($item->warehouseStock && $item->warehouseStock->expired_date)
                            {{ $item->warehouseStock->expired_date->format('d/m/Y') }}
                        @else
                            -
                        @endif
                    </td>
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
        </table>
        </div>
        
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
            <p>Harap periksa barang dan tanggal kadaluarsa sebelum menandatangani tanda terima.</p>
        </div>
        
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="printPDF();" style="padding: 10px 20px; background-color: #FF9800; color: white; border: none; cursor: pointer; border-radius: 4px;">
                Cetak PDF
            </button>
            <button onclick="printSuratJalan();" style="padding: 10px 20px; background-color: #FF9800; color: white; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">
                Cetak Langsung
            </button>
            <button onclick="window.close();" style="padding: 10px 20px; background-color: #f44336; color: white; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">
                Tutup
            </button>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function printSuratJalan() {
            window.print();
        }
        
        function printPDF() {
            document.querySelector('.no-print').style.display = 'none';
            
            const options = {
                margin: 10,
                filename: 'Surat_Jalan_{{ $offlineSale->surat_jalan_number }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            const element = document.body;
            html2pdf().from(element).set(options).save().then(function() {
                document.querySelector('.no-print').style.display = 'block';
            });
        }
    </script>
</body>
</html> 