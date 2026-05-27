<?php

namespace App\Filament\Resources\MappingBarangResource\Pages;

use App\Filament\Resources\MappingBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMappingBarangs extends ManageRecords
{
    protected static string $resource = MappingBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
