<?php

use App\Models\Project;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Projects')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function togglePublished(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['is_published' => ! $project->is_published]);
    }

    public function toggleFeatured(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['is_featured' => ! $project->is_featured]);
    }

    public function delete(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->delete();

        Flux::toast(variant: 'success', text: "Deleted “{$project->title}”.");
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'projects' => Project::query()
                ->with('tags')
                ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->ordered()
                ->paginate(15),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Projects</flux:heading>
            <flux:subheading>Manage the work shown on your portfolio.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.projects.create')" wire:navigate>New project</flux:button>
    </div>

    <div class="max-w-sm">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by title…" clearable />
    </div>

    <flux:table :paginate="$projects">
        <flux:table.columns>
            <flux:table.column>Project</flux:table.column>
            <flux:table.column>Year</flux:table.column>
            <flux:table.column>Order</flux:table.column>
            <flux:table.column>Published</flux:table.column>
            <flux:table.column>Featured</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($projects as $project)
                <flux:table.row :key="$project->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            @if ($project->coverUrl())
                                <img src="{{ $project->coverUrl() }}" alt="" class="size-10 rounded object-cover" />
                            @else
                                <div class="flex size-10 items-center justify-center rounded bg-zinc-100 text-xs font-semibold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">{{ $project->initials() }}</div>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $project->title }}</a>
                                <div class="truncate text-xs text-zinc-500">{{ $project->tags->pluck('name')->join(', ') }}</div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $project->year }}</flux:table.cell>
                    <flux:table.cell>{{ $project->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch :checked="$project->is_published" wire:click="togglePublished({{ $project->id }})" aria-label="Published" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch :checked="$project->is_featured" wire:click="toggleFeatured({{ $project->id }})" aria-label="Featured" />
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" aria-label="Actions" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="route('admin.projects.edit', $project)" wire:navigate>Edit</flux:menu.item>
                                <flux:menu.item icon="eye" :href="route('projects.show', $project)" target="_blank">View on site</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $project->id }})" wire:confirm="Delete “{{ $project->title }}”? This cannot be undone.">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-10 text-center text-zinc-500">
                        No projects yet. <flux:link :href="route('admin.projects.create')" wire:navigate>Add your first one</flux:link>.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
