<?php

/**
 * Sample 24 - Replace a native ODF section with an image resource.
 *
 * The image is copied into the ODT package and declared in the manifest by
 * the existing transactional section-resource path.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/sample_24_sectionImageReplacement.odt');
$image = new ImageElement(__DIR__ . '/templates/sample_23_image.png', [
    'width' => '4cm',
    'height' => '3cm',
    'anchor' => 'as-char',
]);

$template->section('ImageSection')->replaceContent($image);

$outputPath = __DIR__ . '/output/output_24_sectionImageReplacement.odt';
$template->save($outputPath);

echo "✅ samples/output/output_24_sectionImageReplacement.odt generated successfully\n";
