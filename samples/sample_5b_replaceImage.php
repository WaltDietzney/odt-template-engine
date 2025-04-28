<?php

/**
 * Sample 5b - Replace Multiple Images and Set Metadata
 *
 * This advanced example shows how to:
 * - Dynamically replace multiple images inside an ODT document
 * - Update image dimensions if needed
 * - Set metadata fields for the document
 */

use OdtTemplateEngine\OdtTemplate;

// [1] Initialize the template
$template = new OdtTemplate('samples/templates/template_5b_replaceImage.odt');

// [2] Load the ODT document
$template->load();

// [3] Define images to replace (by draw:name attribute)
$imagesToReplace = [
    'Logo' => [
        'path' => 'assets/WaltDietzney.png',
        'width' => '6cm'
     ],
    'Banner' => [
        'path' => 'assets/banner.png',
        'width' => '12cm',
        'height' => '3cm'
    ],
    'FooterImage' => [
        'path' => 'assets/footer.png',
        // No width/height specified; keep original size
    ]
];

// [4] Loop through each image and replace
foreach ($imagesToReplace as $name => $options) {
    $path = $options['path'];
    $dimensions = $options;
    unset($dimensions['path']); // Remove path key from dimensions
    $template->replaceImageByName($name, $path, $dimensions);
}

// [5] Set document metadata
$template->setMeta([
    'title' => 'Sample 5b - Multiple Image Replacement',
    'author' => 'OdtTemplateEngine Team',
    'subject' => 'Replacing multiple images dynamically in an ODT template',
    'description' => 'This document demonstrates how to replace several images based on their frame names and add metadata.',
    'keywords' => 'odt, template, replace image, metadata, php',
    'language' => 'en',
    'generator' => 'OdtTemplateEngine v1.0',
    'editing_cycles' => 2,
    'editing_duration' => 'PT10M',
    'date' => date('c'),
]);

// [6] Render all assignments
$template->render();

// [7] Save the final document
$outputPath = 'samples/output/output_5b_replaceImage.odt';
$template->save($outputPath);

// [8] Output success message
echo "The file 'output_5b_replaceMultipleImages.odt' was successfully created.\n";



