#!/bin/bash
set -e
cd ~/domains/arogikos.gr

# The default `php` on this host is 7.4; the app needs 8.3+. Pick a good one
# explicitly rather than failing halfway through a deploy.
PHP=$(command -v php8.3 || true)
for c in /opt/alt/php83/usr/bin/php /opt/alt/php84/usr/bin/php /usr/bin/php8.3; do
  [ -z "$PHP" ] && [ -x "$c" ] && PHP="$c"
done
[ -z "$PHP" ] && PHP=php

if ! "$PHP" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'; then
  echo "FATAL: need PHP 8.3+, found $("$PHP" -v | head -1)" >&2
  exit 1
fi
echo "PHP: $("$PHP" -v | head -1)"

# Drop Hostinger placeholder if present
if [ -f public_html/default.php ]; then rm -f public_html/default.php; fi

# Back up the SQLite database before migrating.
if [ -f laravel/database/database.sqlite ]; then
  cp laravel/database/database.sqlite \
     "laravel/database/database.sqlite.bak.$(date +%F-%H%M%S)"
  # Keep the five most recent backups.
  ls -1t laravel/database/database.sqlite.bak.* 2>/dev/null | tail -n +6 | xargs -r rm -f
fi

# Serve Laravel's public/ from the fixed docroot (public_html).
#
# NOTE: this used to be `cp -r laravel/public/. public_html/`, which was a bug.
# From the second deploy onward `laravel/public/storage` exists as a symlink to
# laravel/storage/app/public, and `cp -r` DEREFERENCES symlinks — so it copied
# the whole media library onto itself (public_html/storage resolves to the same
# directory) and aborted the deploy via `set -e`. rsync with --exclude keeps the
# symlinks out of it, and --delete prunes stale hashed Vite assets that `cp`
# would otherwise leave behind forever.
if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete \
        --exclude='storage' --exclude='hot' \
        laravel/public/ public_html/
else
  echo "WARN: rsync unavailable — falling back to cp (symlinks preserved via -a)"
  find laravel/public -mindepth 1 -maxdepth 1 ! -name storage ! -name hot \
    -exec cp -a {} public_html/ \;
fi

# Storage symlinks (artisan storage:link needs exec(), disabled on shared host)
ln -sfn ../laravel/storage/app/public public_html/storage
ln -sfn ../storage/app/public laravel/public/storage

# Repoint the front controller to ../laravel (app lives one level beside docroot)
sed -i "s#__DIR__\.'/\.\./#__DIR__.'/../laravel/#g" public_html/index.php

# Schema + caches. Must run AFTER the code is in place.
cd laravel
"$PHP" artisan migrate --force

# Only creates what is missing, so it never overwrites categories the client
# has renamed or withdrawn in the admin.
"$PHP" artisan db:seed --class=CategorySeeder --force

"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
cd ..

echo "OK_DEPLOY"
echo "== index.php __DIR__ refs =="
grep "__DIR__" public_html/index.php
echo "== docroot listing =="
ls -la public_html | head -20
