<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Revolution\Feedable\Drivers\JsonFeed\JsonFeedDriver;
use Revolution\Feedable\Drivers\Laravel\LaravelBlogDriver;

test('FeedableServiceProvider registers build-in drivers', function (): void {
    $routes = Route::getRoutes();

    expect($routes->getByAction(JsonFeedDriver::class)->uri())->toBe('jsonfeed')
        ->and($routes->getByAction(LaravelBlogDriver::class)->uri())->toBe('laravel/blog.{format?}');
});
