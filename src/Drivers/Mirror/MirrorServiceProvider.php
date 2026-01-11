<?php

declare(strict_types=1);

namespace Revolution\Feedable\Drivers\Mirror;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Revolution\Feedable\Core\Driver;

class MirrorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Driver::about(
            id: 'mirror',
            name: 'ミラー',
            description: '入力されたRSSをそのまま返します。',
            example: '/mirror?rss=https://',
        );
    }

    public function boot(): void
    {
        /**
         * /mirror?rss=https:// で入力されたRSSをそのまま返す最小のドライバー
         */
        Route::prefix('mirror')->group(function () {
            Route::get('/', function (Request $request) {
                $request->validate([
                    'rss' => 'required|url',
                ]);

                $body = cache()->flexible(
                    'mirror-'.md5($request->input('rss')),
                    [now()->plus(hours: 1), now()->plus(hours: 2)],
                    fn () => Http::get($request->input('rss'))->body(),
                );

                return response($body)
                    ->header('Content-Type', 'application/xml');
            });
        });
    }
}
