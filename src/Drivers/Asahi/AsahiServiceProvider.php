<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Asahi;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class AsahiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'asahi-news',
            name: '朝日新聞 速報・新着ニュース',
            url: 'https://www.asahi.com/news/',
            tags: ['news', 'japan'],
            description: <<<'MARKDOWN'
朝日新聞デジタルの速報・新着ニュース一覧のフィード。

昨日までの記事を含みます。

`/asahi/news.rss`や`/asahi/news.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/asahi/news',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('asahi')->group(function () {
            Route::get('news.{format?}', AsahiNewsDriver::class);
        });
    }
}
