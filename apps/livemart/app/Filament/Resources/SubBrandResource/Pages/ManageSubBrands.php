<?php

namespace App\Filament\Resources\SubBrandResource\Pages;

use App\Filament\Resources\SubBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSubBrands extends ManageRecords
{
    protected static string $resource = SubBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
