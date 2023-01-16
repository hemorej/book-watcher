<?php
// replace host, deploy_user with values
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'steidl');

// Project repository
set('repository', 'git@github.com:hemorej/book-watcher.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

set('deploy_path', '/home/jerome_a_/jerome-arfouche.com/steidl');

// Shared files/dirs between deploys 
set('writable_mode', 'chmod');
set('writable_chmod_mode', 777);
set('writable_chmod_recursive', true);
add('shared_files', ['steidl.sqlite']);
add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', [
    'storage',
    'public',
    'bootstrap/cache',
]);

set('keep_releases', 10);
set('allow_anonymous_stats', false);

// Hosts

host('jerome-arfouche.com')
    ->set('deploy_path', get('deploy_path'))
    ->user('jerome_a_')
    ->set('branch', 'master');
    
task('deploy:vendor', function(){
    run('cd {{release_path}} && /usr/local/php74/bin/php /home/jerome_a_/.php/composer/composer install --no-dev --no-interaction --no-suggest --optimize-autoloader');
});

// Tasks
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendor',
    'deploy:unlock'
]);

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.
before('deploy:symlink', 'artisan:migrate');
