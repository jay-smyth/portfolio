<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PublicPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_featured_projects(): void
    {
        $featured = Project::factory()->featured()->create(['title' => 'Featured Thing']);
        Project::factory()->create(['title' => 'Ordinary Thing']);
        Project::factory()->featured()->unpublished()->create(['title' => 'Secret Thing']);

        $this->get('/')
            ->assertOk()
            ->assertSee($featured->title)
            ->assertDontSee('Ordinary Thing')
            ->assertDontSee('Secret Thing');
    }

    public function test_about_page_renders(): void
    {
        $this->get('/about')->assertOk()->assertSee(config('portfolio.name'));
    }

    public function test_projects_page_lists_only_published_projects(): void
    {
        Project::factory()->create(['title' => 'Visible Project']);
        Project::factory()->unpublished()->create(['title' => 'Hidden Project']);

        $this->get('/projects')
            ->assertOk()
            ->assertSee('Visible Project')
            ->assertDontSee('Hidden Project');
    }

    public function test_projects_can_be_filtered_by_tag(): void
    {
        $laravel = Tag::create(['name' => 'Laravel']);
        $vue = Tag::create(['name' => 'Vue']);

        Project::factory()->create(['title' => 'Laravel Project'])->tags()->attach($laravel);
        Project::factory()->create(['title' => 'Vue Project'])->tags()->attach($vue);

        Volt::test('projects.index')
            ->set('tag', 'laravel')
            ->assertSee('Laravel Project')
            ->assertDontSee('Vue Project');

        $this->get('/projects?tag=vue')
            ->assertSee('Vue Project')
            ->assertDontSee('Laravel Project');
    }

    public function test_projects_can_be_searched(): void
    {
        Project::factory()->create(['title' => 'Booking Platform']);
        Project::factory()->create(['title' => 'Recipe App']);

        Volt::test('projects.index')
            ->set('search', 'booking')
            ->assertSee('Booking Platform')
            ->assertDontSee('Recipe App');
    }

    public function test_project_detail_page_renders_markdown(): void
    {
        $project = Project::factory()->create([
            'title' => 'Detail Project',
            'body' => "## The challenge\n\nSome **bold** text <script>alert(1)</script>",
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Detail Project')
            ->assertSee('<h2>The challenge</h2>', false)
            ->assertSee('<strong>bold</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_unpublished_project_returns_404_for_guests(): void
    {
        $project = Project::factory()->unpublished()->create();

        $this->get(route('projects.show', $project))->assertNotFound();
    }

    public function test_admin_can_preview_unpublished_project(): void
    {
        $project = Project::factory()->unpublished()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Draft preview');
    }

    public function test_slug_is_generated_and_unique(): void
    {
        $first = Project::factory()->create(['title' => 'Same Name']);
        $second = Project::factory()->create(['title' => 'Same Name']);

        $this->assertSame('same-name', $first->slug);
        $this->assertSame('same-name-2', $second->slug);
    }
}
