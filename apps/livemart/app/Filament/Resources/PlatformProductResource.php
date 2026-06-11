<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformProductResource\Pages;
use App\Models\PlatformProduct;
use App\Models\Platform;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class PlatformProductResource extends Resource
{
    protected static ?string $model = PlatformProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Barang Platform';

    protected static ?string $modelLabel = 'Barang Platform';

    protected static ?string $pluralModelLabel = 'Barang Platform';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('platform_id')
                    ->label('Platform')
                    ->relationship('platform', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('platform_product_name')
                    ->label('Nama Produk Platform')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('variant')
                    ->label('Varian')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('platform.name')
                    ->label('Platform')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('platform_product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant')
                    ->label('Varian')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->relationship('platform', 'name')
                    ->label('Platform'),
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
            'index' => Pages\ManagePlatformProducts::route('/'),
        ];
    }
}
