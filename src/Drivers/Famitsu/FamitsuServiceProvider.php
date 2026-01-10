<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Famitsu;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;
use Revolution\Feedable\Drivers\Famitsu\Enums\Category;

class FamitsuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'famitsu',
            name: 'ファミ通.com',
            url: 'https://famitsu.com/',
            tags: ['game'],
            description: $this->description(),
            example: '/famitsu/category/new-article',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
        );
    }

    protected function description(): string
    {
        $cat = collect(Category::cases())
            ->map(fn (Category $category) => "- `{$category->value}`")
            ->implode(PHP_EOL);

        return <<<MARKDOWN
RSSHubから移植。

以下のカテゴリーを指定できます。
{$cat}

`/famitsu/category/new-article.rss`や`/famitsu/category/new-article.json`でフォーマットを指定できます。
MARKDOWN;
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('famitsu')->group(function () {
            Route::get('category/{category}.{format?}', FamitsuCategoryDriver::class);
        });
    }
}
