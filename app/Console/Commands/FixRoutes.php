<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FixRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the route issues by adding a Route Facade alias';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Create a route service provider
        $this->createRouteServiceProvider();

        // Clear route cache
        Artisan::call('route:clear');
        $this->info('Route cache cleared successfully.');

        return 0;
    }

    private function createRouteServiceProvider()
    {
        $filePath = app_path('Providers/RouteAliasServiceProvider.php');
        
        $content = <<<'EOT'
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
        // Register a custom alias for the missing route
        Route::aliasMiddleware('alias', function ($request, $next) {
            return $next($request);
        });

        // Create a redirect for finance.aruskasblibli.index
        Route::get('finance/aruskasblibli', function () {
            return redirect()->route('finance.aruskasblibli.index');
        })->name('finance.aruskasblibli');
    }
}
EOT;

        File::put($filePath, $content);
        $this->info('RouteAliasServiceProvider created successfully.');

        // Register the service provider in config/app.php
        $appConfigPath = config_path('app.php');
        $appConfig = file_get_contents($appConfigPath);
        $providersArray = "App\Providers\RouteServiceProvider::class,\n        App\Providers\RouteAliasServiceProvider::class,";
        $appConfig = str_replace("App\Providers\RouteServiceProvider::class,", $providersArray, $appConfig);
        File::put($appConfigPath, $appConfig);
        $this->info('Service provider registered in config/app.php.');
    }
}
