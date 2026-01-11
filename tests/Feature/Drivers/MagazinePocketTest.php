<?php

declare(strict_types=1);

it('returns json feed', function (): void {
    $response = $this->getJson('/shonenmagazine/pocket.json');

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
                    'image',
                ],
            ],
        ]);
});
