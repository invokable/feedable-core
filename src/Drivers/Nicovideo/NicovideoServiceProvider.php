<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Nicovideo;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Drivers\Nicovideo\Enums\Category;

class NicovideoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $cat = collect(Category::cases())
            ->map(fn (Category $category) => "- `{$category->value}`")
            ->implode(PHP_EOL);

        Driver::about(
            id: 'nicovideo-manga',
            name: 'ニコニコ静画マンガ',
            url: 'https://manga.nicovideo.jp/manga/list',
            tags: ['manga'],
            description: <<<MARKDOWN
ニコニコ静画マンガの最近更新された作品。

`/nicovideo/manga/{category}`には以下のカテゴリーを指定できます。
{$cat}

`/nicovideo/manga/all.rss`や`/nicovideo/manga/all.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/nicovideo/manga/all',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('nicovideo')->group(function () {
            Route::get('manga/{category?}.{format?}', NicoMangaDriver::class);
        });
    }
}
