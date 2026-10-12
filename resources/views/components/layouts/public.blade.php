@props([
    'title' => null,
    'description' => null,
    'image' => null,
])

@php
    $siteName = config('portfolio.name');
    $pageTitle = $title ? "{$title} · {$siteName}" : "{$siteName} — " . config('portfolio.headline');
    $description ??= config('portfolio.tagline');
    // route name => [label, pattern that marks the link active]
    $nav = [
        'home' => ['Home', 'home'],
        'projects.index' => ['Projects', 'projects.*'],
        'about' => ['About', 'about'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $description }}" />

        <meta property="og:type" content="website" />
        <meta property="og:title" content="{{ $pageTitle }}" />
        <meta property="og:description" content="{{ $description }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        @if ($image)
            <meta property="og:image" content="{{ $image }}" />
            <meta name="twitter:card" content="summary_large_image" />
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @fluxAppearance
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-200">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:text-sm dark:focus:bg-zinc-800">
            Skip to content
        </a>

        <header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="font-semibold tracking-tight text-zinc-900 dark:text-white" wire:navigate>
                    {{ $siteName }}
                </a>

                <nav class="ml-auto hidden items-center gap-1 sm:flex" aria-label="Main">
                    @foreach ($nav as $route => [$label, $pattern])
                        <a href="{{ route($route) }}" wire:navigate @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'text-zinc-900 dark:text-white' => request()->routeIs($pattern),
                            'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => ! request()->routeIs($pattern),
                        ]) @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                    <flux:button size="sm" variant="primary" href="mailto:{{ config('portfolio.email') }}" class="ml-2">Get in touch</flux:button>
                </nav>

                <flux:button x-data x-on:click="$flux.dark = ! $flux.dark" icon="moon" variant="subtle" size="sm" aria-label="Toggle dark mode" class="ml-auto sm:ml-0" />

                <flux:dropdown position="bottom" align="end" class="sm:hidden">
                    <flux:button icon="bars-3" variant="subtle" size="sm" aria-label="Open menu" />
                    <flux:menu>
                        @foreach ($nav as $route => [$label])
                            <flux:menu.item :href="route($route)" wire:navigate>{{ $label }}</flux:menu.item>
                        @endforeach
                        <flux:menu.separator />
                        <flux:menu.item icon="envelope" href="mailto:{{ config('portfolio.email') }}">Get in touch</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </header>

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 text-sm text-zinc-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:text-zinc-400">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                <div class="flex flex-wrap items-center gap-4">
                    <a href="mailto:{{ config('portfolio.email') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ config('portfolio.email') }}</a>
                    @foreach (array_filter(config('portfolio.social', [])) as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="hover:text-zinc-900 dark:hover:text-white">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </footer>

        @livewireScripts
        @fluxScripts
    </body>
</html>
