<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$projectRoot = dirname(__DIR__, 2);
$sampleDir = $projectRoot . '/samples';
$registry = require $sampleDir . '/sample-registry.php';

require $projectRoot . '/vendor/autoload.php';

$sample = $_GET['sample'] ?? '';

if (!is_string($sample) || $sample === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid sample ID.']);
    exit;
}

$sampleEntry = null;
foreach ($registry['samples'] as $candidate) {
    if ($candidate['id'] === $sample) {
        $sampleEntry = $candidate;
        break;
    }
}

if ($sampleEntry === null) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Sample is not registered for public discovery.']);
    exit;
}

if ($sampleEntry['distribution'] !== 'composer' || $sampleEntry['execution_mode'] !== 'odt') {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'This sample is not a self-contained packaged ODT example.']);
    exit;
}

$samplePath = $projectRoot . '/' . $sampleEntry['entry_point'];
$resolvedSamplePath = realpath($samplePath);
if (
    $resolvedSamplePath === false
    || !str_starts_with($resolvedSamplePath, realpath($sampleDir) . DIRECTORY_SEPARATOR)
    || !is_file($resolvedSamplePath)
) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Registered sample file is unavailable.']);
    exit;
}
$samplePath = $resolvedSamplePath;
$outputPath = $projectRoot . '/' . $sampleEntry['output_path'];

$previousWorkingDirectory = getcwd();

try {
    if (!chdir($projectRoot)) {
        throw new RuntimeException('Could not switch to project root.');
    }

    ob_start();
    require $samplePath;
    ob_end_clean();

    if (!is_file($outputPath)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Generated file not found.']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'file' => basename($outputPath),
    ]);
} catch (Throwable $exception) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Sample generation failed.']);
} finally {
    if (is_string($previousWorkingDirectory) && $previousWorkingDirectory !== '') {
        chdir($previousWorkingDirectory);
    }
}
