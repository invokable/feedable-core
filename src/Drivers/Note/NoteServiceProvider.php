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
            id: 'note-index',
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
            Route::get('index.{format?}', NoteIndexDriver::class);
        });

        // agent-browserとCloud provider(Browserbase)でVercelでも動かせたので元のNoteIndexDriverに切り替え。キャッシュ方式はサンプルとして残す。
        // Browserbaseには無料枠が月1時間分あるけどそれ以上に使いたいような場合はGitHub ActionsでChromiumを直接使う。

        // GitHub Actionsからこのルートにポスト。
        // Route::prefix('note')->group(function () {
        //     Route::post('post', NotePostDriver::class);
        // });
        // キャッシュからフィードを返す。
        // Route::middleware('web')->prefix('note')->group(function () {
        //     Route::get('index.{format?}', NoteCacheDriver::class);
        // });
        //
        // GitHub Actionsはこれを参考に設定。
        // https://github.com/invokable/feedable/blob/main/.github/workflows/note.yml
        // GitHub Actionsで実行するコマンドはこれ
        // https://github.com/invokable/feedable/blob/main/app/Console/Commands/NoteCommand.php
        // 実際にはもう動かしてないので後から見たら参考にならないかもしれないので注意。
    }
}
