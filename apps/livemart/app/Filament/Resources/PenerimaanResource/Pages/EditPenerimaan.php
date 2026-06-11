<?php

namespace App\Filament\Resources\PenerimaanResource\Pages;

use App\Filament\Resources\PenerimaanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPenerimaan extends EditRecord
{
    protected static string $resource = PenerimaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (Model $record): bool => $record->status === 'Unlocated'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->status !== 'Unlocated') {
            abort(403, 'Penerimaan yang sudah diproses (Located) tidak dapat diedit.');
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['metode_pembayaran'] === 'Cash') {
            $data['tanggal_jatuh_tempo'] = null;
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
