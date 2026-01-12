<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Yomiuri;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class YomiuriServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'yomiuri-news',
            name: '読売新聞 速報ニュース',
            url: 'https://www.yomiuri.co.jp/news/',
            tags: ['news', 'japan'],
            description: <<<'MARKDOWN'
読売新聞 速報ニュース一覧のフィード。
🔐マークは有料記事です。

`/yomiuri/news.rss`や`/yomiuri/news.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/yomiuri/news',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('yomiuri')->group(function () {
            Route::get('news.{format?}', YomiuriNewsDriver::class);
        });
    }
}
