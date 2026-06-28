$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci || npm install
npm run build
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force
mkdir public
ln -s /mnt/volume-tor1-01/imprintfonts public/fonts

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
