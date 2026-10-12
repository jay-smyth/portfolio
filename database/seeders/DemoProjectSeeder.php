<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class DemoProjectSeeder extends Seeder
{
    /**
     * Seed example portfolio projects so the site has content out of the box.
     * Edit or delete them from the admin panel.
     */
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Trailhead Outfitters Store',
                'summary' => 'A headless e-commerce storefront for an outdoor gear retailer, with real-time stock, faceted search and a sub-second checkout.',
                'client' => 'Trailhead Outfitters',
                'role' => 'Lead developer',
                'year' => 2026,
                'is_featured' => true,
                'tags' => ['Laravel', 'Livewire', 'Tailwind CSS', 'Stripe', 'MySQL'],
            ],
            [
                'title' => 'Clinic Booking Platform',
                'summary' => 'Multi-location appointment scheduling with SMS reminders, practitioner calendars and an admin reporting dashboard.',
                'client' => 'Harbour Health',
                'role' => 'Full-stack developer',
                'year' => 2025,
                'is_featured' => true,
                'tags' => ['Laravel', 'Vue', 'Tailwind CSS', 'MySQL'],
            ],
            [
                'title' => 'Fleet Tracker Dashboard',
                'summary' => 'Live vehicle tracking on an interactive map, with geofence alerts and route history for a regional logistics company.',
                'client' => 'Northline Logistics',
                'role' => 'Frontend developer',
                'year' => 2025,
                'is_featured' => true,
                'tags' => ['JavaScript', 'Vue', 'REST API'],
            ],
            [
                'title' => 'Community Grants Portal',
                'summary' => 'An accessible application and review workflow for a charitable foundation, replacing paper forms and spreadsheets.',
                'client' => 'Riverside Foundation',
                'role' => 'Lead developer',
                'year' => 2024,
                'is_featured' => false,
                'tags' => ['Laravel', 'Livewire', 'Accessibility'],
            ],
            [
                'title' => 'Recipe Planner PWA',
                'summary' => 'An offline-first progressive web app for weekly meal planning that generates grouped shopping lists.',
                'client' => null,
                'role' => 'Solo project',
                'year' => 2024,
                'is_featured' => false,
                'tags' => ['JavaScript', 'PWA', 'Tailwind CSS'],
                'repo_url' => 'https://github.com/example/recipe-planner',
            ],
            [
                'title' => 'Agency Marketing Site',
                'summary' => 'A fast, content-managed marketing site for a creative agency, scoring 100 on Lighthouse performance.',
                'client' => 'Brightside Studio',
                'role' => 'Full-stack developer',
                'year' => 2023,
                'is_featured' => false,
                'tags' => ['WordPress', 'PHP', 'Tailwind CSS'],
            ],
            [
                'title' => 'Inventory Sync Service',
                'summary' => 'A queue-driven integration service that keeps stock levels in sync across three marketplaces and an ERP.',
                'client' => 'Trailhead Outfitters',
                'role' => 'Backend developer',
                'year' => 2023,
                'is_featured' => false,
                'tags' => ['Laravel', 'REST API', 'MySQL', 'Docker'],
            ],
            [
                'title' => 'Open Source Form Builder',
                'summary' => 'A drag-and-drop form builder package for Laravel with conditional logic and exportable submissions.',
                'client' => null,
                'role' => 'Maintainer',
                'year' => 2022,
                'is_featured' => false,
                'tags' => ['Laravel', 'Livewire', 'Open Source'],
                'repo_url' => 'https://github.com/example/form-builder',
            ],
        ];

        foreach ($projects as $index => $data) {
            $tagNames = $data['tags'];
            unset($data['tags']);

            $project = Project::updateOrCreate(
                ['title' => $data['title']],
                [
                    ...$data,
                    'body' => $this->body($data['title']),
                    'live_url' => isset($data['repo_url']) ? null : 'https://example.com',
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );

            $tagIds = collect($tagNames)
                ->map(fn (string $name) => Tag::firstOrCreate(['name' => $name])->id);

            $project->tags()->sync($tagIds);
        }
    }

    private function body(string $title): string
    {
        return <<<MD
        ## The challenge

        The client needed **{$title}** to replace a slow, hard-to-maintain legacy system. The goals were to improve performance, simplify day-to-day admin work and give the team a solid foundation to build on.

        ## The approach

        - Ran discovery workshops to map the existing workflows and pain points
        - Designed a clean data model and a component-driven UI
        - Shipped in small, testable increments behind feature flags
        - Automated testing and deployment with a CI pipeline

        ## The result

        Page loads dropped by over 60%, support requests fell noticeably in the first quarter, and the in-house team can now ship new features with confidence.
        MD;
    }
}
