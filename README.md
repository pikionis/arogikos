# Arogi — Website

Public website of **Arogi**, a centre for learning support, counselling and career
guidance on Kos. Laravel 13 + Blade + Vite + Tailwind, server-rendered, **Greek only**,
GDPR-friendly (self-hosted fonts).

## Stack

- **Laravel 13** (PHP 8.3+), server-rendered **Blade**, not a SPA.
- **Vite + Tailwind v4**. The design system (`resources/css/arogi.css` +
  `arogi-motion.css`) was carried over unchanged from the approved design; the
  colours are also defined as Tailwind tokens (`resources/css/app.css`).
- **Self-hosted fonts** (`@fontsource` Nunito + Source Sans 3): no call to Google
  Fonts (a GDPR requirement). Note: Nunito has no Greek subset, so the Greek
  headings render in a system font, exactly as in the approved design; the Greek
  body text is in Source Sans 3 (Greek subset).
- **spatie/laravel-medialibrary** (conversions `thumb` 400px / `web` 1600px webp),
  **spatie/laravel-sluggable**, **spatie/laravel-sitemap**.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # demo content (SQLite, no configuration needed)
php artisan storage:link       # for media uploads
npm install
npm run dev                    # or: npm run build
php artisan serve
```

In development, emails are written to `storage/logs/laravel.log` (`MAIL_MAILER=log`).

## Structure and principles

- **Pages / routes** (Greek slugs): `/`, `/sxetika-me-emas`, `/ypiresies`,
  `/blog`, `/blog/{slug}`, `/ekdiloseis`, `/gallery`, `/epikoinonia`, `/sitemap.xml`.
- **Settings-driven**: phone numbers, email, address, opening hours, social links,
  Google Analytics ID, the form's recipient address all come from the `settings`
  table (helper `setting('key')`). No contact or branding details are hard-coded.
- **Contact form**: server-side validation, honeypot anti-spam, stored as a
  `ContactMessage`, emailed to the recipient in Settings, and a success
  animation (PRG + flash). A mail failure does not break the experience (try/catch + log).
- **SEO**: per-page `<title>` and meta description from the models' SEO fields, Open
  Graph, `sitemap.xml` (cached 24h), Google Analytics only when `ga_id` is set.
- **Animations**: all as in the design; they respect `prefers-reduced-motion`.
- **Performance**: eager loading in lists (no N+1), responsive and lazy images
  through medialibrary conversions.

## Architecture: temporary models → cms-core

The admin panel (`/admin`) and the content models will come from the reusable
package **`pikionis/cms-core`** (Filament). Because the package was still being
built, the site was built with **temporary local models** in `app/Models/Cms/`,
designed so that the switch is clean:

- The same class and field names, the same table names, the same media collections
  (`cover`/`photo`/`image`) and conversions (`thumb`/`web`) as cms-core.
- The controllers resolve the models through `cms_model('article')`, with a map in
  `config/cms-models.php`. **The views never name a class.**

### Switching to cms-core (when it is ready)

1. Add the path repository and require the package:
   ```jsonc
   // composer.json
   "repositories": [{ "type": "path", "url": "../cms-core" }]
   ```
   ```bash
   composer require pikionis/cms-core:@dev
   php artisan cms:install      # migrations, roles, first admin, (optional) seed
   ```
2. Point `config/cms-models.php` to `Pikionis\Cms\Models\*`.
3. Delete the local `app/Models/Cms/*` and `database/migrations/2026_06_04_15*`
   (cms-core brings its own). Run it on a fresh database.
4. In production: switch composer from the path repository to VCS with a tagged version
   (`"pikionis/cms-core": "^0.1"`).

No change to routes, controllers or views is needed.

## Production / deployment (arogikos.gr — Hostinger, EU)

The server is a git checkout of `main`, with a read-only deploy key. Each deploy:

```bash
cd ~/domains/arogikos.gr/laravel && git pull
cd .. && bash laravel/deploy_arogi.sh
```

Two commands, not one: `deploy_arogi.sh` lives inside the repository, so a
`git pull` **inside** the script itself would replace the file while bash is
still reading it.

`deploy_arogi.sh` does the rest: a backup of the SQLite database, `migrate`,
seeding the categories, syncing `public/` to `public_html/`, symlinks and
production caches.

### What to know about this server

- **`php` is 7.4**, and the application needs 8.3+. The script finds
  `/opt/alt/php83/usr/bin/php` by itself; for manual commands use it
  explicitly, otherwise `artisan` does not even start.
- **There is no Node.** That is why `public/build` is committed to the repository:
  run `npm run build` locally and commit the generated files. Without
  them the site has no styles and every `/admin` route returns 500.
- **`composer install` needs the same php83 binary:**
  `/opt/alt/php83/usr/bin/php $(command -v composer) install --no-dev --optimize-autoloader`
- The database is SQLite, and **`deploy_arogi.sh` keeps a backup before every
  migrate** (`~/backups/`, the last 5).

### .env in production

`APP_ENV=production`, `APP_DEBUG=false`, `APP_TIMEZONE=Europe/Athens`
(otherwise publishing times are 3 hours off), `CACHE_STORE=file` and
`QUEUE_CONNECTION=sync` (an SQLite cache gains nothing, and no queue worker
runs), `SESSION_SECURE_COOKIE=true` behind TLS.

### Email

`MAIL_MAILER=sendmail` through Hostinger's local MTA, with no mailbox
credentials. Tested: sending works and the exim queue empties.

**Pending in DNS:** `arogikos.gr` has no SPF record, so messages go out
but risk being treated as spam. Add:

```
TXT  @  v=spf1 include:_spf.mail.hostinger.com ~all
```

`info@arogi.gr` (in Settings) is on another domain, on Microsoft 365, and it is
the **receiving** address. We do not send from it through Hostinger: the SPF
of arogi.gr allows only Outlook.

### First account

```bash
/opt/alt/php83/usr/bin/php artisan cms:make-owner <email> --name="<name>"
```

Without `--password` it sends a link to set the password; with `--password` it
sets it directly. There is no public sign-up: the other members join by
invitation from the panel.

## Design source

The `arogi/` folder holds the original static HTML/CSS/JS prototype, kept as a
reference (it is not served). Any UI change is made in Blade and `resources`.
