<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenerimaanResource\Pages;
use App\Models\Penerimaan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class PenerimaanResource extends Resource
{
    protected static ?string $model = Penerimaan::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Penerimaan';

    protected static ?string $modelLabel = 'Penerimaan';

    protected static ?string $pluralModelLabel = 'Penerimaan';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penerimaan')
                    ->schema([
                        Forms\Components\TextInput::make('kode_penerimaan')
                            ->label('Kode Penerimaan')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('main_category_id')
                            ->label('Kategori Barang')
                            ->relationship('mainCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('nomor_po')
                            ->label('Nomor PO')
                            ->maxLength(255)
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_penerimaan')
                            ->label('Tanggal Penerimaan')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('lokasi_id')
                            ->label('Lokasi')
                            ->relationship('lokasi', 'nama')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options([
                                'Cash' => 'Cash',
                                'Jatuh Tempo' => 'Jatuh Tempo',
                            ])
                            ->required()
                            ->default('Cash'),

                        Forms\Components\DatePicker::make('tanggal_jatuh_tempo')
                            ->label('Tanggal Jatuh Tempo')
                            ->visible(fn (Forms\Get $get): bool => $get('metode_pembayaran') === 'Jatuh Tempo'),

                        Forms\Components\Select::make('tax_category_id')
                            ->label('Kategori Pajak')
                            ->relationship('taxCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'Unlocated' => 'Unlocated',
                                'Located' => 'Located',
                            ])
                            ->default('Unlocated')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Total')
                    ->schema([
                        Forms\Components\TextInput::make('total_harga')
                            ->label('Total Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_penerimaan', 'desc')
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('#')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('kode_penerimaan')
                    ->label('Kode Penerimaan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_penerimaan')
                    ->label('Tanggal Penerimaan')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('taxCategory.name')
                    ->label('Status Tax')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PKP' => 'info',
                        'NON PKP' => 'warning',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_harga')
                    ->label('DPP')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ppn')
                    ->label('PPN')
                    ->getStateUsing(function (Penerimaan $record): float {
                        if ($record->taxCategory && $record->taxCategory->name === 'PKP') {
                            return round($record->total_harga * 0.11);
                        }
                        return 0;
                    })
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('total_all')
                    ->label('Total')
                    ->getStateUsing(function (Penerimaan $record): float {
                        $dpp = round($record->total_harga);
                        $ppn = 0;
                        if ($record->taxCategory && $record->taxCategory->name === 'PKP') {
                            $ppn = round($dpp * 0.11);
                        }
                        return $dpp + $ppn;
                    })
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Located' => 'success',
                        'Unlocated' => 'warning',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode Bayar')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('lokasi.nama')
                    ->label('Lokasi')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Unlocated' => 'Unlocated',
                        'Located' => 'Located',
                    ]),
                SelectFilter::make('taxCategory')
                    ->label('Status Tax')
                    ->relationship('taxCategory', 'name'),
                Filter::make('tanggal_penerimaan')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai'),
                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_penerimaan', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_penerimaan', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Penerimaan $record): string => PenerimaanResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Penerimaan $record): string => route('penerimaan.print', $record->id))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(fn (Penerimaan $record): bool => $record->status === 'Unlocated'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (Penerimaan $record): bool => $record->status === 'Unlocated')
                    ->modalHeading(fn (Penerimaan $record): string => 'Hapus Penerimaan: ' . $record->kode_penerimaan)
                    ->modalDescription('Data penerimaan dengan status Located tidak bisa dihapus. Hapus hanya diperbolehkan jika status masih Unlocated.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope('mainCategory')
            ->with(['taxCategory', 'lokasi', 'mainCategory']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenerimaans::route('/'),
            'create' => Pages\CreatePenerimaan::route('/create'),
            'view' => Pages\ViewPenerimaan::route('/{record}'),
            'edit' => Pages\EditPenerimaan::route('/{record}/edit'),
        ];
    }
}
