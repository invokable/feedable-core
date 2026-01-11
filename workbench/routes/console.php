<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Revolution\Feedable\Drivers\JumpPlus\JumpPlusDriver;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

Artisan::command('jump', function () {
    $this->comment('Fetching Shonen Jump Plus feed...');
    $jump = new JumpPlusDriver;
    dump($jump->handle());
    $this->comment('Done.');
})->purpose('Fetch Shonen Jump Plus feed');
