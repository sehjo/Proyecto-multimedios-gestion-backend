#!/bin/sh
set -e

# delete the existing storage link if it exists
# read the environment variables.
php artisan config:clear
php artisan cache:clear
php artisan config:cache

exec "$@"
