<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function home(): View
    {
        $featured = Project::published()->featured()->ordered()->with('tags')->take(6)->get();

        // Fall back to the most recent work if nothing has been featured yet.
        if ($featured->isEmpty()) {
            $featured = Project::published()->ordered()->with('tags')->take(3)->get();
        }

        return view('public.home', [
            'featured' => $featured,
            'projectCount' => Project::published()->count(),
        ]);
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function project(Project $project): View
    {
        // Admins can preview unpublished work; everyone else gets a 404.
        abort_unless($project->is_published || auth()->check(), 404);

        $project->load(['tags', 'images']);

        $siblings = Project::published()->ordered()->pluck('slug');
        $position = $siblings->search($project->slug);

        return view('public.projects.show', [
            'project' => $project,
            'previous' => $position !== false && $position > 0 ? $siblings[$position - 1] : null,
            'next' => $position !== false ? $siblings->get($position + 1) : null,
        ]);
    }
}
