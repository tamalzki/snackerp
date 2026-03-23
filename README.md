# Snackerp

Laravel inventory, consignment, sales, and reporting app (PHP 8.2+, Laravel 12).

## Local setup

```bash
cp .env.example .env
php artisan key:generate
composer install
npm install && npm run build   # or npm run dev while developing
php artisan migrate
# optional: php artisan db:seed
php artisan storage:link
```

## Push to GitHub

1. Create a **new empty repository** on [GitHub](https://github.com/new) (no README/license if you already have them here).
2. In this project folder:

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git branch -M main
git push -u origin main
```

Use SSH if you prefer: `git@github.com:YOUR_USERNAME/YOUR_REPO.git`

**Never commit** `.env` — it stays local / on the server only.

## Deploy on DigitalOcean (later)

Typical options:

- **App Platform**: Connect the GitHub repo, set build command (`composer install --no-dev && npm ci && npm run build`), run command (`php artisan migrate --force`), add env vars from `.env`.
- **Droplet**: Install PHP, Nginx, MySQL/MariaDB, Composer, Node; clone repo; `composer install --no-dev`; `npm ci && npm run build`; point web root to `public/`; configure `.env` and queue/cron if needed.

Official Laravel deployment docs: <https://laravel.com/docs/deployment>

## License

MIT (same as Laravel skeleton unless you change it).
