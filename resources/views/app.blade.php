<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Molka Moden') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Inter:wght@100..900&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @vite('resources/js/app.js')
    @inertiaHead
</head>

<body class="font-sans antialiased bg-[#fdfdfc] text-[#1b1b18]">
    @inertia
</body>

</html>