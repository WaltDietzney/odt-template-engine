<?php

namespace OdtTemplateEngine\Utils;

/**
 * Tracks importer-created files until process shutdown.
 */
final class TemporaryAssetRegistry
{
    /** @var array<string, true> */
    private static array $assets = [];

    private static bool $shutdownRegistered = false;

    public static function register(string $path): void
    {
        self::$assets[$path] = true;

        if (!self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'cleanup']);
            self::$shutdownRegistered = true;
        }
    }

    public static function cleanup(): void
    {
        foreach (array_keys(self::$assets) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        self::$assets = [];
    }
}
