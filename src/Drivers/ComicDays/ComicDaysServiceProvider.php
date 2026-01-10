<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\ComicDays;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class ComicDaysServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'comic-days',
            name: 'コミックDAYS オリジナル',
            url: 'https://comic-days.com/',
            tags: ['manga'],
            description: <<<'MARKDOWN'
コミックDAYSの今日更新された無料連載の最新話一覧。復刻作品も含まれます。

`/comic-days/original.rss`や`/comic-days/original.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/comic-days/original',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('comic-days')->group(function () {
            Route::get('original.{format?}', ComicDaysDriver::class);
        });
    }
}
