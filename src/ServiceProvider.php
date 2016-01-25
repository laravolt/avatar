<?php

namespace Laravolt\Avatar;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('avatar', function (Application $app) {
            $config = $app->make('config');
            $cache = $app->make('cache');

            $avatar = new Avatar($config->get('avatar'), $cache, new InitialGenerator());

            // list of folder to scan where font located, order by priority
            $fontFolder = [
                // no folder at all, allow developer to supply full path to file in their configuration
                '',

                // find file located in published asset folder
                base_path('resources/laravolt/avatar/fonts/'),

                // find font included by default in package
                __DIR__.'/../resources/assets/fonts/',
            ];

            $avatar->setFontFolder($fontFolder);

            return $avatar;
        });
    }

    /**
     * Application is booting.
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
