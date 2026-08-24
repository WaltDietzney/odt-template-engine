<?php
/**
 * Sample 11: Advanced Table Creation with Styled Cells
 *
 * This example demonstrates how to create a complex table with styled cells,
 * including different background colors, text styles, and alignments,
 * using the ODT Template Engine for PHP.
 */

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Utils\StyleMapper;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Initialize the template.
$template = new OdtTemplate(__DIR__ . '/templates/template_11_table.odt');

// Create a fixed-width table style and apply it to the table.
$table = new RichTable();
StyleMapper::registerTableStyle('MyFixedTableStyle', [
    'table:width' => '15cm',
    'table:align' => 'left',
    'style:rel-width' => '100%',
]);
$table->setTableStyleName('MyFixedTableStyle');

// First cell: red background, bold and centered text.
$cell1 = (new RichTableCell('Important Notice'))
    ->setStyle([
        'background' => '#ffdddd',
        'padding' => '0.3cm',
        'weight' => 'bold',
        'color' => '#cc0000',
        'align' => 'center',
    ]);

// Second cell: blue background, italic and centered text.
$cell2 = (new RichTableCell('General Information'))
    ->setStyle([
        'background' => '#ddeeff',
        'padding' => '0.3cm',
        'italic' => true,
        'color' => '#003366',
        'text-align' => 'center',
    ]);

// Second row: normal cells without special styling.
$cell3 = new RichTableCell('Project Start Date');
$cell4 = new RichTableCell('April 1, 2025');

// Third row: highlight important data.
$cell5 = (new RichTableCell('Deadline'))
    ->setStyle([
        'background' => '#fff3cd',
        'padding' => '0.2cm',
        'weight' => 'bold',
        'text-align' => 'left',
    ]);
$cell6 = new RichTableCell('June 30, 2025');

// Fourth row: contact details.
$cell7 = new RichTableCell('Project Manager');
$cell8 = new RichTableCell('John Doe');

// Assemble the table and set explicit column widths.
$table
    ->addRow([$cell1, $cell2])
    ->addRow([$cell3, $cell4])
    ->addRow([$cell5, $cell6])
    ->addRow([$cell7, $cell8])
    ->setColumnWidths(['2cm', '10cm']);

// Replace the template placeholder with the generated table.
$template->setElement('tableblock', $table);

// Save the generated ODT document.
$template->save(__DIR__ . '/output/output_11_table.odt');
