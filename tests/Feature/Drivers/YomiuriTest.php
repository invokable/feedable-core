<?php

declare(strict_types=1);

use Revolution\Feedable\Drivers\Yomiuri\YomiuriDriver;

it('returns json feed', function (): void {
    $response = $this->getJson('/yomiuri/news.json');

    $response->assertOk()
        ->assertJsonStructure([
            'version',
            'title',
            'home_page_url',
            'feed_url',
            'description',
            'items' => [
                [
                    'id',
                    'url',
                    'title',
                    'date_published',
                ],
            ],
        ]);
});

it('returns rss feed', function (): void {
    $response = $this->get('/yomiuri/news.rss');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
});

it('parses yomiuri news correctly', function (): void {
    $driver = new YomiuriDriver;

    $items = $driver->handle();

    expect($items)->toBeArray()->not->toBeEmpty();

    // 最初のアイテムの構造を確認
    $first = $items[0];
    expect($first->title)->toBeString()->not->toBeEmpty();
    expect($first->url)->toContain('yomiuri.co.jp');
    expect($first->date_published)->not->toBeNull();
});
