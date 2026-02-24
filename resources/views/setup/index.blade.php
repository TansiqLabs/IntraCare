<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('setup.title') }} — IntraCare HMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-950 text-gray-100 flex items-center justify-center p-4">

    <div class="w-full max-w-lg">
        {{-- Logo / Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-sky-500/10 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">{{ __('setup.welcome') }}</h1>
            <p class="text-gray-400 mt-2">{{ __('setup.description') }}</p>
        </div>

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/20">
                <ul class="text-sm text-red-400 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Setup Form --}}
        <form method="POST" action="{{ route('setup.store') }}" class="bg-gray-900 border border-gray-800 rounded-2xl p-6 space-y-5 shadow-xl">
            @csrf

            {{-- Hospital Name --}}
            <div>
                <label for="hospital_name" class="block text-sm font-medium text-gray-300 mb-1.5">
                    {{ __('setup.hospital_name') }}
                </label>
                <input
                    type="text"
                    id="hospital_name"
                    name="hospital_name"
                    value="{{ old('hospital_name', 'IntraCare HMS') }}"
                    required
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-white placeholder-gray-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition"
                    placeholder="e.g. City General Hospital"
                >
            </div>

            <hr class="border-gray-800">

            <p class="text-sm text-gray-400 font-medium">{{ __('setup.admin_account') }}</p>

            {{-- Admin Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-1.5">
                    {{ __('setup.admin_name') }}
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-white placeholder-gray-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition"
                    placeholder="System Administrator"
                >
            </div>

            {{-- Admin Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">
                    {{ __('setup.admin_email') }}
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-white placeholder-gray-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition"
                    placeholder="admin@hospital.local"
                >
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">
                    {{ __('setup.password') }}
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-white placeholder-gray-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition"
                    placeholder="••••••••"
                >
                <p class="mt-1 text-xs text-gray-500">{{ __('setup.password_hint') }}</p>
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1.5">
                    {{ __('setup.confirm_password') }}
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-white placeholder-gray-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition"
                    placeholder="••••••••"
                >
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="w-full rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition"
            >
                {{ __('setup.complete_setup') }}
            </button>
        </form>

        <p class="text-center text-xs text-gray-600 mt-6">
            IntraCare HMS — {{ __('setup.offline_note') }}
        </p>
    </div>

</body>
</html>
