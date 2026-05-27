<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenerimaanResource\Pages;
use App\Models\Penerimaan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
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

                        Forms\Components\TextInput::make('nomor_po')
                            ->label('Nomor PO')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('tanggal_penerimaan')
                            ->label('Tanggal Penerimaan')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('tanggal_jatuh_tempo')
                            ->label('Tanggal Jatuh Tempo'),

                        Forms\Components\Select::make('lokasi_id')
                            ->label('Lokasi')
                            ->relationship('lokasi', 'nama')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options([
                                'tunai' => 'Tunai',
                                'transfer' => 'Transfer',
                                'tempo_7' => 'Tempo 7 Hari',
                                'tempo_14' => 'Tempo 14 Hari',
                                'tempo_30' => 'Tempo 30 Hari',
                                'tempo_60' => 'Tempo 60 Hari',
                                'tempo_90' => 'Tempo 90 Hari',
                            ]),

                        Forms\Components\Select::make('tax_category_id')
                            ->label('Kategori Pajak')
                            ->relationship('taxCategory', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'selesai' => 'Selesai',
                                'dibatalkan' => 'Dibatalkan',
                            ])
                            ->default('draft')
                            ->required(),

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
            ->columns([
                Tables\Columns\TextColumn::make('kode_penerimaan')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('No. PO')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_penerimaan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lokasi.nama')
                    ->label('Lokasi')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode Bayar')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'selesai' => 'success',
                        'draft' => 'warning',
                        'dibatalkan' => 'danger',
                        default => 'secondary',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ]),
                SelectFilter::make('lokasi')
                    ->relationship('lokasi', 'nama')
                    ->label('Lokasi'),
                SelectFilter::make('metode_pembayaran')
                    ->label('Metode Bayar')
                    ->options([
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer',
                        'tempo_7' => 'Tempo 7 Hari',
                        'tempo_14' => 'Tempo 14 Hari',
                        'tempo_30' => 'Tempo 30 Hari',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Penerimaan $record): string => route('filament.admin.resources.penerimaans.edit', $record)),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'edit' => Pages\EditPenerimaan::route('/{record}/edit'),
        ];
    }
}
