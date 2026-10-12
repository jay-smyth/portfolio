# ASA Services — Developer Portfolio

A Laravel 12 + Livewire (Volt + Flux) site for showcasing web development projects, with a private admin panel for managing them.

## Features

- **Public site:** home page with featured work, a searchable and tag-filterable projects page, project write-ups in Markdown with a gallery, and an about page. Supports dark mode and per-page SEO/Open Graph tags.
- **Admin panel** at `/dashboard`: create and edit projects (cover image, gallery uploads with captions and ordering, tags, publish/feature toggles, sort order) and manage tags. Admins can preview unpublished projects on the public site.
- **Single admin account:** public registration is disabled.

## Getting started (Laravel Sail)

Requires Docker. Run these from WSL:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed   # creates the admin user + demo projects
npm install && npm run build               # or `npm run dev` for hot reload
```

Then open http://localhost. To reach the admin panel, log in at http://localhost/login with `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env` (default `admin@example.com` / `password`). **Change these before deploying.**

> Tip: `alias sail='./vendor/bin/sail'`

## Configuration

Profile details (name, headline, bio, email, social links, availability) come from `PORTFOLIO_*` variables in `.env`. Skills are set in [config/portfolio.php](config/portfolio.php).

The demo projects come from [DemoProjectSeeder](database/seeders/DemoProjectSeeder.php). Delete them from the admin panel once your own work is added.

## Tests

```bash
./vendor/bin/sail artisan test
```
