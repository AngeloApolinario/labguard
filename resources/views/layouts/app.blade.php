<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LabGuard') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-800 min-h-screen overflow-x-hidden">
    <x-banner />

    <div class="flex min-h-screen relative w-full overflow-x-hidden">
        {{-- Navigation Menu Sidebar --}}
        @livewire('navigation-menu')

        {{-- Main Content Container --}}
        <div class="flex-1 min-w-0 w-full lg:pl-64 pt-14 lg:pt-0 flex flex-col transition-all duration-300">

            @if (isset($header))
            <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
            @endif

            <main class="p-4 sm:p-6 lg:p-8 flex-1 w-full max-w-full overflow-x-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />

    @stack('modals')
    @livewireScripts
</body>

</html>