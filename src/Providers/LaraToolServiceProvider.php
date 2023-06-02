<?php
/**
 * User: CharleyChan
 * Date: 2023/6/2
 * Time: 4:42 下午
 **/

namespace Charleychan\Laratool\Providers;


use Illuminate\Support\ServiceProvider;

class LaraToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                $this->getConfigFile() => config_path('laratool.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            $this->getConfigFile(),
            'laratool'
        );

    }

    protected function getConfigFile()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'laratool.php';
    }

}
