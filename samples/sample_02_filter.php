<?php

/**
 * Sample 2 - Filters, Formatting and Conditional Logic
 *
 * This example demonstrates:
 * - Text transformations (upper/lowercase)
 * - Date and number formatting
 * - Currency formatting
 * - Conditional sections (if/else logic)
 */

// (Optional) Load Composer autoloader
// (Normally needed outside Docker)
// require '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

// [1] Initialize template engine with a filter-based ODT template
$template = new OdtTemplate('samples/templates/template_02_filter.odt');

// [2] Load the template into memory
$template->load();

// [3] Assign simple variables with filters applied in the template

// {{upper:name}} → converts "Anna Beispiel" to "ANNA BEISPIEL"
$template->assign(['name' => 'Anna Beispiel']);

// {{lower:email}} → converts "ANNA@EXAMPLE.COM" to "anna@example.com"
$template->assign(['email' => 'ANNA@EXAMPLE.COM']);

// {{date:geburtstag|d.m.Y}} → formats '1995-08-15' as '15.08.1995'
$template->assign(['geburtstag' => '1995-08-15']);

// {{number:umsatz|2}} → formats '1345.5' as '1345.50'
// {{currency:umsatz}} → formats '1345.5' as '1.345,50 €' (depending on locale)
$template->assign(['umsatz' => '1345.5']);

// [4] Assign a value for conditional logic inside the template

// {{#if:is_admin}}Hello Admin!{{#else}}Hello User!{{#endif}}
// Sets 'is_admin' to true, resulting in "Hello Admin!" being displayed
$template->assign(['is_admin' => true]);

// [5] (Optional) Alternative legacy method for setting values
// Note: 'setValues' is deprecated but still functional
/*
$template->setValues([
    'name' => 'Anna Beispiel',
    'email' => 'ANNA@EXAMPLE.COM',
    'kommentar' => "This is line 1\nAnd this is line 2.",
    'geburtstag' => '1995-08-15',
    'umsatz' => '1345.5',
]);
*/

// [6] Render all assigned variables into the template
$template->render();

// [7] Save the output to a new ODT file
$template->save('samples/output/output_02_filter.odt');


