# Feedable core and built-in drivers

[![tests](https://github.com/invokable/feedable-core/actions/workflows/tests.yml/badge.svg)](https://github.com/invokable/feedable-core/actions/workflows/tests.yml)
[![Maintainability](https://qlty.sh/gh/invokable/projects/feedable-core/maintainability.svg)](https://qlty.sh/gh/invokable/projects/feedable-core)
[![Code Coverage](https://qlty.sh/gh/invokable/projects/feedable-core/coverage.svg)](https://qlty.sh/gh/invokable/projects/feedable-core)

Feedable is an RSS feed generation service inspired by [RSSHub](https://github.com/DIYgod/RSSHub).
It allows you to create RSS feeds from websites that do not provide RSS feeds, enabling you to read them in your feed reader.

## Requirements
- PHP >= 8.4
- Laravel >= 12.x

## Installation

```shell
composer require revolution/feedable-core
```

## Request Submissions Welcome

This project is just getting started and we haven't decided which sites to support yet.
If you have a site you'd like us to support, please send the URL via our [request form](https://forms.gle/ipEVgmS8XZutKoXH7) or [discussion](https://github.com/orgs/invokable/discussions/25).

Some sites may be difficult to support, so we'll evaluate each request individually.

## Usage

### Sample Site
Since there are currently few supported sites, using the sample site is sufficient for testing.

https://feedable-rss.vercel.app/

### Fork and Deploy to Vercel

https://github.com/invokable/feedable

You can run it on the free plan without a database. You can also use a free database for caching.
This is the recommended normal usage.

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Finvokable%2Ffeedable&env=APP_NAME,APP_KEY&envDefaults=%7B%22APP_NAME%22%3A%22Feedable%22%7D&envDescription=APP_KEY%20can%20be%20generated%20using%20the%20artisan%20command.&envLink=https%3A%2F%2Fgithub.com%2Finvokable%2Flaravel-vercel-installer%3Ftab%3Dreadme-ov-file%23env&demo-title=Feedable&demo-url=https%3A%2F%2Ffeedable-rss.vercel.app%2F&skippable-integrations=1)

### Add Drivers and Deploy to Laravel Forge or Other Servers
If you want to use special drivers that require Playwright, please deploy it to a regular server such as Laravel Forge.
Drivers are simply Laravel routes, so you can freely add them to your forked project.

## Terms of Use

- Please use it only for personal private use. It is not intended for purposes other than reading in an RSS reader.

## License

MIT
