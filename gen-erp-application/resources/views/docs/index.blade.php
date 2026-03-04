<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GenERP BD — Documentation</title>

    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    @vite(['resources/css/app.css', 'resources/css/docs.css', 'resources/js/docs.js'])
</head>
<body class="antialiased bg-base-bg text-base-text {{ app()->getLocale() === 'bn' ? 'font-bn' : 'font-sans' }}">
    <div id="docs-app"></div>
</body>
</html>

