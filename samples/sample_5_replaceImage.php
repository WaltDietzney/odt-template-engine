<?php

/**
 * Sample 5 - Replace Image by Frame Name
 *
 * This example demonstrates how to replace an existing image inside an ODT file
 * by referencing the draw:name attribute of a <draw:frame> element.
 *
 * Highlights:
 * - Replaces the image file inside the document.
 * - Optionally updates the width and height of the image frame.
 */

use OdtTemplateEngine\OdtTemplate;

// [1] Initialize the template
$template = new OdtTemplate('samples/templates/template_5_replaceImage.odt');

// [2] Load the ODT document
$template->load();

// [3] Replace an image identified by the draw:name="Logo" in the document
// - Replace the image file with "assets/WaltDietzney.png"
// - Set the new width of the image frame to "6cm"
// - Height will be kept proportional if not specified
$template->replaceImageByName('Logo', 'assets/WaltDietzney.png', [
    'width' => '6cm' // Optional: you could also specify 'height' => '4cm'
]);

// [4] Render (optional, in case other replacements were made)
//$template->render();

// [5] Save the updated document
$outputPath = 'samples/output/output_5_replaceImage.odt';
$template->save($outputPath);

// [6] Output success message
echo "The file 'output_5_replaceImage.odt' was successfully created.\n";

