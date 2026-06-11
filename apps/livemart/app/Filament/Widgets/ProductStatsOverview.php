<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Product::withoutGlobalScope('mainCategory')->count();
        $active = Product::withoutGlobalScope('mainCategory')->where('is_active', true)->count();
        $inactive = Product::withoutGlobalScope('mainCategory')->where('is_active', false)->count();

        return [
            Stat::make('Total Produk', $total)
                ->description('Semua produk')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('primary'),
            Stat::make('Produk Aktif', $active)
                ->description(number_format($total > 0 ? ($active / $total) * 100 : 0, 1) . '% dari total')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Produk Nonaktif', $inactive)
                ->description('Perlu ditinjau')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('warning'),
        ];
    }
}
