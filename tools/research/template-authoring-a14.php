<?php

declare(strict_types=1);

/**
 * TEMPLATE-AUTHORING-01A1.4 manual LibreOffice regression helper.
 *
 * Source fixture:
 *   research/template-authoring-a14.odt
 *
 * Generated result:
 *   research/output/template-authoring-a14-output.odt
 */

use OdtTemplateEngine\OdtTemplate;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$templatePath = $root . '/research/template-authoring-a14.odt';
$outputDirectory = $root . '/research/output';
$outputPath = $outputDirectory . '/template-authoring-a14-output.odt';

if (!is_file($templatePath)) {
    fwrite(
        STDERR,
        "Missing local Writer fixture:\n"
        . $templatePath
        . "\n\nCreate it according to "
        . "docs/architecture/TEMPLATE_AUTHORING_01A14_LIBREOFFICE_REGRESSION_FIXTURES.md\n"
    );
    exit(1);
}

if (!is_dir($outputDirectory)
    && !mkdir($outputDirectory, 0775, true)
    && !is_dir($outputDirectory)
) {
    throw new RuntimeException('Could not create research output directory.');
}

$template = new OdtTemplate($templatePath);

$template->setValues([
    'show_profile' => true,
    'show_table' => false,
    'table_value' => 'SHOULD NOT APPEAR',
    'active' => false,
]);

$template->assignRepeating('people', [
    [
        'name' => 'Ada Lovelace',
        'role' => 'Analyst',
        'active' => true,
    ],
    [
        'name' => 'Grace Hopper',
        'role' => 'Engineer',
        'active' => false,
    ],
    [
        'name' => 'Katherine Johnson',
        'role' => 'Mathematician',
        'active' => true,
    ],
]);

$template->assignRepeating('orders', [
    [
        'customer' => 'Alpha GmbH',
        'amount' => '120.00 EUR',
    ],
    [
        'customer' => 'Beta AG',
        'amount' => '240.00 EUR',
    ],
]);

$template->assignRepeating('entries', [
    ['label' => 'First entry'],
    ['label' => 'Second entry'],
]);

$template->render();
$template->save($outputPath);

fwrite(STDOUT, "Generated: {$outputPath}\n");
