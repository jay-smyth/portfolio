<?php

use App\Models\Project;
use App\Models\Tag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Projects')] class extends Component {
    #[Url(except: '')]
    public string $tag = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function filterBy(string $slug): void
    {
        $this->tag = $this->tag === $slug ? '' : $slug;
    }

    public function clearFilters(): void
    {
        $this->reset('tag', 'search');
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'projects' => Project::published()
                ->ordered()
                ->with('tags')
                ->when($this->tag, fn ($query) => $query->whereHas('tags', fn ($q) => $q->where('slug', $this->tag)))
                ->when($search, fn ($query) => $query->where(fn ($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%")))
                ->get(),
            'tags' => Tag::whereHas('projects', fn ($query) => $query->published())->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
    <header class="max-w-2xl">
        <h1 class="text-4xl font-semibold tracking-tight text-zinc-900 dark:text-white">Projects</h1>
        <p class="mt-3 text-lg text-zinc-600 dark:text-zinc-400">
            Websites, web apps and tools I've designed and built for clients and for fun.
        </p>
    </header>

    <div class="mt-10 flex flex-col gap-4 lg:flex-row lg:items-start">
        <div class="lg:w-72 lg:shrink-0">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search projects…" aria-label="Search projects" clearable />
        </div>

        @if ($tags->isNotEmpty())
            <div class="flex flex-wrap gap-2" role="group" aria-label="Filter by technology">
                @foreach ($tags as $t)
                    <button type="button" wire:click="filterBy('{{ $t->slug }}')" aria-pressed="{{ $tag === $t->slug ? 'true' : 'false' }}" @class([
                        'rounded-full border px-3 py-1.5 text-sm transition',
                        'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $tag === $t->slug,
                        'border-zinc-200 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-500' => $tag !== $t->slug,
                    ])>{{ $t->name }}</button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-10" wire:loading.class="opacity-60">
        @if ($projects->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                <p class="text-zinc-500 dark:text-zinc-400">No projects match your filters.</p>
                @if ($tag || $search)
                    <flux:button size="sm" class="mt-4" wire:click="clearFilters">Clear filters</flux:button>
                @endif
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    <x-project-card :project="$project" wire:key="project-{{ $project->id }}" />
                @endforeach
            </div>
        @endif
    </div>
</div>
