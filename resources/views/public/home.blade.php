<x-layouts.public>
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--color-zinc-100),_transparent_60%)] dark:bg-[radial-gradient(ellipse_at_top,_var(--color-zinc-800),_transparent_60%)]"></div>

        <div class="mx-auto max-w-6xl px-4 pb-20 pt-20 sm:px-6 sm:pt-28">
            @if (config('portfolio.available_for_work'))
                <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-zinc-200 px-3 py-1 text-xs font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    Available for new projects
                </p>
            @endif

            <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-zinc-900 sm:text-6xl dark:text-white">
                Hi, I'm {{ config('portfolio.name') }}.
                <span class="block text-zinc-500 dark:text-zinc-400">{{ config('portfolio.headline') }}.</span>
            </h1>

            <p class="mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                {{ config('portfolio.tagline') }}
            </p>

            <div class="mt-10 flex flex-wrap gap-3">
                <flux:button variant="primary" icon-trailing="arrow-right" :href="route('projects.index')" wire:navigate>
                    View my work
                </flux:button>
                <flux:button icon="envelope" href="mailto:{{ config('portfolio.email') }}">
                    Get in touch
                </flux:button>
            </div>
        </div>
    </section>

    {{-- Featured work --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="mb-10 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Selected work</h2>
                <p class="mt-1 text-zinc-500 dark:text-zinc-400">A few projects I'm particularly proud of.</p>
            </div>
            @if ($projectCount > $featured->count())
                <flux:link :href="route('projects.index')" wire:navigate class="shrink-0 text-sm">
                    All {{ $projectCount }} projects &rarr;
                </flux:link>
            @endif
        </div>

        @if ($featured->isEmpty())
            <p class="rounded-xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-700">
                Projects are on their way — check back soon.
            </p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $project)
                    <x-project-card :project="$project" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- About / skills --}}
    <section class="border-y border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950/40">
        <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">About me</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ config('portfolio.bio') }}</p>
                <flux:link :href="route('about')" wire:navigate class="mt-6 inline-block text-sm">More about me &rarr;</flux:link>
            </div>

            <dl class="grid gap-6 sm:grid-cols-3">
                @foreach (config('portfolio.skills', []) as $group => $skills)
                    <div>
                        <dt class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $group }}</dt>
                        <dd class="mt-3">
                            <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                                @foreach ($skills as $skill)
                                    <li>{{ $skill }}</li>
                                @endforeach
                            </ul>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <x-contact-cta />
</x-layouts.public>
