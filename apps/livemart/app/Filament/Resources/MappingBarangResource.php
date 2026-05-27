<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MappingBarangResource\Pages;
use App\Models\MappingBarang;
use App\Models\PlatformProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class MappingBarangResource extends Resource
{
    protected static ?string $model = MappingBarang::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Mapping Barang';

    protected static ?string $modelLabel = 'Mapping Barang';

    protected static ?string $pluralModelLabel = 'Mapping Barang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('platform_product_id')
                    ->label('Barang Platform')
                    ->relationship('platformProduct', 'platform_product_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->label('Produk Internal')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Kuantitas')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Forms\Components\TextInput::make('version')
                    ->label('Versi')
                    ->numeric()
                    ->default(1),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\DateTimePicker::make('valid_from')
                    ->label('Berlaku Dari'),
                Forms\Components\DateTimePicker::make('valid_until')
                    ->label('Berlaku Sampai'),
                Forms\Components\Textarea::make('change_reason')
                    ->label('Alasan Perubahan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('platformProduct.platform.name')
                    ->label('Platform')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('platformProduct.platform_product_name')
                    ->label('Barang Platform')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk Internal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Berlaku Dari')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->label('Produk'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMappingBarangs::route('/'),
        ];
    }
}
