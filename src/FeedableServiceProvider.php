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
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole() && class_exists(DriverMakeCommand::class)) {
            $this->commands([
                DriverMakeCommand::class,
            ]);
        }

        foreach ($this->drivers() as $driver) {
            $this->app->register($driver);
        }
    }

    protected function drivers(): array
    {
        $namespace = 'Revolution\\Feedable\\';

        $paths = File::glob(__DIR__.'/Drivers/*/*ServiceProvider.php');

        $drivers = [];

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
                $drivers[] = $driver;
            }
        }

        return $drivers;
    }
}
