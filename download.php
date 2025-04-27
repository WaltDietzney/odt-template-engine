<?php
// download.php

$file = $_GET['file'] ?? null;
$filePath = __DIR__ . '/' . $file;

if (!$file || !file_exists($filePath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.oasis.opendocument.text');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;
