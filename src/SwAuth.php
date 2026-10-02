<?php

namespace SwAuth;

use Dotenv\Dotenv;
use PDO;
use SwAuth\SwAuthContainer;

final class SwAuth
{
    private static bool $booted = false;

    public static function boot(string $rootPath): void
    {
        if (self::$booted) {
            return;
        }

        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        self::$booted = true;
    }

    public static function init(PDO $pdo): SwAuthContainer
    {
        return new SwAuthContainer($pdo);
    }

    public static function version(): string
    {
        return '1.0.0';
    }
}