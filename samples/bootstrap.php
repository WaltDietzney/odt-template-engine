<?php

declare(strict_types=1);

/**
 * Load the Composer autoloader for public samples in both supported layouts:
 * a repository checkout and an installed Composer dependency.
 */
$autoloadCandidates = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 4) . '/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;

        return;
    }
}

throw new RuntimeException(
    'Composer autoloader not found. Run the sample from a repository checkout '
    . 'with dependencies installed or from a Composer consumer project.'
);
