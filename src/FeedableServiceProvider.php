<?php

declare(strict_types=1);

namespace Revolution\Feedable;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class FeedableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->drivers() as $driver) {
            $this->app->register($driver);
        }
    }

    protected function drivers(): array
    {
        $namespace = 'Revolution\\Feedable\\';

        $paths = [
            __DIR__.DIRECTORY_SEPARATOR.'Drivers',
        ];

        $drivers = [];

        foreach ((new Finder)->in($paths)->name('*ServiceProvider.php')->files() as $driver) {
            $driver = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($driver->getPathname(), 'src'.DIRECTORY_SEPARATOR),
            );

            $drivers[] = $driver;
        }

        return $drivers;
    }
}
