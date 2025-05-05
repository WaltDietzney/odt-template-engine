<?php

/**
 * Sample 1 - Simple Variables, Repeating Blocks and Image Insertion
 *
 * This example demonstrates:
 * - Replacing simple text variables
 * - Repeating a list of items (e.g., table rows)
 * - Inserting an image into the document
 */

// [1] (Optional) Load Composer autoloader
// (Normally needed outside Docker)
// require '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

// [2] Initialize template engine with a simple ODT template
$template = new OdtTemplate('samples/templates/template_01_simple_variables.odt');

// [3] Load the template into memory
$template->load();

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
$template->setImage('foto', 'assets/Logo.png', [
    'width' => '2cm' // Resize the image to fit nicely
]);

// [7] Render the final document with all replacements
$template->render();

// [8] Save the result to a new output file
$template->save('samples/output/output_01_simple_variables.odt');

