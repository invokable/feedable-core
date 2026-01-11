<?php

declare(strict_types=1);

it('returns rss', function (): void {
    $response = $this->get('/nintendo/direct.rss');

    $response->assertOk()
        ->assertSee('<rss', escape: false);
});

it('returns json feed', function (): void {
    $response = $this->getJson('/nintendo/ir/news.json');

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
                    'external_url',
                    'title',
                    'summary',
                    'date_published',
                    'tags',
                ],
            ],
        ]);
});
