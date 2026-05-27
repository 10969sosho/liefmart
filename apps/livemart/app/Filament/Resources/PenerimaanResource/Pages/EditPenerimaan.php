<?php

namespace App\Filament\Resources\PenerimaanResource\Pages;

use App\Filament\Resources\PenerimaanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenerimaan extends EditRecord
{
    protected static string $resource = PenerimaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
