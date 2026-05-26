<?php

namespace App\Exports;

use App\Models\Penerimaan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class PenerimaanExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $request;
    protected $counter = 1;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        // Build the same query as in the controller index method
        return Penerimaan::with(['mainCategory', 'taxCategory', 'details.product', 'details.satuan'])
            ->when($this->request->filled('kode'), function ($q) {
                return $q->where('kode_penerimaan', 'like', '%' . $this->request->kode . '%');
            })
            ->when($this->request->filled('kategori'), function ($q) {
                return $q->where('main_category_id', $this->request->kategori);
            })
            ->when($this->request->filled('nomor_po'), function ($q) {
                return $q->where('nomor_po', 'like', '%' . $this->request->nomor_po . '%');
            })
            ->when($this->request->filled('status'), function ($q) {
                return $q->where('status', $this->request->status);
            })
            ->when($this->request->filled('tax_category'), function ($q) {
                return $q->whereHas('taxCategory', function ($subQ) {
                    $subQ->where('name', $this->request->tax_category);
                });
            })
            ->when($this->request->filled('start_date'), function ($q) {
                return $q->whereDate('tanggal_penerimaan', '>=', $this->request->start_date);
            })
            ->when($this->request->filled('end_date'), function ($q) {
                return $q->whereDate('tanggal_penerimaan', '<=', $this->request->end_date);
            })
            ->orderBy('tanggal_penerimaan', 'asc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Penerimaan',
            'Nomor PO',
            'Tanggal Penerimaan',
            'Kategori Utama',
            'Status Tax',
            'Metode Pembayaran',
            'Tanggal Jatuh Tempo',
            'Total (DPP)',
            'Status',
            'Jumlah Item',
            'Catatan',
            'Dibuat Pada'
        ];
    }

    public function map($penerimaan): array
    {
        return [
            $this->counter++,
            $penerimaan->kode_penerimaan,
            $penerimaan->nomor_po,
            $penerimaan->tanggal_penerimaan->format('d/m/Y'),
            $penerimaan->mainCategory->name ?? '-',
            $penerimaan->taxCategory->name ?? '-',
            $penerimaan->metode_pembayaran,
            $penerimaan->tanggal_jatuh_tempo ? $penerimaan->tanggal_jatuh_tempo->format('d/m/Y') : '-',
            'Rp ' . number_format(round($penerimaan->calculated_total), 0, ',', '.'),
            $penerimaan->status,
            $penerimaan->details->count(),
            $penerimaan->catatan ?? '-',
            $penerimaan->created_at->format('d/m/Y H:i')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ],
            // Apply borders to all cells
            'A1:M1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]
        ];
    }
} 