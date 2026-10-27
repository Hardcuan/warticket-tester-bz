#!/bin/sh
set -eu

php artisan migrate --force

exec /usr/bin/supervisord -n -c /etc/supervisord.conf
