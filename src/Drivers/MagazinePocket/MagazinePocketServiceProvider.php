<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\MagazinePocket;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class MagazinePocketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'magazine-pocket',
            name: 'マガポケ',
            url: 'https://pocket.shonenmagazine.com/',
            tags: ['manga'],
            description: <<<'MARKDOWN'
マガポケの今日の更新作品。復刻作品も含まれます。

`/shonenmagazine/pocket.rss`や`/shonenmagazine/pocket.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/shonenmagazine/pocket',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('shonenmagazine')->group(function () {
            Route::get('pocket.{format?}', MagazinePocketDriver::class);
        });
    }
}
