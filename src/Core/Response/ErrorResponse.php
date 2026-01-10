<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Response;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

readonly class ErrorResponse implements Responsable
{
    /**
     * Create a new error response instance.
     *
     * @param  string|null  $error  Short error title
     * @param  string|null  $message  Detailed error message
     * @param  int  $status  HTTP status code
     */
    public function __construct(
        protected ?string $error = null,
        protected ?string $message = null,
        protected int $status = 500,
    ) {
        //
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): Response
    {
        $html = Blade::render(File::get(__DIR__.'/views/error.blade.php'), [
            'error' => $this->error,
            'message' => $this->message,
            'status' => $this->status,
        ]);

        return response($html, $this->status)
            ->header('Content-Type', 'text/html');
    }
}
