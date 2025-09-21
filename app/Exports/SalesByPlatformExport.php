<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SalesByPlatformExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithCustomValueBinder
{
    protected $orders;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedPlatform;

    public function __construct($orders, $summary, $startDate, $endDate, $selectedPlatform = null)
    {
        $this->orders = collect($orders);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedPlatform = $selectedPlatform;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Get the column index (1-based)
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        
        // Column 3 is "Order Number" - force this to be text
        if ($columnIndex === 3 && is_string($value) && !empty($value) && $value !== '-') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        // For all other values, use the default behavior
        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Order Number',
            'Platform',
            'Value (Rp)',
            'Volume (pcs)',
        ];
    }

    public function map($order): array
    {
        static $index = 0;
        $index++;

        // Handle both object and array formats
        if (is_object($order)) {
            $tanggal = $order->tanggal ? $order->tanggal->format('d-m-Y') : 'N/A';
            $orderNumber = $order->order_number ?? '';
            $platformName = $order->platform ? $order->platform->name : 'N/A';
            $totalValue = $order->total_value ?? 0;
            $totalVolume = $order->total_volume ?? 0;
        } else {
            $tanggal = isset($order['tanggal']) ? $order['tanggal']->format('d-m-Y') : 'N/A';
            $orderNumber = $order['order_number'] ?? '';
            $platformName = isset($order['platform']) ? $order['platform']['name'] : 'N/A';
            $totalValue = $order['total_value'] ?? 0;
            $totalVolume = $order['total_volume'] ?? 0;
        }

        return [
            $index,
            $tanggal,
            (string)$orderNumber,
            $platformName,
            $totalValue,
            $totalVolume,
        ];
    }
} 