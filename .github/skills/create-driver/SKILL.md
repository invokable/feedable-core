---
name: create-driver
description: Guide for creating a built-in driver for Feedable. Use this when you want to add support for a new website by implementing a driver that fetches and formats the site's content into an RSS feed.
---

このパッケージ用の内蔵ドライバーを作る時はこのガイドに従ってください。

## 最初に対象サイトを調査

LaravelのHTTPクライアントで取得できて単純なhtmlの解析だけで対応できるサイトならドライバーを作成します。

JavaScriptで動的に生成されるサイトやログインが必要なサイトは対象外です。中断してユーザーの指示に従ってください。

## ドライバー生成コマンド

```shell
vendor/bin/testbench make:driver Sample
```

`src/Drivers/Sample/SampleDriver.php`, `src/Drivers/Sample/SampleServiceProvider.php`, `tests/Feature/Drivers/SampleTest.php`が生成されます。
`Sample`は対象サイトに合わせて指定してください。

## 実装

既存の他のドライバーを参考にしてください。
基本はLaravelのHTTPクライアントでhtmlを取得、PHP8.3用のDOMDocumentで解析、`handle()`はFeedItemの配列を返す形になります。

内蔵ドライバーはほとんどResponseFactoryでRSSとJSON Feedに対応しているので`__invoke()`はテンプレートから少し変更するだけで完成です。

### ServiceProvider

- Format enumのルートバインディングはwebミドルウェアがないと無効なので`Route::middleware('web')`は残す。
- ドライバーのServiceProviderはFeedableServiceProviderで自動登録されるので追加の作業は不要。
