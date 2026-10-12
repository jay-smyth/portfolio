<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ProjectForm extends Form
{
    public ?Project $project = null;

    public string $title = '';

    public string $slug = '';

    public string $summary = '';

    public string $body = '';

    public string $client = '';

    public string $role = '';

    public string $year = '';

    public string $live_url = '';

    public string $repo_url = '';

    public bool $is_featured = false;

    public bool $is_published = true;

    public int $sort_order = 0;

    /** @var list<int|string> */
    public array $tags = [];

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public bool $remove_cover = false;

    /** @var list<TemporaryUploadedFile> */
    public array $gallery = [];

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('projects', 'slug')->ignore($this->project?->id)],
            'summary' => ['required', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'client' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'between:1990,'.(date('Y') + 1)],
            'live_url' => ['nullable', 'url', 'max:255'],
            'repo_url' => ['nullable', 'url', 'max:255'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'tags' => ['array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'gallery' => ['array'],
            'gallery.*' => ['image', 'max:5120'],
        ];
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;

        $this->fill([
            ...$project->only(['title', 'slug', 'summary', 'is_featured', 'is_published', 'sort_order']),
            'body' => $project->body ?? '',
            'client' => $project->client ?? '',
            'role' => $project->role ?? '',
            'year' => (string) ($project->year ?? ''),
            'live_url' => $project->live_url ?? '',
            'repo_url' => $project->repo_url ?? '',
            'tags' => $project->tags->modelKeys(),
        ]);
    }

    public function save(): Project
    {
        $this->validate();

        $project = $this->project ?? new Project;

        $project->fill([
            'title' => $this->title,
            'slug' => $this->slug ?: null,
            'summary' => $this->summary,
            'body' => $this->body ?: null,
            'client' => $this->client ?: null,
            'role' => $this->role ?: null,
            'year' => $this->year !== '' ? (int) $this->year : null,
            'live_url' => $this->live_url ?: null,
            'repo_url' => $this->repo_url ?: null,
            'is_featured' => $this->is_featured,
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
        ]);

        if (($this->remove_cover || $this->cover) && $project->cover_image) {
            Storage::disk('public')->delete($project->cover_image);
            $project->cover_image = null;
        }

        if ($this->cover) {
            $project->cover_image = $this->cover->store('projects', 'public');
        }

        $project->save();
        $project->tags()->sync($this->tags);

        $nextOrder = (int) $project->images()->max('sort_order');
        foreach ($this->gallery as $file) {
            $project->images()->create([
                'path' => $file->store('projects/gallery', 'public'),
                'sort_order' => ++$nextOrder,
            ]);
        }

        $this->reset('cover', 'remove_cover', 'gallery');
        $this->setProject($project->fresh('tags'));

        return $project;
    }
}
