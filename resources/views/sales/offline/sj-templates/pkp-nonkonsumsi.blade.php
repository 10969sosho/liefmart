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
            font-size: 14px;
            line-height: 1.5;
        }
        .sj-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 2px solid #2196F3;
        }
        .sj-header {
            text-align: center;
            margin-bottom: 30px;
            color: #2196F3;
        }
        .sj-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .company-details {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2196F3;
            padding-bottom: 20px;
        }
        .sj-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .sj-info-box {
            width: 45%;
        }
        .sj-details h2, .customer-details h2 {
            font-size: 16px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            color: #2196F3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #E3F2FD;
            color: #1565C0;
        }
        .text-right {
            text-align: right;
        }
        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #E3F2FD;
            border-left: 4px solid #2196F3;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature-box {
            width: 30%;
            text-align: center;
        }
        .signature-line {
            margin-top: 70px;
            border-top: 1px solid #2196F3;
            padding-top: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            font-size: 12px;
            border-top: 1px solid #2196F3;
            padding-top: 20px;
        }
        .warning-box {
            background-color: #E3F2FD;
            border: 1px solid #2196F3;
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
            <h1>SURAT JALAN PRODUK NON-KONSUMSI</h1>
            <p>No. SJ: {{ $offlineSale->surat_jalan_number }}</p>
        </div>
        
        <div class="company-details">
            <h2>PT. PAK RUDI</h2>
            <p>Jl. Contoh No. 123, Jakarta</p>
            <p>Telp: (021) 123-4567 | Email: info@pakrudi.com</p>
            <p>NPWP: 01.234.567.8-123.456</p>
        </div>
        
        <div class="sj-info">
            <div class="sj-info-box sj-details">
                <h2>Informasi Pengiriman</h2>
                <p><strong>No. PO:</strong> {{ $offlineSale->No_PO ?? '-' }}</p>
                <p><strong>Tanggal:</strong> {{ $offlineSale->sale_date->format('d/m/Y') }}</p>
                <p><strong>Jenis Barang:</strong> Produk Non-Konsumsi</p>
            </div>
            
            <div class="sj-info-box customer-details">
                <h2>Informasi Penerima</h2>
                <p><strong>Nama:</strong> {{ $offlineSale->customer_name }}</p>
                <p><strong>Alamat:</strong> {{ $offlineSale->customerInfo->address ?? 'Alamat tidak tersedia' }}</p>
                <p><strong>Telepon:</strong> {{ $offlineSale->customerInfo->phone ?? 'No. Telepon tidak tersedia' }}</p>
                <p><strong>NPWP:</strong> {{ $offlineSale->customerInfo->npwp ?? 'NPWP tidak tersedia' }}</p>
            </div>
        </div>
        
        <div class="warning-box">
            <strong>PERHATIAN:</strong> Produk non-konsumsi memerlukan perawatan khusus. Harap periksa kondisi barang sebelum menerima.
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Barcode</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Kondisi</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($offlineSale->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->sku ?? 'N/A' }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->product->satuan->name ?? 'Pcs' }}</td>
                    <td>Baik</td>
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
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
            <p>Harap periksa kondisi barang sebelum menandatangani tanda terima.</p>
        </div>
        
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="printPDF();" style="padding: 10px 20px; background-color: #2196F3; color: white; border: none; cursor: pointer; border-radius: 4px;">
                Cetak PDF
            </button>
            <button onclick="printSuratJalan();" style="padding: 10px 20px; background-color: #2196F3; color: white; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">
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