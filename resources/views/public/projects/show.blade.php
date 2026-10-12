<x-layouts.public :title="$project->title" :description="$project->summary" :image="$project->coverUrl()">
    <article>
        <header class="mx-auto max-w-4xl px-4 pt-12 sm:px-6 sm:pt-16">
            <flux:link :href="route('projects.index')" wire:navigate variant="subtle" class="text-sm">&larr; All projects</flux:link>

            @unless ($project->is_published)
                <flux:callout icon="eye-slash" variant="warning" class="mt-6" heading="Draft preview — this project is not published." />
            @endunless

            <h1 class="mt-6 text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">{{ $project->title }}</h1>
            <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">{{ $project->summary }}</p>

            <dl class="mt-8 grid grid-cols-2 gap-6 border-y border-zinc-200 py-6 text-sm sm:grid-cols-4 dark:border-zinc-800">
                @foreach (['Client' => $project->client, 'Role' => $project->role, 'Year' => $project->year] as $label => $value)
                    @if ($value)
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
                @if ($project->tags->isNotEmpty())
                    <div class="col-span-2 sm:col-span-1">
                        <dt class="text-zinc-500 dark:text-zinc-400">Built with</dt>
                        <dd class="mt-1 flex flex-wrap gap-1">
                            @foreach ($project->tags as $tag)
                                <a href="{{ route('projects.index', ['tag' => $tag->slug]) }}" wire:navigate>
                                    <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                                </a>
                            @endforeach
                        </dd>
                    </div>
                @endif
            </dl>

            @if ($project->live_url || $project->repo_url)
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($project->live_url)
                        <flux:button variant="primary" size="sm" :href="$project->live_url" target="_blank" rel="noopener" icon-trailing="arrow-up-right">Visit live site</flux:button>
                    @endif
                    @if ($project->repo_url)
                        <flux:button size="sm" :href="$project->repo_url" target="_blank" rel="noopener" icon="code-bracket">View source</flux:button>
                    @endif
                </div>
            @endif
        </header>

        @if ($project->coverUrl())
            <div class="mx-auto mt-12 max-w-5xl px-4 sm:px-6">
                <img src="{{ $project->coverUrl() }}" alt="{{ $project->title }} screenshot" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-800" />
            </div>
        @endif

        @if (filled($project->body))
            <div class="markdown mx-auto max-w-3xl px-4 py-12 sm:px-6">
                {!! $project->bodyHtml() !!}
            </div>
        @endif

        @if ($project->images->isNotEmpty())
            <section class="mx-auto max-w-5xl px-4 pb-12 sm:px-6" aria-label="Gallery">
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($project->images as $image)
                        <figure>
                            <a href="{{ $image->url() }}" target="_blank" rel="noopener">
                                <img src="{{ $image->url() }}" alt="{{ $image->caption ?? $project->title }}" loading="lazy" class="w-full rounded-xl border border-zinc-200 dark:border-zinc-800" />
                            </a>
                            @if ($image->caption)
                                <figcaption class="mt-2 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ $image->caption }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif
    </article>

    @if ($previous || $next)
        <nav class="mx-auto flex max-w-4xl justify-between gap-4 border-t border-zinc-200 px-4 py-8 text-sm sm:px-6 dark:border-zinc-800" aria-label="Project navigation">
            <div>
                @if ($previous)
                    <flux:link :href="route('projects.show', $previous)" wire:navigate>&larr; Previous project</flux:link>
                @endif
            </div>
            <div>
                @if ($next)
                    <flux:link :href="route('projects.show', $next)" wire:navigate>Next project &rarr;</flux:link>
                @endif
            </div>
        </nav>
    @endif

    <x-contact-cta />
</x-layouts.public>
