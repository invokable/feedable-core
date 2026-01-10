<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\JumpPlus;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class JumpPlusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'shonenjumpplus',
            name: '少年ジャンプ＋',
            url: 'https://shonenjumpplus.com/',
            tags: ['manga'],
            description: <<<'MARKDOWN'
少年ジャンプ＋の最新マンガ記事を取得します。公式RSSから旧作を除いた新作のみのRSSです。

`/shonenjumpplus/daily.rss`や`/shonenjumpplus/daily.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/shonenjumpplus/daily',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('shonenjumpplus')->group(function () {
            Route::get('daily.{format?}', JumpPlusDriver::class);
        });
    }
}
