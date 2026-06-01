<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'AI Notes Workspace')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased">
        <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-5 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 border-b border-zinc-200 pb-5 lg:flex-row lg:items-center lg:justify-between">
                <a href="{{ route('notes.index') }}" class="block">
                    <p class="text-sm font-semibold text-teal-700">AI Notes Workspace</p>
                    <h1 class="mt-1 text-2xl font-bold text-zinc-950 sm:text-3xl">Notes Management System</h1>
                </a>

                <nav class="flex flex-wrap justify-start gap-2 lg:justify-end">
                    <a href="{{ route('notes.index') }}" @class([
                        'rounded-md px-3 py-2 text-sm font-semibold transition',
                        'bg-zinc-950 text-white' => request()->routeIs('notes.index') || request()->is('/'),
                        'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' => ! request()->routeIs('notes.index') && ! request()->is('/'),
                    ])>Notes list</a>
                    <a href="{{ route('notes.create') }}" @class([
                        'rounded-md px-3 py-2 text-sm font-semibold transition',
                        'bg-zinc-950 text-white' => request()->routeIs('notes.create'),
                        'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' => ! request()->routeIs('notes.create'),
                    ])>Create note</a>
                </nav>
            </header>

            @if (session('status'))
                <div class="mt-5 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-900">
                    {{ session('status') }}
                </div>
            @endif

            <main class="flex-1 py-6">
                @yield('content')
            </main>
        </div>
    </body>
</html>
