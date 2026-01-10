<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Nintendo;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class NintendoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'nintendo-direct',
            name: '任天堂 ニンテンドーダイレクト',
            url: 'https://www.nintendo.com/jp/nintendo-direct/',
            tags: ['game'],
            description: <<<'MARKDOWN'
最新のニンテンドーダイレクト。通常のダイレクトのみで小規模なダイレクトは含まれません。

`/nintendo/direct.rss`や`/nintendo/direct.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/nintendo/direct',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );

        Driver::about(
            id: 'nintendo-ir-news',
            name: '任天堂 IRニュース',
            url: 'https://www.nintendo.co.jp/ir/news/index.html',
            tags: ['game'],
            description: <<<'MARKDOWN'
任天堂のIRニュース

`/nintendo/ir/news.rss`や`/nintendo/ir/news.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/nintendo/ir/news',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );

        // トピックスは公式RSSがある
        // https://www.nintendo.com/jp/topics/c/api/whatsnew.xml

        // 全体の公式RSS
        // https://www.nintendo.co.jp/news/whatsnew.xml
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('nintendo')->group(function () {
            Route::get('ir/news.{format?}', IRNewsDriver::class);
            Route::get('direct.{format?}', DirectDriver::class);
        });
    }
}
