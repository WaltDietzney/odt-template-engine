<?php
/**
 * Sample 11: Advanced Table Creation with Styled Cells
 *
 * This example demonstrates how to create a complex table with styled cells,
 * including different background colors, text styles, and alignments,
 * using the ODT Template Engine for PHP.
 */

use OdtTemplateEngine\AbstractOdtTemplate;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Utils\StyleMapper;

// Initialize the template
$template = new OdtTemplate('samples/templates/template_11_table.odt');
$template->load();

// Create a new RichTable instance
$table = new RichTable();

// 🔴 First cell: Red background, bold and centered text
$cell1 = (new RichTableCell('Important Notice'))
    ->setStyle([
        'background' => '#ffdddd',
        'padding' => '0.3cm',
        'weight' => 'bold',
        'color' => '#cc0000',
        'align' => 'center'
    ]);

// 🔵 Second cell: Blue background, italic and centered text
$cell2 = (new RichTableCell('General Information'))
    ->setStyle([
        'background' => '#ddeeff',
        'padding' => '0.3cm',
        'italic' => true,
        'color' => '#003366',
        'text-align' => 'center'
    ]);

// Second row: Normal cells without special styling
$cell3 = new RichTableCell('Project Start Date');
$cell4 = new RichTableCell('April 1, 2025');

// Third row: Slightly highlighted important data
$cell5 = (new RichTableCell('Deadline'))
    ->setStyle([
        'background' => '#fff3cd',
        'padding' => '0.2cm',
        'weight' => 'bold',
        'text-align' => 'left'
    ]);
$cell6 = new RichTableCell('June 30, 2025');

// Fourth row: Contact details
$cell7 = new RichTableCell('Project Manager');
$cell8 = new RichTableCell('John Doe');

// 🧱 Assemble the table by adding rows
$table
    ->addRow([$cell1, $cell2])
    ->addRow([$cell3, $cell4])
    ->addRow([$cell5, $cell6])
    ->addRow([$cell7, $cell8]);

// 🔄 Replace the {{tableblock}} placeholder in the template with the generated table
$template->setElement('tableblock', $table);

// 💾 Save the rendered document
$template->save('samples/output/output_11_table.odt');
?>
