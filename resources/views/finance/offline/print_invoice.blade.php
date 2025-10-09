<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            width: 800px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .company-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
        }
        
        .company-website {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            text-align: center;
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
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            table-layout: fixed;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        td.product-description {
            white-space: normal;
            word-wrap: break-word;
        }
        
        td.discount-column {
            white-space: normal;
            word-wrap: break-word;
            font-size: 11px;
            line-height: 1.3;
        }
        
        thead th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .barcode {
            font-family: "Courier New", monospace;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        
        .grand-total {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        
        .signature {
            width: 30%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            margin-bottom: 10px;
        }
        
        .payment-info {
            margin-top: 30px;
            font-style: italic;
        }
        
        /* Print styles */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .invoice-container {
                max-width: 100%;
                margin: 0;
                padding: 15px;
                border: none;
                box-shadow: none;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="no-print" style="text-align: right; margin-bottom: 10px;">
            <button onclick="window.print();" style="background: #008000; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" style="vertical-align: middle; margin-right: 5px;" viewBox="0 0 16 16">
                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                </svg>
                Cetak Invoice
            </button>
        </div>
        
        @php
            $printCount = $invoice->print_count ?? 0;
            $isPrintedBefore = $printCount > 0;
            $isReprint = $isPrintedBefore && $invoice->reprint_approved;
            
            // Check if the items are taxable (PKP) and set appropriate logo
            $barangKeluarItems = $invoice->barangKeluarItems;
            $firstItem = $barangKeluarItems->first();
            $isPKP = false;
            $logoFile = 'HGN.jpeg'; // Default logo
            $taxId = null;
            $mainCategoryId = null;
            
            if ($firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id) {
                $taxId = $firstItem->warehouseStock->tax_id;
                
                if ($taxId == 3) {
                    // HGN = PKP = PT. HARVEST GLOBAL NIAGA
                    $isPKP = true;
                    $logoFile = 'HGN.jpeg';
                } elseif ($taxId == 4) {
                    // LM = Non-PKP = LaMOURAD
                    $isPKP = false;
                    $logoFile = 'LM.jpeg';
                } else {
                    // Default untuk tax ID lainnya
                    $isPKP = in_array($taxId, [3, 5, 7]);
                    $logoFile = $isPKP ? 'HGN.jpeg' : 'LM.jpeg';
                }
            }

            // Get active bank account
            $activeAccount = \App\Models\BankAccount::getActive();
            
            // Create variables for totals
            $grandTotal = 0;
            $subTotal = 0;
            $totalQty = 0;
            $ppn = 0;
            $totalBeforeDiscount = 0;
            $totalDiscount = 0;
        @endphp
        
        @if($isPrintedBefore)
        <div class="no-print" style="margin-bottom: 15px; padding: 10px; background-color: #ffe9e9; border-radius: 5px; border-left: 4px solid #dc3545;">
            <div style="display: flex; align-items: center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#dc3545" style="margin-right: 10px;" viewBox="0 0 16 16">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                <div>
                    <strong style="color: #dc3545;">Perhatian!</strong>
                    <p style="margin: 5px 0 0 0;">
                        @if($isReprint)
                            Invoice ini telah dicetak sebelumnya ({{ $printCount }}x). Pencetakan ulang telah disetujui oleh Super Admin.
                        @else
                            Invoice ini telah dicetak sebelumnya ({{ $printCount }}x). Harap cetak dengan hati-hati.
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endif
        
        <div class="header">
            <div class="company-info">
                <img src="{{ asset('images/INV/' . $logoFile) }}" alt="Logo" class="logo" style="width: 500px; height: auto;">
            </div>
        </div>
        
        <div class="horizontal-line"></div>
        
        <div class="invoice-details">
            <div class="invoice-info">
                <div><strong>TANGGAL INVOICE</strong> : {{ $invoice->tanggal_invoice->format('d F Y') }}</div>
                <div><strong>NO. INVOICE</strong> : {{ $invoice->invoice_number }}</div>
            </div>
            
            <div class="customer-info">
                <div><strong>CUSTOMER</strong> : 
                @php
                    $offlineSale = null;
                    $customer = null;
                    
                    if ($firstItem && $firstItem->offlineSaleItem) {
                        $offlineSale = $firstItem->offlineSaleItem->offlineSale;
                        if ($offlineSale) {
                            $customer = $offlineSale->customerInfo;
                        }
                    }
                @endphp
                
                @if($customer)
                    {{ $customer->name }}
                </div>
                <div>
                    {{ $customer->address ?? '' }}<br>
                    {{ $customer->phone ? 'Telp: '.$customer->phone : '' }}
                </div>
                @endif
            </div>
        </div>
        
        <div class="horizontal-line"></div>
        
        <table>
            <thead>
                <tr>
                    <th width="40" class="text-center">No</th>
                    <th width="200">Deskripsi</th>
                    <th width="100">Barcode</th>
                    <th width="60" class="text-center">Qty</th>
                    <th width="100" class="text-right">Harga</th>
                    <th width="70" class="text-right">Diskon</th>
                    <th width="100" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Calculate invoice totals with progressive rounding
                    $invoiceItems = $invoice->barangKeluarItems;
                    
                    $totalBeforeDiscount = 0;
                    $totalDiscount = 0;
                    $totalAfterDiscount = 0;
                    $dpp = 0;
                    $ppn = 0;
                    $grandTotal = 0;
                    $totalQty = 0;
                    $subTotal = 0;
                    
                    // First, calculate total amount before discount
                    foreach ($invoiceItems as $item) {
                        // Ambil data offline sale item
                        $offlineSaleItem = $item->offlineSaleItem;
                        if (!$offlineSaleItem) continue;
                        
                        // Ambil harga dasar dan qty
                        $price = $offlineSaleItem->unit_price;
                        $qty = $item->qty;
                        
                        // Count total pieces
                        $totalQty += $qty;
                        
                        // Tambahkan ke total before discount (harga × qty)
                        $itemTotal = $price * $qty;
                        $totalBeforeDiscount += $itemTotal;
                        
                        // Check for discounts
                        $currentTotal = $itemTotal;
                        
                        // Hitung semua diskon persen (1-5)
                        for($i = 1; $i <= 5; $i++) {
                            $percentField = "discount_percent_" . $i;
                            $amountField = "discount_amount_" . $i;
                            
                            // Diskon persen
                            $discountPercent = $offlineSaleItem->$percentField ?? 0;
                            if($discountPercent > 0) {
                                $discountAmount = $currentTotal * ($discountPercent / 100);
                                $totalDiscount += $discountAmount;
                                $currentTotal -= $discountAmount;
                                // Apply cascading rounding after each discount
                                $currentTotal = \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
                            }
                            
                            // Diskon nominal
                            $discountNominal = $offlineSaleItem->$amountField ?? 0;
                            if($discountNominal > 0) {
                                $discountAmount = $discountNominal;
                                $totalDiscount += ($discountAmount * $qty);
                                $currentTotal -= ($discountAmount * $qty);
                                // Apply cascading rounding after each discount
                                $currentTotal = \App\Helpers\NumberFormatter::roundToTwoDecimals($currentTotal);
                            }
                        }
                        
                        // Add to total after discount
                        $totalAfterDiscount += $currentTotal;
                    }
                    
                    // Based on tax calculation
                    $isPKP = $taxId == 3;
                    $dpp = \App\Helpers\NumberFormatter::calculateDPP($totalAfterDiscount);
                    $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
                    $ppn = \App\Helpers\NumberFormatter::calculatePPN($dpp11_12);
                    
                    // Grand total
                    if ($isPKP) {
                        $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($dpp, $ppn);
                    } else {
                        $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($dpp);
                    }
                @endphp
                @foreach($invoice->barangKeluarItems as $item)
                    @php
                        $offlineSaleItem = $item->offlineSaleItem;
                        $product = $offlineSaleItem && $offlineSaleItem->product ? $offlineSaleItem->product : null;
                        $qty = $offlineSaleItem && $offlineSaleItem->qty ? $offlineSaleItem->qty : ($item->qty ?? 0);
                        $price = $offlineSaleItem ? $offlineSaleItem->unit_price : 0;
                        
                        // Hitung total sebelum diskon untuk item ini
                        $itemBeforeDiscount = $price * $qty;
                        $currentTotal = $itemBeforeDiscount;
                        
                        // Hitung diskon persentase
                        $discountText = [];
                        $hasDiscount = false;
                        for($i = 1; $i <= 5; $i++) {
                            $percentField = "discount_percent_" . $i;
                            $amountField = "discount_amount_" . $i;
                            
                            // Check if either discount exists
                            if($offlineSaleItem->$percentField > 0 || $offlineSaleItem->$amountField > 0) {
                                $hasDiscount = true;
                                $discountText[] = "Diskon " . $i . ": " . 
                                    ($offlineSaleItem->$percentField > 0 ? number_format(\App\Helpers\NumberFormatter::formatForDatabase($offlineSaleItem->$percentField), 0, ',', '.') . '%' : '') .
                                    ($offlineSaleItem->$percentField > 0 && $offlineSaleItem->$amountField > 0 ? ' + ' : '') .
                                    ($offlineSaleItem->$amountField > 0 ? 'Rp.' . number_format(\App\Helpers\NumberFormatter::formatForDatabase($offlineSaleItem->$amountField), 2, ',', '.') : '');
                                
                                // Calculate discount amount
                                if($offlineSaleItem->$percentField > 0) {
                                    $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $offlineSaleItem->$percentField);
                                }
                                
                                if($offlineSaleItem->$amountField > 0) {
                                    $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $offlineSaleItem->$amountField * $qty);
                                }
                            }
                        }
                        
                        $subtotal = \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
                        $subTotal += $subtotal;
                        
                        // Format diskon text
                        $discountDisplay = $hasDiscount ? implode('<br>', $discountText) : '-';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="product-description">
                            {{ $product ? $product->name : 'Unknown Product' }}
                        </td>
                        <td>{{ $product && $product->barcode ? $product->barcode : '-' }}</td>
                        <td class="text-center">{{ number_format(\App\Helpers\NumberFormatter::formatForDatabase($qty), 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format(\App\Helpers\NumberFormatter::formatForDatabase($price), 2, ',', '.') }}</td>
                        <td class="text-right discount-column">{!! $discountDisplay !!}</td>
                        <td class="text-right">{{ number_format(\App\Helpers\NumberFormatter::formatForDatabase($subtotal), 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
          
        </table>
        
        <div class="horizontal-line"></div>
        
        <div class="totals">
            <div class="total-row">
                <div><strong>TOTAL SEBELUM DISKON :</strong></div>
                <div>{{ number_format(\App\Helpers\NumberFormatter::formatForDatabase($totalBeforeDiscount), 2, ',', '.') }}</div>
            </div>
            
            <div class="total-row">
                <div><strong>TOTAL DISKON :</strong></div>
                <div>({{ number_format(\App\Helpers\NumberFormatter::formatForDatabase($totalDiscount), 2, ',', '.') }})</div>
            </div>
            
            @if($isPKP)
            <div class="horizontal-line" style="margin: 10px 0;"></div>
            
            <div class="total-row">
                <div><strong>DPP :</strong></div>
                <div>{{ \App\Helpers\NumberFormatter::formatInvoiceAmount($dpp) }}</div>
            </div>
            
            <div class="total-row">
                <div><strong>DPP 11/12 :</strong></div>
                <div>{{ \App\Helpers\NumberFormatter::formatInvoiceAmount($dpp11_12) }}</div>
            </div>
            
            <div class="total-row">
                <div><strong>PPN (12% x DPP 11/12) :</strong></div>
                <div>{{ \App\Helpers\NumberFormatter::formatInvoiceAmount($ppn) }}</div>
            </div>
            @endif
        </div>
        
        <div class="horizontal-line"></div>
        
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <div><strong>TOTAL PCS : </strong> {{ number_format(\App\Helpers\NumberFormatter::formatForDatabase($totalQty), 2, ',', '.') }}</div>
            <div style="text-align: right;"><strong>TOTAL Rp. </strong> <span style="border: 1px solid #000; padding: 5px 10px;">{{ \App\Helpers\NumberFormatter::formatInvoiceAmount($grandTotal) }}</span></div>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <div>Tanda Terima,</div>
                <div class="signature-line"></div>
                <div>( )</div>
            </div>
            
            <div class="signature" style="text-align: right;">
                <div>Hormat Kami,</div>
                <div class="signature-line"></div>
                <div>
                    @if($taxId == 4)
                        ( LAMOURAD )
                    @else
                        ( PT. HARVEST GLOBAL NIAGA )
                    @endif
                </div>
            </div>
        </div>
        
        <div class="payment-info">
            <p>Pembayaran transfer ke rekening :<br>
            @if($activeAccount)
                {{ $activeAccount->bank_name }} {{ $activeAccount->account_number }} atas nama {{ $activeAccount->account_name }}
            @else
                @if($taxId == 4)
                    DANAMON ********** atas nama LAMOURAD
                @else
                    DANAMON ********** atas nama PT. HARVEST GLOBAL NIAGA
                @endif
            @endif
            </p>
        </div>
    </div>
</body>
</html>