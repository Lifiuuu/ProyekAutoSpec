<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Main Dashboard - {{ config('app.name', 'AutoSpec') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-semute-bg-primary text-semute-text">
        <!-- React Root Mount Point -->
        <div id="root"></div>
    </body>
</html>
