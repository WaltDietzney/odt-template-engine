<?php
// fix_samples.php

$sampleDir = __DIR__ . '/samples/';
$files = glob($sampleDir . 'sample_*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Entferne jede Zeile, die den Autoloader einbindet
    $content = preg_replace(
        '/^\s*require\s+[\'"]\.\.\/vendor\/autoload\.php[\'"];\s*$/m',
        '',
        $content
    );

    file_put_contents($file, $content);
    echo "✅ Fixed autoload in: " . basename($file) . PHP_EOL;
}

echo "\n🎉 All samples cleaned up successfully!\n";
