<?php

// Run against a disposable database; never migrate or truncate application data.
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if ($app->configurationIsCached()) {
    throw new RuntimeException('Remove cached configuration before running isolated tests.');
}
$connection = config('database.connections.mysql');
$database = 'reembolsos_workflow_test_' . bin2hex(random_bytes(6));
$pdo = new PDO(
    'mysql:host=' . $connection['host'] . ';port=' . $connection['port'],
    $connection['username'], $connection['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $database,
        'DB_HOST' => $connection['host'], 'DB_PORT' => $connection['port'],
        'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password'],
        'DB_URL' => '', 'MAIL_MAILER' => 'array', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
        putenv($key . '=' . $value);
    }
    $args = array_slice($argv, 1);
    $command = array_merge([PHP_BINARY, dirname(__DIR__) . '/vendor/phpunit/phpunit/phpunit'], $args);
    $process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, dirname(__DIR__));
    $exit = is_resource($process) ? proc_close($process) : 1;
} finally {
    $pdo->exec('DROP DATABASE `' . $database . '`');
}
exit($exit);
