<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class LaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'laravel-blog',
            name: 'Laravel Official Blog',
            url: 'https://laravel.com/blog',
            tags: ['programming'],
            description: <<<'MARKDOWN'
Laravel Official Blog

`/laravel/blog.rss`や`/laravel/blog.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/laravel/blog',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'en',
            timezone: Timezone::UTC->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('laravel')->group(function () {
            Route::get('blog.{format?}', LaravelBlogDriver::class);
        });
    }
}
