#!/bin/sh
set -e

# Run as www-data (the user php-fpm's workers run as) so everything these
# commands write under storage/ (logs, cached views, ...) is owned by the
# same user that serves requests afterward, not root.
su www-data -s /bin/sh -c "php artisan migrate --force"
su www-data -s /bin/sh -c "php artisan config:cache"
su www-data -s /bin/sh -c "php artisan route:cache"
su www-data -s /bin/sh -c "php artisan view:cache"

php-fpm -D
cron
exec nginx -g 'daemon off;'
