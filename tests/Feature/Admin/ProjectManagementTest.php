<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guests_cannot_access_admin_pages(): void
    {
        $project = Project::factory()->create();

        $this->get(route('admin.projects.index'))->assertRedirect(route('login'));
        $this->get(route('admin.projects.create'))->assertRedirect(route('login'));
        $this->get(route('admin.projects.edit', $project))->assertRedirect(route('login'));
        $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
    }

    public function test_admin_pages_render(): void
    {
        $project = Project::factory()->create(['title' => 'Listed Project']);
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('admin.projects.index'))->assertOk()->assertSee('Listed Project');
        $this->get(route('admin.projects.create'))->assertOk();
        $this->get(route('admin.projects.edit', $project))->assertOk()->assertSee('Listed Project');
        $this->get(route('admin.tags.index'))->assertOk();
    }

    public function test_admin_can_create_a_project_with_images_and_tags(): void
    {
        $this->actingAs(User::factory()->create());
        $tag = Tag::create(['name' => 'Laravel']);

        Volt::test('admin.projects.form')
            ->set('form.title', 'Brand New Site')
            ->set('form.summary', 'A short summary.')
            ->set('form.body', '## Hello')
            ->set('form.year', '2026')
            ->set('form.live_url', 'https://example.com')
            ->set('form.is_featured', true)
            ->set('form.tags', [$tag->id])
            ->set('form.cover', UploadedFile::fake()->image('cover.jpg'))
            ->set('form.gallery', [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.projects.edit', 'brand-new-site'));

        $project = Project::where('slug', 'brand-new-site')->firstOrFail();

        $this->assertTrue($project->is_featured);
        $this->assertSame(2026, $project->year);
        $this->assertTrue($project->tags->contains($tag));
        $this->assertCount(2, $project->images);
        Storage::disk('public')->assertExists($project->cover_image);
        Storage::disk('public')->assertExists($project->images->first()->path);
    }

    public function test_admin_can_update_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['title' => 'Old Title']);

        Volt::test('admin.projects.form', ['project' => $project])
            ->assertSet('form.title', 'Old Title')
            ->set('form.title', 'New Title')
            ->set('form.is_published', false)
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertSame('New Title', $project->title);
        $this->assertFalse($project->is_published);
    }

    public function test_project_validation(): void
    {
        $this->actingAs(User::factory()->create());
        Project::factory()->create(['slug' => 'taken']);

        Volt::test('admin.projects.form')
            ->set('form.title', '')
            ->set('form.summary', '')
            ->set('form.slug', 'taken')
            ->set('form.live_url', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['form.title', 'form.summary', 'form.slug', 'form.live_url']);
    }

    public function test_admin_can_toggle_and_delete_projects(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['is_published' => true, 'is_featured' => false]);
        $image = $project->images()->create(['path' => UploadedFile::fake()->image('g.jpg')->store('projects/gallery', 'public')]);

        $component = Volt::test('admin.projects.index')
            ->call('togglePublished', $project->id)
            ->call('toggleFeatured', $project->id);

        $project->refresh();
        $this->assertFalse($project->is_published);
        $this->assertTrue($project->is_featured);

        $component->call('delete', $project->id);

        $this->assertModelMissing($project);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_admin_can_add_tags_inline_and_reorder_gallery(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $first = $project->images()->create(['path' => 'a.jpg', 'sort_order' => 0]);
        $second = $project->images()->create(['path' => 'b.jpg', 'sort_order' => 1]);

        Volt::test('admin.projects.form', ['project' => $project])
            ->set('newTag', 'Alpine.js')
            ->call('addTag')
            ->assertSet('newTag', '')
            ->call('moveImage', $second->id, -1);

        $this->assertDatabaseHas('tags', ['name' => 'Alpine.js', 'slug' => 'alpinejs']);
        $this->assertSame([$second->id, $first->id], $project->images()->pluck('id')->all());
    }

    public function test_admin_can_manage_tags(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('admin.tags.index')
            ->set('name', 'Livewire')
            ->call('create')
            ->assertHasNoErrors();

        $tag = Tag::where('name', 'Livewire')->firstOrFail();

        Volt::test('admin.tags.index')
            ->call('edit', $tag->id)
            ->set('editingName', 'Livewire 4')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('livewire-4', $tag->fresh()->slug);

        Volt::test('admin.tags.index')->call('delete', $tag->id);

        $this->assertModelMissing($tag);
    }
}
