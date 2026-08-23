<?php

use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;

// Load the ODT template.
$template = new OdtTemplate('samples/templates/template_20_tableRatio.odt');
$template->load();

// Create a table with a 2:1:1 column width ratio.
$table = new RichTable();
$table->setColumnWidthRatios([2, 1, 1]);

// First row: demonstrate how the width ratio is applied across the columns.
$table->addRow([
    (new RichTableCell('Spalte A'))->setStyle([
        'background' => '#ffe0b2',
        'text-align' => 'center',
        'color' => '#eb4034',
    ]),
    (new RichTableCell('Spalte B'))->setStyle([
        'background' => '#ffe0b2',
        'text-align' => 'center',
        'color' => '#eb4034',
    ]),
    (new RichTableCell('Spalte C'))->setStyle([
        'background' => '#ffe0b2',
        'text-align' => 'center',
        'color' => '#eb4034',
    ]),
]);

// Add additional rows using the same column proportions.
$table->addRow([
    new RichTableCell('Alpha'),
    new RichTableCell('Beta'),
    new RichTableCell('Gamma'),
]);

$table->addRow([
    new RichTableCell('X'),
    new RichTableCell('Y'),
    new RichTableCell('Z'),
]);

// Insert the table into the template and save the result.
$template->setElement('tableblock', $table);
$template->save('samples/output/output_20_tableRatio.odt');
