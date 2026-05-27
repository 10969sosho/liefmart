<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProductVariants extends ManageRecords
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
