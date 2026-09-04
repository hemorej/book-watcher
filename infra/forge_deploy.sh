$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

chown -R forge:forge storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$PNPM_PATH install --frozen-lockfile
$PNPM_PATH run build
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache

ln -s /mnt/$VOLUME_NAME/imprintfonts public/fonts

$ACTIVATE_RELEASE()

$RESTART_QUEUES()

if [ -f /etc/php/$FORGE_PHP_VERSION/fpm/php-fpm.conf ]; then
    ( flock -w 10 9 || exit 1
        echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
fi