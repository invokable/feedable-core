<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Revolution\Feedable\Core\Driver;

Route::get('/', function () {
    return response(Driver::toPrettyJson())
        ->header('Content-Type', 'application/json');
});
