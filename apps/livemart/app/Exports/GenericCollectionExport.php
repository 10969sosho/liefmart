<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GenericCollectionExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $data;
    protected $headings;

    public function __construct($data, array $headings)
    {
        $this->data = collect($data);
        $this->headings = $headings;
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            if (is_object($item)) {
                return (array) $item;
            }
            return $item;
        });
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
