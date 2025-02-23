<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Ensure test environment
$_SERVER['APP_ENV'] = 'test';

// Create test database if it doesn't exist
passthru(sprintf(
    'php "%s/bin/console" doctrine:database:create --env=test --if-not-exists 2>&1',
    dirname(__DIR__)
));

// Run migrations
passthru(sprintf(
    'php "%s/bin/console" doctrine:migrations:migrate --env=test --no-interaction --allow-no-migration 2>&1',
    dirname(__DIR__)
));

// Load test fixtures if they exist
if (file_exists(dirname(__DIR__).'/src/DataFixtures')) {
    passthru(sprintf(
        'php "%s/bin/console" doctrine:fixtures:load --env=test --no-interaction 2>&1',
        dirname(__DIR__)
    ));
}
