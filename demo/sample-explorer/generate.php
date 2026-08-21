<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$projectRoot = dirname(__DIR__, 2);
$sampleDir = $projectRoot . '/samples';
$outputDir = $sampleDir . '/output';

$sample = $_GET['sample'] ?? '';

if (!is_string($sample) || !preg_match('/^sample_[A-Za-z0-9_-]+$/', $sample)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid sample name.']);
    exit;
}

$samplePath = $sampleDir . '/' . $sample . '.php';

if (!is_file($samplePath)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Sample file not found.']);
    exit;
}

try {
    ob_start();
    require $samplePath;
    ob_end_clean();

    $sampleBase = preg_replace('/^sample_/', '', $sample);
    $outputFile = $outputDir . '/output_' . $sampleBase . '.odt';

    if (!is_file($outputFile)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Generated file not found.']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'file' => basename($outputFile),
    ]);
} catch (Throwable $exception) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Sample generation failed.']);
}
