<?php

declare(strict_types=1);

it('returns json feed', function (): void {
    $response = $this->getJson('/comic-days/original.json');

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
                    'summary',
                    'date_published',
                    'image',
                ],
            ],
        ]);
});
