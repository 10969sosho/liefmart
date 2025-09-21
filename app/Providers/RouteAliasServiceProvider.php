<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\Finance\ArusKasBlibliController;

class RouteAliasServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Define all the aruskasblibli routes that were referenced in views
        // Order matters: specific routes must come before parameterized routes
        
        // Import routes - Point directly to controller
        Route::get('finance/aruskasblibli/import', [ArusKasBlibliController::class, 'import'])
            ->name('finance.aruskasblibli.import');
        
        // Create route - must come before routes with parameters
        Route::get('finance/aruskasblibli/create', [ArusKasBlibliController::class, 'create'])
            ->name('finance.aruskasblibli.create');
            
        // Preview and process routes
        Route::post('finance/aruskasblibli/preview', [ArusKasBlibliController::class, 'preview'])
            ->name('finance.aruskasblibli.preview');
        
        Route::post('finance/aruskasblibli/process', [ArusKasBlibliController::class, 'process'])
            ->name('finance.aruskasblibli.process');
            
        // Route with parameters must come after specific routes
        Route::get('finance/aruskasblibli/{transaction}/edit', [ArusKasBlibliController::class, 'edit'])
            ->name('finance.aruskasblibli.edit');
        
        Route::put('finance/aruskasblibli/{transaction}', [ArusKasBlibliController::class, 'update'])
            ->name('finance.aruskasblibli.update');
        
        Route::delete('finance/aruskasblibli/{transaction}', [ArusKasBlibliController::class, 'destroy'])
            ->name('finance.aruskasblibli.destroy');
        
        // Index and store routes
        Route::get('finance/aruskasblibli', [ArusKasBlibliController::class, 'index'])
            ->name('finance.aruskasblibli.index');
            
        Route::post('finance/aruskasblibli', [ArusKasBlibliController::class, 'store'])
            ->name('finance.aruskasblibli.store');
    }
}