# Feedable core and built-in drivers - Project Guidelines

## Project Overview

RSSフィードを提供してないサイトからRSSを作るLaravelプロジェクト**Feedable**からコアヘルパーと内蔵ドライバーを分離したcomposerパッケージ。
新規LaravelプロジェクトにこのパッケージをインストールすればFeedableと同じ機能が使える。
フォークしたプロジェクトに独自ドライバーを追加して運用していくとフォーク元に追従するのが難しくなっていくのでパッケージにして更新しやすくする。

## Technology Stack

- **Language**: PHP 8.3+
- **Framework**: Laravel 12.x+
- **Testing**: Pest PHP 4.x
- **Code Quality**: Laravel Pint (PSR-12)
- Vercel でデータベースなしでも動くようにする。`vercel-php`がまだPHP8.3しか使えないので8.3に制限。
- Playwrightを使ったデータ取得もできるけどVercelでは動かすのが難しい。Laravel Forge向け。

## Commands
```bash
composer run test          # Run all tests
composer run lint          # Format code with Pint
```

## Architecture
- Core: Feedableのコアヘルパー。ドライバーを作りやすいように用意しているけど独自ドライバーでの使用は必須ではない。
- Drivers: Feedableに内蔵されているドライバー群。実態はLaravelのルートなので独自ドライバーを個別のcomposerパッケージとして配布も可能。
- FeedableServiceProvider: 内蔵ドライバーを登録。

## ドライバー
各サイトのフィード生成コードはドライバーとして分離。
入口のルーティングから出口のレスポンスまで全てドライバーで制御可能。
サイト毎に細かい調整が必要になることは分かっているので厳密なパターンは適用せず最大限の柔軟性を持たせる。

### Service Provider

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

### タイムゾーンの扱い

対象サイトのタイムゾーンが決まっている場合はドライバー側でタイムゾーンを指定する。Laravelのアプリケーション全体のタイムゾーン設定に依存しないようにする。

## スクレイピング
- LaravelのHTTPクライアント: これで取得できるなら一番簡単。
- Playwright(`revolution/salvager`): JavaScriptで動的に生成されるページを取得する場合に使う。Vercelでは動かせない。
- Cloudflare Browser Rendering: Vercelでも使えるはず。個別にAPIトークンの設定が必要。無料プランでは1日10分まで。

## HTML解析
- DOMDocument: PHP8.3以下用。
- Dom\HTMLDocument: PHP8.4以上用。
- Symfony DomCrawler: 7.xはPHP8.1以上。8.0はPHP8.4以上。
- PlaywrightのLocator: `playwright-php/playwright`の`$page->locator()`はquerySelectorAllに似た使い方ができる。

## Feedable Core

ドライバーから使うヘルパー。

### Response
`JsonFeedResponse`や`Rss2Response`で最終的な出力フォーマットを固定化する。フィードのフォーマットは統一されてないのでこれを使わなくてもいい。

PHP 8.4の時期なら名前付き引数を使うのがいいので以下のような使い方。
```php
use Revolution\Feedable\Core\Response\Rss2Response;

return new Rss2Response(
    title: $title,
    items: $items,
);
```

ユーザーがフォーマットを選べるようにするなら`ResponseFactory`を使う。
拡張子でフォーマットを指定できるようにするなら以下のようにする。`feed.rss`や`feed.json`のようにアクセスできる。
```php
use Revolution\Feedable\Core\Response\ResponseFactory;
use Revolution\Feedable\Core\Enums\Format;

Route::get('feed.{format?}', function (Format $format = Format::RSS) {
return ResponseFactory::format($format)
->make(
    title: $title,
    items: $items,
);
});
```

現代ではjsonが使いやすいので全体的にJsonFeedに寄せた方がいいかもしれないけどリーダー側が対応できてない。Feedable内部でのフィールド名などはJsonFeedに寄せる。出力フォーマットはRSSが標準。

