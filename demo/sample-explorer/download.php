<?php

declare(strict_types=1);

$outputDir = realpath(dirname(__DIR__, 2) . '/samples/output');
$file = $_GET['file'] ?? '';

if ($outputDir === false || !is_string($file) || $file === '' || basename($file) !== $file) {
    http_response_code(404);
    exit('File not found.');
}

if (!preg_match('/^[A-Za-z0-9._-]+\.odt$/', $file)) {
    http_response_code(404);
    exit('File not found.');
}

$filePath = realpath($outputDir . DIRECTORY_SEPARATOR . $file);

if (
    $filePath === false
    || !str_starts_with($filePath, $outputDir . DIRECTORY_SEPARATOR)
    || !is_file($filePath)
) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.oasis.opendocument.text');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-store');

readfile($filePath);
exit;
