<?php

/**
 * Sample 25 - Instantiate a native CV section with local template values.
 *
 * The LibreOffice-authored section remains the visible prototype. Each call
 * clones its native structure, rewrites identities, and binds only that clone.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/sample_25_sectionClone.odt');

$template->section('ExperienceEntry')->instantiate([
    'note' => 'Aktuelle Position',
    'position' => 'Senior Projektmanager',
    'activity' => 'Leitung eines interdisziplinären Projektteams.',
]);

$template->section('ExperienceEntry')->instantiate([
    'note' => 'Vorherige Position',
    'position' => 'Marketing-Spezialist',
    'activity' => 'Entwicklung digitaler Marketingkampagnen.',
]);

$outputPath = __DIR__ . '/output/output_25_sectionInstantiation.odt';
$template->save($outputPath);

echo "✅ samples/output/output_25_sectionInstantiation.odt generated successfully\n";