`ErrorResponse`はエラー時のレスポンス。htmlを返す。RSSHubでは詳細なエラー画面を表示しているので後で拡張。

### FeedItem
JsonFeedやRSS2やAtomで共通のフィードアイテムオブジェクト。ドライバーで生成したデータをこのクラスに詰めてレスポンスに渡す。
使わなくてもいいのでbladeでは`data_get()`を使ってarrayでもオブジェクトでもいいようにしている。

### FeedableDriver

ドライバー用の契約=Interface。必須メソッドは`handle()`のみ。テストを書きやすいようにメインの処理を`handle()`、Routeからの入力・出力を`__invoke()`で分ける意図があるけどこれも使わなくてもいい。

### Support
Supportはstaticメソッドのみで構成されたヘルパー。

#### AbsoluteUri
`AbsoluteUri::resolve()`は相対URLを絶対URLに変換する。

```php
use Revolution\Feedable\Core\Support\AbsoluteUri;

$absoluteUrl = AbsoluteUri::resolve('https://example.com/', '/images/sample.jpg');
```

URLの組み立てにはなるべくLaravelの`Illuminate\Support\Uri`を使う。`/`の有無によるミスを防ぐため。
```php
use Illuminate\Support\Uri;
$url = Uri::of('https://example.com')->withPath('images/sample.jpg')->value();
```

#### RSS
RSS操作ヘルパー。RSSは提供されているけど余計なitemが多い場合にフィルタリングしたり、タイトルや説明を修正したりするのに使う。

```php
use Revolution\Feedable\Core\Support\RSS;

// itemが多い場合に別のページから解析した$linksのみに絞る
$xml = RSS::filterLinks($rss, $links);
```

```php
use Revolution\Feedable\Core\Support\RSS;
use DOMElement;

// NGワードで除外したり
$xml = RSS::each($rss, function (DOMElement $item) {
    $title = $item->getElementsByTagName('title')->item(0);
    if ($title && str_contains($title->textContent, 'NGワード')) {
        $item->parentNode->removeChild($item);
    }
});
```

ほとんど同じ`Atom`クラスもある。

## デプロイ
VervelへのデプロイはDBなしなら簡単だけどDBを使ってキャッシュが推奨。
AWS RDSでDBを用意するかSupabaseなどの無料DBを使う。

### SupabaseのDBを使う場合
Vercelの環境設定で

- `DB_URL`: SupabaseのPostgres接続URL。VercelではDirect connectionは使えないのでTransaction poolerのURLを指定する。
  Supabaseの**Connect**画面で以下のようなURLが表示される箇所を探す。
```
DB_URL=postgresql://postgres.*****:[YOUR-PASSWORD]@*****.pooler.supabase.com:6543/postgres
```

- `DB_CONNECTION`: `pgsql`

## カスタムドライバー

普通のLaravelのルーティングなのでフォークしたプロジェクトでカスタムドライバーを作るには `routes/web.php` にルートを追加するだけ。

composerパッケージとして作る場合はServiceProviderでルートを登録。

`Driver::about()`でドライバー情報を登録。対応サイトリストに表示するための情報なので登録しなくても使える。

### Playwright を使いたい場合の実装テクニック
- Playwrightを使う部分をartisanコマンドとして作成。コマンドをGitHub Actionsで定期実行。コマンドからVercel側にデータをポスト。
- Vercel側では受け取ったデータをCache::forever()で永遠にキャッシュ。表示時はキャッシュを表示するだけ。
- これならPlaywrightを動かすのはGitHub Actions側だけでVercel側にはPlaywrightをインストールする必要がない。

GitHub Actionsワークフローの例。
```yaml
      - uses: playwright-php/setup-playwright@main
        with:
          browsers: chrome

      - name: Install Playwright Dependencies
        run: vendor/bin/playwright-install --with-deps

      - name: Run Command
        run: php artisan your:playwright-command
```
https://github.com/playwright-php/setup-playwright
