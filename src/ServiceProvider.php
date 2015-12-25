<?php

namespace Laravolt\Avatar;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class PackageServiceProvider.
 *
 * @see http://laravel.com/docs/5.1/packages#service-providers
 * @see http://laravel.com/docs/5.1/providers
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @see http://laravel.com/docs/5.1/providers#deferred-providers
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @see http://laravel.com/docs/5.1/providers#the-register-method
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('avatar', function ($app) {
            $config = $app->make('config');
            $cache = $app->make('cache');

            return new Avatar($config->get('avatar'), $cache);
        });
    }

    /**
     * Application is booting.
     *
     * @see http://laravel.com/docs/5.1/providers#the-boot-method
     *
     * @return void
     */
    public function boot()
    {
        $this->registerAssets();
        $this->registerConfigurations();
    }

    /**
     * Register the package public assets.
     *
     * @see http://laravel.com/docs/5.1/packages#public-assets
     *
     * @return void
     */
    protected function registerAssets()
    {
        $this->publishes([
            $this->packagePath('resources/assets') => base_path('resources/laravolt/avatar'),
        ], 'assets');
    }

    /**
     * Register the package configurations.
     *
     * @see http://laravel.com/docs/5.1/packages#configuration
     *
     * @return void
     */
    protected function registerConfigurations()
    {
        $this->mergeConfigFrom(
            $this->packagePath('config/config.php'), 'avatar'
        );
        $this->publishes([
            $this->packagePath('config/config.php') => config_path('avatar.php'),
        ], 'config');
    }

    /**
     * Loads a path relative to the package base directory.
     *
     * @param string $path
     *
     * @return string
     */
    protected function packagePath($path = '')
    {
        return sprintf('%s/../%s', __DIR__, $path);
    }
}
