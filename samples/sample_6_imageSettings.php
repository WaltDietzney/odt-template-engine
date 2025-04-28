<?php

/**
 * Sample 6 - Advanced Image Handling
 *
 * Demonstrates two different ways to insert and style images in an ODT document:
 * 1. Using ImageElement for flexible placement (e.g., in RichText or separately).
 * 2. Using setImage() directly to replace a placeholder in the document.
 */

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\ImageElement;


// [1] Initialize template
$template = new OdtTemplate('samples/templates/template_6_imageSettings.odt');

// [2] Define paths to images
$imagePath = 'assets/banner.png';  // Image for direct replacement
$logoPath = 'assets/Logo.png';     // Logo for ImageElement insertion

// [3] Create an ImageElement with custom styling
$img = new ImageElement($logoPath, [
    'align'  => 'right',         // Horizontal alignment (style:horizontal-pos)
    'anchor' => 'as-char',        // Anchoring as character (text:anchor-type)
    'wrap'   => 'none',           // No text wrapping around image
    'width'  => '4cm',            // Set width
    'height' => '3cm',            // Set height
]);

// Alternative: Set styles separately if needed
// $img->setStyle([
//     'align'  => 'right',
//     'anchor' => 'paragraph',
//     'wrap'   => 'none',
//     'width'  => '4cm',
//     'height' => '3cm'
// ]);

// [4] Insert ImageElement into placeholder {{logo}}
$template->setElement('logo', $img);

// [5] Replace placeholder {{image}} with an image directly
$template->setImage('image', $imagePath, [
    'width'   => '6cm',
    'anchor'  => 'paragraph',
    'wrap'    => 'none',
]);

// [6] Save the final document
$template->save('samples/output/output_6_imageSettings.odt');

// Optional Debug Output
// echo "✅ ODT with images successfully generated.\n";

