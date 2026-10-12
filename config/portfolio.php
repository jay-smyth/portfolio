<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portfolio Owner
    |--------------------------------------------------------------------------
    |
    | Public profile details shown on the home and about pages. Override any
    | of these in your .env file without touching the code.
    |
    */

    'name' => env('PORTFOLIO_NAME', 'Alex Developer'),

    'headline' => env('PORTFOLIO_HEADLINE', 'Full-stack web developer'),

    'tagline' => env('PORTFOLIO_TAGLINE', 'I design and build fast, accessible websites and web applications with Laravel, Livewire and modern JavaScript.'),

    'bio' => env('PORTFOLIO_BIO', "I'm a web developer who enjoys turning complex problems into simple, well-crafted products. I work across the stack — from database design and APIs to polished, responsive interfaces — and care about performance, accessibility and maintainable code."),

    'location' => env('PORTFOLIO_LOCATION'),

    'email' => env('PORTFOLIO_EMAIL', 'hello@example.com'),

    'available_for_work' => (bool) env('PORTFOLIO_AVAILABLE', true),

    'social' => [
        'GitHub' => env('PORTFOLIO_GITHUB'),
        'LinkedIn' => env('PORTFOLIO_LINKEDIN'),
        'X' => env('PORTFOLIO_X'),
    ],

    'skills' => [
        'Backend' => ['PHP', 'Laravel', 'MySQL', 'REST APIs', 'Queues & Jobs'],
        'Frontend' => ['Livewire', 'Alpine.js', 'Tailwind CSS', 'JavaScript', 'Vue'],
        'Tooling' => ['Git', 'Docker', 'CI/CD', 'Testing', 'Linux'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Account
    |--------------------------------------------------------------------------
    |
    | Credentials for the single admin user created by the database seeder.
    | Public registration is disabled, so this is how you get in.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
