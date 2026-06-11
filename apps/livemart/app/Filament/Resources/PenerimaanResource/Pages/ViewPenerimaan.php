<?php

namespace App\Filament\Resources\PenerimaanResource\Pages;

use App\Filament\Resources\PenerimaanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\Model;

class ViewPenerimaan extends ViewRecord
{
    protected static string $resource = PenerimaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => PenerimaanResource::getUrl('index'))
                ->color('gray'),
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('penerimaan.print', $this->record->id))
                ->openUrlInNewTab()
                ->color('success'),
            Actions\EditAction::make()
                ->label('Edit')
                ->visible(fn (Model $record): bool => $record->status === 'Unlocated'),
            Actions\DeleteAction::make()
                ->visible(fn (Model $record): bool => $record->status === 'Unlocated'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Penerimaan')
                    ->schema([
                        TextEntry::make('kode_penerimaan')
                            ->label('Kode Penerimaan'),
                        TextEntry::make('mainCategory.name')
                            ->label('Kategori'),
                        TextEntry::make('nomor_po')
                            ->label('Nomor PO'),
                        TextEntry::make('tanggal_penerimaan')
                            ->label('Tanggal Penerimaan')
                            ->date('d-m-Y'),
                        TextEntry::make('metode_pembayaran')
                            ->label('Metode Pembayaran'),
                        TextEntry::make('tanggal_jatuh_tempo')
                            ->label('Tanggal Jatuh Tempo')
                            ->date('d-m-Y')
                            ->visible(fn (Model $record): bool => $record->metode_pembayaran === 'Jatuh Tempo' && !is_null($record->tanggal_jatuh_tempo)),
                        TextEntry::make('taxCategory.name')
                            ->label('Status Tax'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Located' => 'success',
                                'Unlocated' => 'warning',
                                default => 'secondary',
                            }),
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->default('-'),
                    ])
                    ->columns(3),

                Section::make('Total Penerimaan')
                    ->schema([
                        TextEntry::make('total_harga')
                            ->label(false)
                            ->money('IDR'),
                    ]),

                Section::make('Detail Barang')
                    ->schema([
                        ViewEntry::make('details_table')
                            ->label(false)
                            ->view('filament.components.penerimaan-detail-table'),
                    ]),

                Section::make('Riwayat Aktivitas')
                    ->schema([
                        RepeatableEntry::make('activities')
                            ->label(false)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Tanggal')
                                    ->dateTime('d-m-Y H:i'),
                                TextEntry::make('user.name')
                                    ->label('User')
                                    ->default('Admin'),
                                TextEntry::make('activity_type')
                                    ->label('Aktivitas'),
                                TextEntry::make('description')
                                    ->label('Detail'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
