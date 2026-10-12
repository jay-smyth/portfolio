<?php

use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\Tag;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public ProjectForm $form;

    public string $newTag = '';

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->form->setProject($project);
        }
    }

    public function save(): void
    {
        $creating = ! $this->form->project;
        $project = $this->form->save();

        if ($creating) {
            session()->flash('status', 'Project created.');
            $this->redirectRoute('admin.projects.edit', $project, navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: 'Project saved.');
    }

    public function addTag(): void
    {
        $this->validate(['newTag' => ['required', 'string', 'max:50']]);

        $tag = Tag::firstOrCreate(['name' => trim($this->newTag)]);

        if (! in_array($tag->id, array_map('intval', $this->form->tags), true)) {
            $this->form->tags[] = $tag->id;
        }

        $this->reset('newTag');
    }

    public function removeGalleryUpload(int $index): void
    {
        unset($this->form->gallery[$index]);
        $this->form->gallery = array_values($this->form->gallery);
    }

    public function deleteImage(int $id): void
    {
        $this->galleryImage($id)->delete();
    }

    public function updateCaption(int $id, string $caption): void
    {
        $this->galleryImage($id)->update(['caption' => trim($caption) ?: null]);
    }

    public function moveImage(int $id, int $direction): void
    {
        $ids = $this->form->project->images()->pluck('id')->all();
        $from = array_search($id, $ids, true);
        $to = $from === false ? null : $from + $direction;

        if ($to === null || ! isset($ids[$to])) {
            return;
        }

        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

        foreach ($ids as $order => $imageId) {
            ProjectImage::whereKey($imageId)->update(['sort_order' => $order]);
        }
    }

    protected function galleryImage(int $id): ProjectImage
    {
        return $this->form->project->images()->findOrFail($id);
    }

    public function with(): array
    {
        return [
            'allTags' => Tag::orderBy('name')->get(),
            'images' => $this->form->project?->images()->get() ?? collect(),
            'editing' => (bool) $this->form->project,
        ];
    }

    public function rendering($view): void
    {
        $view->title($this->form->project ? "Edit {$this->form->project->title}" : 'New project');
    }
}; ?>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:link :href="route('admin.projects.index')" wire:navigate variant="subtle" class="text-sm">&larr; Projects</flux:link>
            <flux:heading size="xl" level="1" class="mt-1">{{ $editing ? $form->title : 'New project' }}</flux:heading>
        </div>
        @if ($editing)
            <flux:button icon="eye" size="sm" :href="route('projects.show', $form->project)" target="_blank">View on site</flux:button>
        @endif
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" :heading="session('status')" />
    @endif

    <form wire:submit="save" class="grid gap-8 lg:grid-cols-3">
        {{-- Main details --}}
        <div class="flex flex-col gap-6 lg:col-span-2">
            <flux:input wire:model="form.title" label="Title" required />

            <flux:input wire:model="form.slug" label="URL slug" description="Leave blank to generate from the title." />

            <flux:textarea wire:model="form.summary" label="Summary" rows="3" required description="One or two sentences shown on project cards and in search results." />

            <flux:textarea wire:model="form.body" label="Write-up" rows="14" description="Markdown supported: ## headings, **bold**, - lists, [links](https://…)." />

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="form.client" label="Client" />
                <flux:input wire:model="form.role" label="Your role" />
                <flux:input wire:model="form.year" label="Year" type="number" min="1990" max="{{ date('Y') + 1 }}" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="form.live_url" label="Live URL" type="url" placeholder="https://" />
                <flux:input wire:model="form.repo_url" label="Repository URL" type="url" placeholder="https://" />
            </div>

            {{-- Gallery --}}
            <flux:fieldset>
                <flux:legend>Gallery</flux:legend>

                @if ($images->isNotEmpty())
                    <ul class="mb-4 space-y-3">
                        @foreach ($images as $image)
                            <li wire:key="image-{{ $image->id }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                                <img src="{{ $image->url() }}" alt="" class="h-14 w-20 rounded object-cover" />
                                <flux:input size="sm" class="flex-1" placeholder="Caption (optional)" value="{{ $image->caption }}"
                                    x-on:change="$wire.updateCaption({{ $image->id }}, $event.target.value)" aria-label="Caption" />
                                <flux:button size="sm" variant="ghost" icon="arrow-up" wire:click="moveImage({{ $image->id }}, -1)" :disabled="$loop->first" aria-label="Move up" />
                                <flux:button size="sm" variant="ghost" icon="arrow-down" wire:click="moveImage({{ $image->id }}, 1)" :disabled="$loop->last" aria-label="Move down" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteImage({{ $image->id }})" wire:confirm="Delete this image?" aria-label="Delete image" />
                            </li>
                        @endforeach
                    </ul>
                @endif

                <flux:input type="file" wire:model="form.gallery" multiple accept="image/*" label="Add images" description="PNG, JPG or WebP, up to 5 MB each. Uploaded on save." />
                <flux:error name="form.gallery.*" />

                @if ($form->gallery)
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($form->gallery as $i => $upload)
                            <div class="relative" wire:key="upload-{{ $i }}">
                                @if (method_exists($upload, 'isPreviewable') && $upload->isPreviewable())
                                    <img src="{{ $upload->temporaryUrl() }}" alt="" class="h-20 w-28 rounded object-cover" />
                                @endif
                                <button type="button" wire:click="removeGalleryUpload({{ $i }})" class="absolute -right-2 -top-2 rounded-full bg-zinc-900 p-0.5 text-white" aria-label="Remove">
                                    <flux:icon.x-mark variant="micro" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:fieldset>
        </div>

        {{-- Sidebar --}}
        <div class="flex flex-col gap-6">
            <flux:card class="flex flex-col gap-4">
                <flux:switch wire:model="form.is_published" label="Published" description="Visible on the public site." />
                <flux:switch wire:model="form.is_featured" label="Featured" description="Shown on the home page." />
                <flux:input wire:model="form.sort_order" label="Sort order" type="number" min="0" description="Lower numbers appear first." />

                <flux:button type="submit" variant="primary" class="w-full">
                    <span wire:loading.remove wire:target="save">{{ $editing ? 'Save changes' : 'Create project' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </flux:button>
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <flux:heading>Cover image</flux:heading>

                @if ($form->cover && method_exists($form->cover, 'isPreviewable') && $form->cover->isPreviewable())
                    <img src="{{ $form->cover->temporaryUrl() }}" alt="" class="aspect-[16/10] w-full rounded object-cover" />
                @elseif ($form->project?->coverUrl() && ! $form->remove_cover)
                    <img src="{{ $form->project->coverUrl() }}" alt="" class="aspect-[16/10] w-full rounded object-cover" />
                @endif

                <flux:input type="file" wire:model="form.cover" accept="image/*" aria-label="Cover image" />
                <div wire:loading wire:target="form.cover" class="text-sm text-zinc-500">Uploading…</div>

                @if ($form->project?->cover_image && ! $form->cover)
                    <flux:checkbox wire:model.live="form.remove_cover" label="Remove current cover" />
                @endif
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <flux:checkbox.group wire:model="form.tags" label="Tech & tags">
                    @forelse ($allTags as $tag)
                        <flux:checkbox :value="$tag->id" :label="$tag->name" wire:key="tag-{{ $tag->id }}" />
                    @empty
                        <flux:text>No tags yet — add one below.</flux:text>
                    @endforelse
                </flux:checkbox.group>

                <div class="flex gap-2">
                    <flux:input wire:model="newTag" wire:keydown.enter.prevent="addTag" size="sm" placeholder="New tag" aria-label="New tag" />
                    <flux:button size="sm" wire:click="addTag" icon="plus">Add</flux:button>
                </div>
                <flux:error name="newTag" />
            </flux:card>
        </div>
    </form>
</div>
