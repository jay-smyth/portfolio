# ASA Services — Developer Portfolio

A Laravel 12 + Livewire (Volt + Flux) site for showcasing web development projects, with a private admin panel for managing them.

## Features

- **Public site:** home page with featured work, a searchable and tag-filterable projects page, project write-ups in Markdown with a gallery, and an about page. Supports dark mode and per-page SEO/Open Graph tags.
- **Admin panel** at `/dashboard`: create and edit projects (cover image, gallery uploads with captions and ordering, tags, publish/feature toggles, sort order) and manage tags. Admins can preview unpublished projects on the public site.
- **Single admin account:** public registration is disabled.

## Getting started (Laravel Sail)

You only need Docker; PHP and Composer run inside containers. Run these from WSL, Linux or macOS:

```bash
git clone https://github.com/jay-smyth/portfolio.git && cd portfolio
cp .env.example .env

# Install PHP dependencies without a local PHP install
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:latest \
    composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed   # creates the admin user + demo projects
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run build            # or `npm run dev` for hot reload
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

GitHub Actions runs the test suite (on SQLite) and a Pint code-style check on every push and pull request to `main`.

## Deploying

On a production server:

- Set `APP_ENV=production`, `APP_DEBUG=false`, the real `APP_URL`, your database credentials and a strong `ADMIN_PASSWORD`.
- Run `composer install --no-dev --optimize-autoloader`, `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed --force` (first deploy only) and `php artisan storage:link`.
- Run `npm ci && npm run build`, then `php artisan optimize`.

Uploaded images are stored on the `public` disk (`storage/app/public`), so keep that directory on persistent storage.
