<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Fate;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Drivers\Fate\Enums\Category;

class FateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $cat = collect(Category::cases())
            ->map(fn (Category $category) => "- `{$category->value}`")
            ->implode(PHP_EOL);

        Driver::about(
            id: 'fgo',
            name: 'FGOニュース',
            url: 'https://news.fate-go.jp/',
            tags: ['game'],
            description: <<<MARKDOWN
FGOニュースのフィード。

`/fgo/{category}`には以下のカテゴリーを指定できます。
{$cat}

`/fgo/news.rss`や`/fgo/news.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/fgo/news',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('fgo')->group(function () {
            Route::get('{category}.{format?}', FateGoDriver::class);
        });
    }
}
