<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MapacheSSL</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white min-h-screen no-scrollbar">
    <div class="min-h-screen flex flex-col items-center justify-center px-6 py-12">
        <main class="w-full max-w-md animate-fade-in">
            @yield('content')
        </main>
    </div>
</body>
</html>
