<?php

declare(strict_types=1);

it('returns json feed', function (): void {
    $response = $this->getJson('/asahi/news.json');

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
    $response = $this->get('/asahi/news.rss?compact');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
});
