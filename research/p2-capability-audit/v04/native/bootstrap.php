<?php

declare(strict_types=1);

// Use the installed test runner, but resolve project classes from this worktree.
$root = dirname(__DIR__, 4);
spl_autoload_register(static function (string $class) use ($root): void {
    foreach (['OdtTemplateEngine\\Tests\\' => '/tests/', 'OdtTemplateEngine\\' => '/src/'] as $prefix => $directory) {
        if (str_starts_with($class, $prefix)) {
            $path = $root . $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($path)) {
                require $path;
            }
            return;
        }
    }
}, true, true);
