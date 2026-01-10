<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\JsonFeed;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;

class JsonFeedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'jsonfeed',
            name: 'JSON Feed',
            description: 'RSSやAtomをJSON Feed形式に変換します。元のRSSの件数が多い場合は/jsonfeed?url=https://...&limit=20のようにlimitパラメータで件数を制限できます。',
            example: '/jsonfeed?url=https://',
            format: [Format::JSON->value],
        );
    }

    public function boot(): void
    {
        Route::prefix('jsonfeed')->group(function () {
            Route::get('/', JsonFeedDriver::class);
        });
    }
}
