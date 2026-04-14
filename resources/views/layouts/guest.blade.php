<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema de Reembolsos') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/">
                    <x-application-logo class="w-auto h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            <footer class="mt-6 mb-4 border-t border-gray-200 dark:border-gray-700/50 pt-6">
                <a href="https://indi-lab.com/" target="_blank" rel="noopener noreferrer"
                   class="flex flex-col items-center gap-1.5 opacity-85 hover:opacity-100 transition-opacity duration-300 group">
                    <span class="text-[10px] font-semibold tracking-[0.2em] text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors">
                        Desarrollado por
                    </span>
                    <img src="{{ asset('images/INDI Lab - Logo Emergencia.png') }}"
                         alt="INDI Lab"
                         class="h-16 w-auto dark:invert dark:brightness-200"
                    />
                </a>
            </footer>
        </div>
    </body>
</html>
