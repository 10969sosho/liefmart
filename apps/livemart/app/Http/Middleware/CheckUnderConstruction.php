<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUnderConstruction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get current route and main category
        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        $uri = $request->getRequestUri();
        $mainCategoryId = session('main_category_id');
        $mainCategoryName = session('main_category_name');
        
        // Check if user selected KOPI category (ID 1 is for coffee based on seeder)
        $isCoffeeCategory = $mainCategoryId == 1 || (is_string($mainCategoryName) && (stripos($mainCategoryName, 'kopi') !== false || stripos($mainCategoryName, 'coffee') !== false));
        
        // Define offline-related routes and URIs
        $offlineRoutes = [
            'sales.offline',
            'sales.offline.list',
            'sales.offline.create',
            'sales.offline.store',
            'sales.offline.show',
            'sales.offline.print.invoice',
            'sales.offline.print.sj',
            'sales.offline.destroy',
            'finance.offline.index',
            'finance.offline.invoices',
            'finance.offline.pay',
            'finance.offline.generate-invoice',
            'finance.offline.print-invoice',
            'finance.offline.print-invoice-after-return',
            'finance.offline.print-return-invoice',
            'finance.offline.approve-reprint',
            'finance.offline.delete-payment',
            'analytics.offline.index',
            'analytics.offline.monthly-sales-summary',
            'analytics.offline.sales-by-customer',
            'analytics.offline.sales-detail-report',
            'analytics.offline.sales-by-product',
            'analytics.offline.monthly-sales-summary.export',
            'analytics.offline.sales-by-customer.export',
            'analytics.offline.sales-by-product.export',
            'analytics.offline.sales-detail-report.export',
            'retur-offline.index',
            'retur-offline.create',
            'retur-offline.store',
            'retur-offline.show',
            'retur-offline.edit',
            'retur-offline.update',
            'retur-offline.process',
            'retur-offline.cancel',
            'retur-offline.reverse',
            'retur-offline.print',
            'retur-offline.finance.form',
            'retur-offline.finance.process',
            'retur-offline.finance.reprocess',
        ];
        
        // Define offline-related URI patterns
        $offlineUriPatterns = [
            '/sales/offline',
            '/finance/offline',
            '/analytics/offline',
            '/retur-offline',
        ];
        
        // Check if current request is for offline functionality
        $isOfflineRequest = in_array($routeName, $offlineRoutes);
        
        if (!$isOfflineRequest) {
            foreach ($offlineUriPatterns as $pattern) {
                if (strpos($uri, $pattern) !== false) {
                    $isOfflineRequest = true;
                    break;
                }
            }
        }
        
        // Redirect offline requests to under construction - DISABLED
        // if ($isOfflineRequest) {
        //     return response()->view('under-construction', ['featureType' => 'offline']);
        // }
        
        // Redirect coffee category users to under construction - DISABLED for dashboard
        // Allow dashboard access even for coffee category
        if ($isCoffeeCategory && $routeName !== 'dashboard' && $routeName !== 'home') {
            return response()->view('under-construction', ['featureType' => 'coffee']);
        }
        
        return $next($request);
    }
}
