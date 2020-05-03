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
            $cache = $app->make('cache.store');
            $config = $app['config']->get('laravolt.avatar', []);

            $avatar = new Avatar($config, $cache);
            $avatar->setGenerator($app['avatar.generator']);

            return $avatar;
        });

        $this->app->bind('avatar.generator', function (Application $app) {
            $config = $app->make('config');
            $class = $config->get('laravolt.avatar.generator');

            return new $class;
        });
    }

    public function provides()
    {
        return ['avatar', 'avatar.generator'];
    }

    /**
     * Application is booting.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfigurations();
    }

    /**
     * Register the package configurations.
     *
     * @return void
     */
    protected function registerConfigurations()
    {
        $this->mergeConfigFrom($this->packagePath('config/config.php'), 'laravolt.avatar');
        $this->publishes([$this->packagePath('config/config.php') => config_path('laravolt/avatar.php')], 'config');
    }

    /**
     * Loads a path relative to the package base directory.
     *
     * @param  string  $path
     * @return string
     */
    protected function packagePath($path = '')
    {
        return sprintf('%s/../%s', __DIR__, $path);
    }
}
