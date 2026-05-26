<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.5;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 2px solid #4CAF50;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            color: #4CAF50;
        }
        .invoice-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .company-details {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 20px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info-box {
            width: 45%;
        }
        .invoice-details h2, .customer-details h2 {
            font-size: 16px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            color: #4CAF50;
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
            background-color: #E8F5E9;
            color: #2E7D32;
        }
        .text-right {
            text-align: right;
        }
        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #F1F8E9;
            border-left: 4px solid #4CAF50;
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
            border-top: 1px solid #4CAF50;
            padding-top: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            font-size: 12px;
            border-top: 1px solid #4CAF50;
            padding-top: 20px;
        }
        .tax-info {
            background-color: #E8F5E9;
            padding: 15px;
            margin: 20px 0;
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
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>INVOICE</h1>
            <p>No. INV: {{ $invoice->invoice_number }}</p>
        </div>
        
        <div class="company-details">
            <h2>PT. PAK RUDI</h2>
            <p>Jl. Contoh No. 123, Jakarta</p>
            <p>Telp: (021) 123-4567 | Email: info@pakrudi.com</p>
            <p>NPWP: 01.234.567.8-123.456</p>
        </div>
        
        <div class="invoice-info">
            <div class="invoice-info-box invoice-details">
                <h2>Informasi Invoice</h2>
                <p><strong>Tanggal:</strong> {{ $invoice->created_at->format('d/m/Y') }}</p>
                <p><strong>Jatuh Tempo:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
                <p><strong>Jenis Transaksi:</strong> Online</p>
                <p><strong>Kategori Produk:</strong> KOPI</p>
            </div>
            
            <div class="invoice-info-box customer-details">
                <h2>Informasi Pelanggan</h2>
                <p><strong>Nama:</strong> {{ $invoice->customer_name }}</p>
                <p><strong>Alamat:</strong> {{ $invoice->customer_address }}</p>
                <p><strong>Telepon:</strong> {{ $invoice->customer_phone }}</p>
                <p><strong>NPWP:</strong> {{ $invoice->customer_npwp ?? 'Tidak ada' }}</p>
            </div>
        </div>
        
        <div class="tax-info">
            <h3>Informasi Pajak</h3>
            <p>PKP (Pengusaha Kena Pajak)</p>
            <p>PPN 11%</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Barcode</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Harga Satuan</th>
                    <th>Diskon</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->sku ?? 'N/A' }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->discount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right"><strong>Subtotal</strong></td>
                    <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right"><strong>PPN (11%)</strong></td>
                    <td class="text-right">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right"><strong>Total</strong></td>
                    <td class="text-right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        
        @if ($invoice->notes)
        <div class="notes">
            <h2>Catatan</h2>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
        
        <div class="signatures">
            <div class="signature-box">
                <p>Dibuat oleh</p>
                <div class="signature-line">
                    (.................................)
                </div>
            </div>
            
            <div class="signature-box">
                <p>Disetujui oleh</p>
                <div class="signature-line">
                    (.................................)
                </div>
            </div>
            
            <div class="signature-box">
                <p>Diterima oleh</p>
                <div class="signature-line">
                    (.................................)
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Invoice ini adalah dokumen resmi dan sah</p>
            <p>Pembayaran dapat dilakukan melalui transfer bank ke rekening:</p>
            @php
                $activeAccount = \App\Models\BankAccount::getActive();
            @endphp
            @if($activeAccount)
                <p><strong>{{ $activeAccount->bank_name }} {{ $activeAccount->account_number }} atas nama {{ $activeAccount->account_name }}</strong></p>
            @else
                <p><strong>DANAMON ********** atas nama PT. PAK RUDI</strong></p>
            @endif
        </div>
        
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="printPDF();" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 4px;">
                Cetak PDF
            </button>
            <button onclick="printInvoice();" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">
                Cetak Langsung
            </button>
            <button onclick="window.close();" style="padding: 10px 20px; background-color: #f44336; color: white; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">
                Tutup
            </button>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function printInvoice() {
            window.print();
        }
        
        function printPDF() {
            document.querySelector('.no-print').style.display = 'none';
            
            const options = {
                margin: 10,
                filename: 'Invoice_{{ $invoice->invoice_number }}.pdf',
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