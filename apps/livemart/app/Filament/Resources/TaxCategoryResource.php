<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxCategoryResource\Pages;
use App\Filament\Resources\TaxCategoryResource\RelationManagers;
use App\Models\TaxCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxCategoryResource extends Resource
{
    protected static ?string $model = TaxCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 42;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope('mainCategory');
    }

    protected static ?string $navigationLabel = 'Kategori Pajak';

    protected static ?string $modelLabel = 'Kategori Pajak';

    protected static ?string $pluralModelLabel = 'Kategori Pajak';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tax_percentage')
                    ->label('Tarif')
                    ->numeric()
                    ->suffix('%'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_percentage')
                    ->label('Tarif')
                    ->suffix('%'),
            ])
            ->filters([
                //
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
            'index' => Pages\ManageTaxCategories::route('/'),
        ];
    }
}
