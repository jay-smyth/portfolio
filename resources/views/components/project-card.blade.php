@props(['project'])

<article class="group relative flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:shadow-black/40">
    <div class="aspect-[16/10] overflow-hidden bg-zinc-100 dark:bg-zinc-800">
        @if ($project->coverUrl())
            <img src="{{ $project->coverUrl() }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105" />
        @else
            <div class="flex size-full items-center justify-center bg-gradient-to-br from-zinc-100 to-zinc-200 text-4xl font-semibold tracking-tight text-zinc-400 dark:from-zinc-800 dark:to-zinc-900 dark:text-zinc-600">
                {{ $project->initials() }}
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div class="flex items-baseline justify-between gap-3">
            <h3 class="font-semibold text-zinc-900 dark:text-white">
                <a href="{{ route('projects.show', $project) }}" wire:navigate class="after:absolute after:inset-0">
                    {{ $project->title }}
                </a>
            </h3>
            @if ($project->year)
                <span class="shrink-0 text-xs text-zinc-500 tabular-nums dark:text-zinc-400">{{ $project->year }}</span>
            @endif
        </div>

        <p class="line-clamp-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $project->summary }}</p>

        @if ($project->tags->isNotEmpty())
            <div class="mt-auto flex flex-wrap gap-1.5 pt-2">
                @foreach ($project->tags as $tag)
                    <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                @endforeach
            </div>
        @endif
    </div>
</article>
