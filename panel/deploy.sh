#!/bin/bash
# Pull the latest code and update the live panel. Run on the server: bash ~/brands-cms/panel/deploy.sh
set -e
cd "$(dirname "$0")"

git pull --ff-only

# No global Composer on the host: fall back to ~/composer.phar
if command -v composer >/dev/null 2>&1; then COMPOSER="composer"; else COMPOSER="php $HOME/composer.phar"; fi
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force
php artisan optimize

echo "Deployed."
