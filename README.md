# Feedable

[![tests](https://github.com/invokable/feedable-core/actions/workflows/tests.yml/badge.svg)](https://github.com/invokable/feedable-core/actions/workflows/tests.yml)
[![Maintainability](https://qlty.sh/gh/invokable/projects/feedable-core/maintainability.svg)](https://qlty.sh/gh/invokable/projects/feedable-core)
[![Code Coverage](https://qlty.sh/gh/invokable/projects/feedable-core/coverage.svg)](https://qlty.sh/gh/invokable/projects/feedable-core)

Feedableは [RSSHub](https://github.com/DIYgod/RSSHub) を参考にしたRSSフィード生成サービスです。RSSHubは日本向けサイトの登録が少ないので別途開発。

RSSフィードを提供してないサイトからRSSを作りフィードリーダーで読めるようにします。

## Requirements
- PHP >= 8.3
- Laravel >= 12.x

## リクエスト募集中

このプロジェクトは始まったばかりでどのサイトに対応するかも決まってない段階です。
対応して欲しいサイトがあれば [リクエストフォーム](https://forms.gle/ipEVgmS8XZutKoXH7) か [Discussion](https://github.com/orgs/invokable/discussions/25) からURLを送ってください。

参考として
- [RSSHubの日本語サイトリスト](./docs/routes-jp.md)
- [RSSHubの英語サイトリスト](./docs/routes-en.md)

RSSHubにあるサイトでもないサイトでも構いません。

サイトによっては対応が難しいので個別に判断して決めます。

## Request Submissions Welcome

This project is just getting started and we haven't decided which sites to support yet.
If you have a site you'd like us to support, please send the URL via our [request form](https://forms.gle/ipEVgmS8XZutKoXH7) or [discussion](https://github.com/orgs/invokable/discussions/25).

For reference:
- [RSSHub Japanese sites list](./docs/routes-jp.md)
- [RSSHub English sites list](./docs/routes-en.md)

Sites can be from RSSHub or completely new ones.

Some sites may be difficult to support, so we'll evaluate each request individually.

## 使い方

### サンプルサイトを使う

現状は対応サイトが少ないのでサンプルサイトを使って試すだけで十分です。

https://feedable-rss.vercel.app/

### フォークしてVercelにデプロイする

https://github.com/invokable/feedable

無料プラン・データベースなしでも動かせます。無料データベースでキャッシュ対応にもできます。  
これが推奨する普通の使い方。

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Finvokable%2Ffeedable&env=APP_NAME,APP_KEY&envDefaults=%7B%22APP_NAME%22%3A%22Feedable%22%7D&envDescription=APP_KEY%20can%20be%20generated%20using%20the%20artisan%20command.&envLink=https%3A%2F%2Fgithub.com%2Finvokable%2Flaravel-vercel-installer%3Ftab%3Dreadme-ov-file%23env&demo-title=Feedable&demo-url=https%3A%2F%2Ffeedable-rss.vercel.app%2F&skippable-integrations=1)

### ドライバーを追加してLaravel Forgeや他のサーバーにデプロイする

Playwrightが必要になるような特殊なドライバーを使いたい場合はLaravel Forgeなどで普通のサーバーにデプロイしてください。  
ドライバーは単なるLaravelのルーティングなのフォークしたプロジェクトに自由に追加できます。

## 利用規約
- 個人による私的利用の範囲内でのみ使用してください。RSSリーダーで読む以外の目的での利用は想定していません。

## License

MIT
