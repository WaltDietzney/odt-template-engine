<?php
// generate.php
ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

header('Content-Type: application/json');

if (!isset($_GET['sample'])) {
    echo json_encode(['status' => 'error', 'message' => 'No sample file provided.']);
    exit;
}

$sampleFile = basename($_GET['sample']); // <<<<< WICHTIG: GET statt POST
$samplePath = __DIR__ . '/samples/' . $sampleFile . '.php'; // <<<<< Dateiendung ergänzen

if (!file_exists($samplePath)) {
    echo json_encode(['status' => 'error', 'message' => 'Sample file not found.']);
    exit;
}

// Try to execute the sample script
try {
    ob_start();
    require $samplePath;
    ob_end_clean();

    // Find output file (assumes sample_sampleName.php => output_sampleName.odt)
    $sampleBase = preg_replace('/^sample_/', '', pathinfo($sampleFile, PATHINFO_FILENAME));
    $outputFile = __DIR__ . '/samples/output/output_' . $sampleBase . '.odt';

    if (!file_exists($outputFile)) {
        echo json_encode(['status' => 'error', 'message' => 'Generated file not found.']);
        exit;
    }

    $downloadUrl = 'samples/output/' . basename($outputFile);

    echo json_encode([
        'status' => 'success',
        'url' => $downloadUrl, // <--- NICHT 'download', sondern 'url'
    ]);


} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error while generating: ' . $e->getMessage()]);
}
