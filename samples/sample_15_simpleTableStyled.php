<?php
/**
 * Sample 15: Create and Style a Simple Table in an ODT Template
 *
 * Description:
 * This script loads an ODT template, generates a table with styled headers and cells,
 * fills it with some sample content (including check marks and pending tasks),
 * and saves the output to a new ODT file.
 *
 * Author: [Your Name]
 * Date: [Current Date]
 * Requires: OdtTemplateEngine library
 */

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Utils\StyleMapper;

// Load the ODT template
$template = new OdtTemplate('samples/templates/template_15_simpleTableStyled.odt');
$template->load();

// Create a new RichTable instance
$table = new RichTable();

// Add a header row with styled cells
$table->addRow([
    (new RichTableCell('Task', [
        'background' => '#ddeeff', 
        'text-align' => 'center', 
        'border' => '0.05pt solid #000000'
    ]))->alignCenter(),
    (new RichTableCell('Status', [
        'background' => '#ddeeff', 
        'text-align' => 'center', 
        'border' => '0.05pt solid #000000'
    ]))->alignCenter()
]);

// Create a Paragraph element (example usage)
$c1 = new Paragraph();
$c1->addText('HtmlImport');

// Create a RichText instance
$r = new RichText();

// Add a row with rich text in the first cell and a check mark in the second cell
$table->addRow([
    new RichTableCell(
        $r->addText('HTML Import', ['text-align' => 'center', 'bold' => true]),
        ['background' => '#c8facc', 'align' => 'end']
    ),
    new RichTableCell('✔', [
        'background' => '#c8facc', 
        'text-align' => 'center'
    ]),
]);

// Add a row for "Table Styling" feature with a check mark
$table->addRow([
    new RichTableCell('Table Styling', [
        'background' => '#ffffff'
    ]),
    new RichTableCell('✔', [
        'background' => '#c8facc', 
        'text-align' => 'center'
    ]),
]);

// Add a row for "Pending" task with an empty box
$table->addRow([
    new RichTableCell('Pending Task', [
        'background' => '#ffffff'
    ]),
    new RichTableCell('☐', [
        'background' => '#fce3e3', 
        'align' => 'center'
    ]),
]);

// Replace the placeholder 'tableblock' in the template with the constructed table
$template->setElement('tableblock', $table);



// Save the modified document
$template->save('samples/output/output_15_simpleTableStyled.odt');

// Output success message
echo "✅ Table with formatting successfully generated.\n";

