<?php 

namespace OwowAgency\AutomatedApiDoc;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use OwowAgency\AutomatedApiDoc\Commands\GenerateApiDocumentation;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * This will be used to register config & view in
     * your package namespace.
     *
     * @var  string
     */
    protected $packageName = 'automated-api-docs';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish your config
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path($this->packageName.'.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateApiDocumentation::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php', $this->packageName
        );
    }
}
