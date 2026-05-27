<?php

namespace App\Filament\Resources\PlatformProductResource\Pages;

use App\Filament\Resources\PlatformProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePlatformProducts extends ManageRecords
{
    protected static string $resource = PlatformProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
