<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Services\Analytics\SalesAnalyticsService;
use Illuminate\Http\Request;

/**
 * SalesAnalyticsController - REFACTORED VERSION
 * 
 * Thin controller - hanya menerima input & return view
 * Semua logika ada di Service layer
 * Semua perhitungan ada di Query layer (SQL)
 */
class SalesAnalyticsController extends Controller
{
    protected $service;
    
    public function __construct(SalesAnalyticsService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Sales by Day of Week Report
     * REFACTORED: Thin controller, semua logika di service
     */
    public function salesByDayOfWeekReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'quick_range' => $request->input('quick_range'),
        ];
        
        $data = $this->service->getSalesByDayOfWeek($filters);
        
        return view('analytics.sales_by_day_of_week', [
            'dayOfWeekData' => collect($data['day_of_week_data']),
            'dayOfWeekSummary' => $this->buildDayOfWeekSummary($data['day_of_week_data']),
            'dayNames' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'platformSummary' => collect($data['platform_summary']),
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Helper: Build day of week summary for JavaScript charts
     */
    private function buildDayOfWeekSummary(array $dayOfWeekData): array
    {
        $summary = [];
        foreach ($dayOfWeekData as $day) {
            $summary[$day['day_of_week']] = [
                'day_name' => $day['day_name'],
                'total_value' => $day['total_value'],
                'total_nominal' => $day['total_nominal'],
                'total_hpp' => $day['total_hpp'],
                'total_gross_profit' => $day['total_gross_profit'],
                'total_volume' => $day['total_volume'],
                'order_count' => $day['total_orders'],
            ];
        }
        return $summary;
    }
}

