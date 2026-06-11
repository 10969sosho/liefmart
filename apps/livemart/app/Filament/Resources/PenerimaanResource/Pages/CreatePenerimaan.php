<?php

namespace App\Filament\Resources\PenerimaanResource\Pages;

use App\Filament\Resources\PenerimaanResource;
use Filament\Resources\Pages\Page;

class CreatePenerimaan extends Page
{
    protected static string $resource = PenerimaanResource::class;

    protected static string $view = 'filament.pages.create-penerimaan';

    public function getTitle(): string
    {
        return 'Form Penerimaan Barang';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
