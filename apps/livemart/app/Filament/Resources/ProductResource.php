<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\TaxCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('barcode')
                            ->label('Barcode')
                            ->maxLength(100),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Klasifikasi')
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('sub_brand_id', null)),

                        Forms\Components\Select::make('sub_brand_id')
                            ->label('Sub Brand')
                            ->relationship('subBrand', 'name', fn ($query, $get) =>
                                $query->when($get('brand_id'), fn ($q, $brandId) =>
                                    $q->where('brand_id', $brandId)
                                )
                            )
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('product_category_id')
                            ->label('Kategori')
                            ->relationship('productCategory', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('product_type_id')
                            ->label('Tipe')
                            ->relationship('productType', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('product_size_id')
                            ->label('Ukuran')
                            ->relationship('productSize', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('product_variant_id')
                            ->label('Varian')
                            ->relationship('productVariant', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('tax_category_id')
                            ->label('Kategori Pajak')
                            ->relationship('taxCategory', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Harga')
                    ->schema([
                        Forms\Components\TextInput::make('initial_price')
                            ->label('Harga Awal')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),

                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Diskon (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable(),

                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('initial_price')
                    ->label('Harga Awal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->label('Brand'),
                SelectFilter::make('productCategory')
                    ->relationship('productCategory', 'name')
                    ->label('Kategori'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
