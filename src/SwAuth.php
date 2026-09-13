<?php
namespace SwAuth;

use Dotenv\Dotenv;


final class SwAuth
{
    private static bool $booted = false;

    public static function boot(string $rootPath): void
    {
        if(self::$booted) {
            return;
        }

        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->load();

        self::$booted = true;

    }

    public static function version(): string
    {
        return '1.0.0';
    }
}