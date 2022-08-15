<?php

namespace RennyPasardesa\Apriori;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
    * Publishes configuration file.
    *
    * @return  void
    */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/pasardesa.php' => config_path('pasardesa.php'),
        ], 'pasardesa-config');

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    /**
    * Make config publishment optional by merging the config from the package.
    *
    * @return  void
    */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/pasardesa.php',
            'pasardesa'
        );
    }
}
