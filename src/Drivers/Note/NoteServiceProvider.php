<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Note;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;
use Revolution\Feedable\Core\Enums\Format;
use Revolution\Feedable\Core\Enums\Timezone;

class NoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'node-index',
            name: 'note 注目記事',
            url: 'https://note.com/',
            tags: ['blog'],
            description: <<<'MARKDOWN'
ブラウザを使うドライバーのサンプル。noteトップページの注目記事。

`/note/index.rss`や`/note/index.json`でフォーマットを指定できます。
MARKDOWN,
            example: '/note/index',
            format: [Format::RSS->value, Format::JSON->value],
            language: 'ja',
            timezone: Timezone::AsiaTokyo->value,
            browser: true,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->prefix('note')->group(function () {
            Route::get('index.{format?}', NoteCacheDriver::class);
        });

        // agent-browserがまだVercelで動かないのでGitHub Actionsで実行してここで受け取ってキャッシュから返す方式
        Route::prefix('note')->group(function () {
            Route::post('post', NotePostDriver::class);
        });

        // NoteIndexDriverは直接agent-browserを使う版。Vercelで使えるようになったら切り替え。
    }
}
