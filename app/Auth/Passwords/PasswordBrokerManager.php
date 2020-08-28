<?php

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager as BasePasswordBrokerManager;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PasswordBrokerManager extends BasePasswordBrokerManager
{
    /**
     * Create a token repository instance based on the given configuration.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $connection = $config['connection'] ?? null;

        return new DatabaseTokenRepository(
            $this->app['db']->connection($connection),
            $this->app['hash'],
            $config['table'],
            $key,
            $config['expire'],
            $config['throttle'] ?? 0
        );
    }

    protected function resolve($name)
    {
        $config = $this->getConfig($name);
        if (is_null($config)) {
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        return new PasswordBroker(
            $this->createTokenRepository($config),
            $this->app['auth']->createUserProvider($config['provider'])
        );
    }
}
