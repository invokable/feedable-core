# Feedable core and built-in drivers - Project Guidelines

## Overview

RSSフィードを提供してないサイトからRSSを作るLaravelプロジェクト**Feedable**からコアヘルパーと内蔵ドライバーを分離したcomposerパッケージ。
新規LaravelプロジェクトにこのパッケージをインストールすればFeedableと同じ機能が使える。
フォークしたプロジェクトに独自ドライバーを追加して運用していくとフォーク元に追従するのが難しくなっていくのでパッケージにして更新しやすくする。

### スターターキット

https://github.com/invokable/feedable

## Technology Stack

- **Language**: PHP 8.3+
- **Framework**: Laravel 12.x+
- **Testing**: Pest PHP 4.x. Orchestra Testbench 10.x
- **Code Quality**: Laravel Pint (PSR-12)
- Vercel でデータベースなしでも動くようにする。`vercel-php`がまだPHP8.3しか使えないので8.3に制限。
- Playwrightを使ったデータ取得もできるけどVercelでは動かすのが難しい。Laravel Forge向け。

## Commands
```bash
composer run test          # Run all tests
composer run lint          # Format code with Pint

composer run serve        # Serve the application using the testbench/workbench local server
# ルートは`workbench/routes/web.php`。対応サイトリストを表示するview部分はスターターキットが担当しているのでここではjsonを表示しているだけ。
```

## Architecture
- `src/Core/`: Feedableのコアヘルパー。ドライバーを作りやすいように用意しているけど独自ドライバーでの使用は必須ではない。
- `src/Drivers/`: Feedableに内蔵されているドライバー群。実態はLaravelのルートなので独自ドライバーを個別のcomposerパッケージとして配布も可能。
  - `src/Drivers/Sample/SampleDriver.php`: ドライバー本体。コントローラーと同じ役割。複数のドライバーを同じディレクトリに配置してもいい。
  - `src/Drivers/Sample/SampleServiceProvider.php`: ドライバー情報とRouteを登録。
- `src/FeedableServiceProvider.php`: 内蔵ドライバーを自動登録。

## ドライバー
各サイトのフィード生成コードはドライバーとして分離。
入口のルーティングから出口のレスポンスまで全てドライバーで制御可能。
サイト毎に細かい調整が必要になることは分かっているので厳密なパターンは適用せず最大限の柔軟性を持たせる。

手動でドライバーを作って来て仕様が固まって来たのでAIでドライバーを増やして行く段階。
ドライバーを作る時は`create-driver`スキルを読み込む。

## スクレイピング
- LaravelのHTTPクライアント: これで取得できるなら一番簡単。
- Playwright(`playwright-php/playwright`, `revolution/salvager`): JavaScriptで動的に生成されるページを取得する場合に使う。Vercelでは動かせない。
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
Vervelへのデプロイはデータベースなしなら簡単だけどデータベースを使ってキャッシュが推奨。
AWS RDSでデータベースを用意するかSupabaseなどの無料データベースを使う。

### Supabaseのデータベースを使う場合
Vercelの環境設定で

- `DB_URL`: SupabaseのPostgres接続URL。VercelではDirect connectionは使えないのでTransaction poolerのURLを指定する。
  Supabaseの**Connect**画面で以下のようなURLが表示される箇所を探す。
```
DB_URL=postgresql://postgres.*****:[YOUR-PASSWORD]@*****.pooler.supabase.com:6543/postgres
```

- `DB_CONNECTION`: `pgsql`

### Laravel Cloudのデータベースを使う場合

public endpointを有効にすればVercelから接続できるもののLaravel Cloudのウェブサーバーを使ったほうがいい。

Laravel Forge+Laravel VPSのデータベースは外部から接続できないので使えない。

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
