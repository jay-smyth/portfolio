<?php

use App\Models\Tag;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] #[Title('Tags')] class extends Component {
    public string $name = '';

    public ?int $editingId = null;

    public string $editingName = '';

    public function create(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:50', 'unique:tags,name']]);

        Tag::create(['name' => trim($this->name)]);

        $this->reset('name');
    }

    public function edit(int $id): void
    {
        $this->editingId = $id;
        $this->editingName = Tag::findOrFail($id)->name;
        $this->resetValidation();
    }

    public function update(): void
    {
        $this->validate([
            'editingName' => ['required', 'string', 'max:50', Rule::unique('tags', 'name')->ignore($this->editingId)],
        ], attributes: ['editingName' => 'name']);

        Tag::findOrFail($this->editingId)->update(['name' => trim($this->editingName)]);

        $this->reset('editingId', 'editingName');
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'editingName');
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        Flux::toast(variant: 'success', text: "Deleted tag “{$tag->name}”.");
    }

    public function with(): array
    {
        return ['tags' => Tag::withCount('projects')->orderBy('name')->get()];
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Tags</flux:heading>
        <flux:subheading>Technologies and categories visitors can filter projects by.</flux:subheading>
    </div>

    <form wire:submit="create" class="flex items-start gap-2">
        <flux:input wire:model="name" placeholder="e.g. Laravel" aria-label="Tag name" class="flex-1" />
        <flux:button type="submit" variant="primary" icon="plus">Add tag</flux:button>
    </form>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Projects</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($tags as $tag)
                <flux:table.row :key="$tag->id">
                    <flux:table.cell>
                        @if ($editingId === $tag->id)
                            <form wire:submit="update" class="flex items-start gap-2">
                                <flux:input wire:model="editingName" size="sm" aria-label="Tag name" autofocus x-on:keydown.escape="$wire.cancel()" />
                                <flux:button type="submit" size="sm" variant="primary">Save</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                            </form>
                        @else
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $tag->name }}</span>
                            <span class="ml-2 text-xs text-zinc-500">{{ $tag->slug }}</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $tag->projects_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @if ($editingId !== $tag->id)
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $tag->id }})" aria-label="Rename" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $tag->id }})"
                                wire:confirm="Delete “{{ $tag->name }}”? It will be removed from {{ $tag->projects_count }} project(s)." aria-label="Delete" />
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" class="py-8 text-center text-zinc-500">No tags yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
