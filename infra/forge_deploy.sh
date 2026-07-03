$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$PNPM_PATH install --frozen-lockfile
$PNPM_PATH run build
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache

ln -s /mnt/volume-tor1-01/imprintfonts public/fonts

$ACTIVATE_RELEASE()

$RESTART_QUEUES()