<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\PublicPageController;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Public portfolio
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('about', [PublicPageController::class, 'about'])->name('about');
Volt::route('projects', 'projects.index')->name('projects.index');
Route::get('projects/{project}', [PublicPageController::class, 'project'])->name('projects.show');

// Blog guides (static Markdown in resources/content/blog)
Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{guide}', [BlogController::class, 'guide'])->name('blog.guide');
Route::get('blog/{guide}/{chapter}', [BlogController::class, 'chapter'])->name('blog.chapter');

// Admin
Route::get('dashboard', fn () => view('dashboard', [
    'stats' => [
        'Projects' => Project::count(),
        'Published' => Project::published()->count(),
        'Featured' => Project::featured()->count(),
        'Tags' => Tag::count(),
    ],
    'recent' => Project::latest('updated_at')->take(5)->get(),
]))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('projects', 'admin.projects.index')->name('projects.index');
    Volt::route('projects/create', 'admin.projects.form')->name('projects.create');
    Volt::route('projects/{project}/edit', 'admin.projects.form')->name('projects.edit');
    Volt::route('tags', 'admin.tags.index')->name('tags.index');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
