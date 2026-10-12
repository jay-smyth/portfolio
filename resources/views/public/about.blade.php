<x-layouts.public title="About" :description="config('portfolio.bio')">
    <section class="mx-auto max-w-3xl px-4 pb-8 pt-16 sm:px-6 sm:pt-24">
        <h1 class="text-4xl font-semibold tracking-tight text-zinc-900 dark:text-white">About me</h1>
        <p class="mt-2 text-lg text-zinc-500 dark:text-zinc-400">
            {{ config('portfolio.headline') }}@if (config('portfolio.location')) · {{ config('portfolio.location') }}@endif
        </p>

        <div class="markdown mt-8">
            @foreach (preg_split('/\n\s*\n/', config('portfolio.bio')) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Skills &amp; tools</h2>
        <div class="mt-6 grid gap-6 sm:grid-cols-3">
            @foreach (config('portfolio.skills', []) as $group => $skills)
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $group }}</h3>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($skills as $skill)
                            <flux:badge size="sm">{{ $skill }}</flux:badge>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if (array_filter(config('portfolio.social', [])))
        <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Elsewhere</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                @foreach (array_filter(config('portfolio.social')) as $label => $url)
                    <flux:button size="sm" :href="$url" target="_blank" rel="noopener" icon-trailing="arrow-up-right">{{ $label }}</flux:button>
                @endforeach
            </div>
        </section>
    @endif

    <x-contact-cta />
</x-layouts.public>
