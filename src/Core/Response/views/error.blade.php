<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Feedable</title>

</head>
<body>
<h1>Error {{ $status }}</h1>
<h2>{{ $error }}</h2>
<p>{{ $message }}</p>
</body>
</html>
