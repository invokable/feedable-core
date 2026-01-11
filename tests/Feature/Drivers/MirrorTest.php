<?php

declare(strict_types=1);

it('returns rss', function (): void {
    $response = $this->getJson('/mirror?rss=https://feedable-rss.vercel.app/nintendo/direct');

    $response->assertOk()
        ->assertSee('<rss', escape: false);
});
