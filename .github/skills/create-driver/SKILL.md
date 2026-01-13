---
name: create-driver
description: Guide for creating a built-in driver for Feedable. Use this when you want to add support for a new website by implementing a driver that fetches and formats the site's content into an RSS feed.
---

このパッケージ用の内蔵ドライバーを作る時はこのガイドに従ってください。

## 最初に対象サイトを調査

LaravelのHTTPクライアントで取得できて単純なhtmlの解析だけで対応できるサイトならドライバーを作成します。

JavaScriptで動的に生成されるサイトやログインが必要なサイトは対象外です。中断してユーザーの指示に従ってください。

## 内蔵ドライバー生成コマンド

```shell
vendor/bin/testbench make:driver Sample
```

`src/Drivers/Sample/SampleDriver.php`, `src/Drivers/Sample/SampleServiceProvider.php`, `tests/Feature/Drivers/SampleTest.php`が生成されます。
`Sample`は対象サイトに合わせて指定してください。

## 実装

既存の他のドライバーを参考にしてください。

### Driver

基本はLaravelのHTTPクライアントでhtmlを取得、PHP8.3用のDOMDocumentかSymfony DomCrawlerで解析、`handle()`はFeedItemの配列を返す形になります。

内蔵ドライバーはほとんどResponseFactoryでRSSとJSON Feedに対応しているので`__invoke()`はテンプレートから少し変更するだけで完成です。

対象サイトへのアクセスは最小限に抑え負荷がかからないようにする。個別URLへアクセスして全文取得はしなくていい。全文取得はフィードリーダー側で対応。

### ServiceProvider

ただのLaravelのService Provider。ルートを定義したりドライバー情報を登録する。

```php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;

class SampleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 本来のServiceProvider::register()はサービスコンテナの登録のみに使う。
        // DriverはLaravelの機能を使ってないのでここでも使える。
        Driver::about(
            id: 'sample-1',
            name: 'サンプル1',
            url: 'https://example.com/',
            tags: ['example'],
            description: 'サンプルドライバー',
            example: '/sample',
            language: 'ja',
        );

        // idさえ違っていれば複数のドライバー情報を登録可能
        Driver::about(
            id: 'sample-2',
            name: 'サンプル2',
            url: 'https://example.com/',
            tags: ['example'],
            description: 'サンプルドライバー',
            example: '/sample/two',
            language: 'ja',
        );
    }

    public function boot(): void
    {
        // ルート定義
        // 通常のroutes/web.phpのつもりで使うとwebミドルウェアが適用されてないので少し動作が違う場合がある。
        Route::prefix('sample')->group(function () {
            Route::get('/', SampleDriver::class);
            Route::get('two', SampleTwoDriver::class);
        });

        // 同じ動作にするにはmiddleware('web')を追加する。
        // CSRFなども適用されて外部からのポストが難しくなるのでドライバーの用途によっては注意が必要なので各ドライバーで工夫する。
        Route::middleware('web')->prefix('sample')->group(function () {
            Route::get('/', SampleDriver::class);
        });
    }
}
```

- Format enumのルートバインディングはwebミドルウェアがないと無効なので`Route::middleware('web')`は残す。
- ドライバーのServiceProviderはFeedableServiceProviderで自動登録されるので追加の作業は不要。

### Testing

ドライバーのテストではモックせずに実際のHTTPリクエストを使う。
遅くなるけど対象サイトの構造の変化にすぐに気付けるようにするため。

テスト実行時のみhtmlファイルをローカルに保存するコードをドライバーに追加しておく。
```php
if (app()->runningUnitTests()) {
    Storage::put('sample/home.html', $response->body());
}
```

通常のLaravelプロジェクトではないので実際の保存場所は `vendor/orchestra/testbench-core/laravel/storage/app/private/` の中。

### タイムゾーンの扱い

対象サイトのタイムゾーンが決まっている場合はドライバー側でタイムゾーンを指定する。Laravelのアプリケーション全体のタイムゾーン設定に依存しないようにする。

Laravelの`now()`や`today()`ヘルパーはEnumをそのまま渡せる。
`$now->copy()`はなるべく使わずに`now()`や`today()`を再度呼び出す。
`addDays()`や`subDays()`は使わずに`->plus()`や`->minus()`を使う。
```php
use Revolution\Feedable\Core\Enums\Timezone;

$now = now(Timezone::AsiaTokyo);
$tomorrow = today(Timezone::UTC)->plus(days: 1, hours: 12);
$yesterday = today(Timezone::UTC)->minus(days: 1);
```

CarbonではEnumを渡せないので`->value`を使用する。

```php
use Carbon\Carbon;

Carbon::parse($time, timezone: Timezone::AsiaTokyo->value);
```
