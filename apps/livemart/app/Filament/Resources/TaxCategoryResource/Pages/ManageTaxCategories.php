<?php

namespace App\Filament\Resources\TaxCategoryResource\Pages;

use App\Filament\Resources\TaxCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTaxCategories extends ManageRecords
{
    protected static string $resource = TaxCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
