<?php

declare(strict_types=1);

namespace Revolution\Feedable;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Revolution\Feedable\Console\DriverMakeCommand;

class FeedableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerDrivers();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole() && class_exists(DriverMakeCommand::class)) {
            $this->commands([
                DriverMakeCommand::class,
            ]);
        }
    }

    /**
     * Auto register built-in drivers.
     */
    protected function registerDrivers(): void
    {
        $namespace = 'Revolution\\Feedable\\';

        $paths = File::glob(__DIR__.'/Drivers/*/*ServiceProvider.php');

        foreach ($paths as $path) {
            $driver = Str::of($path)
                ->after('/src/')
                ->replace(
                    ['/', '.php'],
                    ['\\', ''],
                )
                ->prepend($namespace)
                ->value();

            if (class_exists($driver)) {
                $this->app->register($driver);
            }
        }
    }
}
