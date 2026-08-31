<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light dark" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-name" content="{{ config('app.name', 'Laravel') }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @fonts
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/inertia/app.tsx'])
    @inertiaHead
</head>
<body class="min-h-screen bg-background text-foreground">
    @inertia
</body>
</html>
