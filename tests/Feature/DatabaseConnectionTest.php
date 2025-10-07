<?php

test('db:ping fails when SSL CA path is missing', function () {
    config()->set('database.connections.mysql', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'options' => [
            \PDO::MYSQL_ATTR_SSL_CA => '/tmp/missing-ca.pem',
        ],
    ]);

    $this->artisan('db:ping', ['--connection' => 'mysql'])
        ->expectsOutputToContain('CA file was not found')
        ->assertExitCode(1);
});

test('db:ping succeeds when CA exists', function () {
    $caPath = tempnam(sys_get_temp_dir(), 'tidb-ca-');
    file_put_contents($caPath, 'dummy');

    config()->set('database.connections.mysql', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'options' => [
            \PDO::MYSQL_ATTR_SSL_CA => $caPath,
        ],
    ]);

    $this->artisan('db:ping', ['--connection' => 'mysql', '--verbose' => true])
        ->expectsOutputToContain('SSL: ENABLED')
        ->assertExitCode(0);

    @unlink($caPath);
});
