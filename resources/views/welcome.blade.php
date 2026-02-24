<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'IntraCare HMS') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-950 text-gray-100 flex items-center justify-center p-6">
    <div class="max-w-xl w-full bg-gray-900 border border-gray-800 rounded-2xl p-6">
        <h1 class="text-2xl font-bold text-white">{{ __('welcome.running') }}</h1>
        <p class="text-gray-400 mt-2">
            {{ __('welcome.offline_description') }}
        </p>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <a href="/admin" class="inline-flex justify-center rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500 transition">
                {{ __('welcome.go_to_admin') }}
            </a>
            <a href="/setup" class="inline-flex justify-center rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-semibold text-gray-100 hover:bg-gray-700 transition">
                {{ __('welcome.setup_first_install') }}
            </a>
        </div>

        <p class="text-xs text-gray-500 mt-6">
            {{ __('welcome.no_external_cdn') }}
        </p>
    </div>
</body>
</html>
