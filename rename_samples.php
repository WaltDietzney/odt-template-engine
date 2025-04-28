<?php
// rename_samples.php

$samplesDir = __DIR__ . '/samples/';
$templatesDir = $samplesDir . 'templates/';

function renameFiles($dir, $pattern, $replacement)
{
    foreach (glob($dir . $pattern) as $filePath) {
        $fileName = basename($filePath);
        $newFileName = preg_replace('/^example_/', $replacement, $fileName);
        $newPath = $dir . $newFileName;

        if ($newPath !== $filePath) {
            rename($filePath, $newPath);
            echo "Renamed: $fileName → $newFileName\n";
        }
    }
}

renameFiles($samplesDir, 'example_*.php', 'sample_');
renameFiles($templatesDir, 'example_*.odt', 'template_');

echo "✅ Done renaming files!\n";
