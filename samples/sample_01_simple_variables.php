<?php

/**
 * Sample 1 - Simple Variables, Repeating Blocks and Image Insertion
 *
 * This example demonstrates:
 * - Replacing simple text variables
 * - Repeating a list of items (e.g., table rows)
 * - Inserting an image into the document
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

// [2] Initialize template engine with a simple ODT template
$template = new OdtTemplate(__DIR__ . '/templates/template_01_simple_variables.odt');

// [4] Assign simple variables for direct text replacement
$template->assign([
    'name'  => 'Anna Beispiel',
    'datum' => '2025-04-01',
]);

// [5] Assign a repeating structure (e.g., for table rows)
$template->assignRepeating('items', [
    ['produkt' => 'Kaffee', 'preis' => '4,99 €'],
    ['produkt' => 'Tee',    'preis' => '3,49 €'],
    ['produkt' => 'Kakao',  'preis' => '2,99 €'],
]);

// [6] Insert an image by replacing a placeholder
$template->setImage('foto', __DIR__ . '/../assets/Logo.png', [
    'width' => '2cm' // Resize the image to fit nicely
]);

// [7] Render the final document with all replacements
$template->render();

// [8] Save the result to a new output file
$template->save(__DIR__ . '/output/output_01_simple_variables.odt');
