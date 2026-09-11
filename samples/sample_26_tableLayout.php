<?php

/**
 * Sample 26: Table Layout Showcase
 *
 * Demonstrates the TABLE-LAYOUT-01 API:
 * - absolute and relative table width;
 * - whole-table alignment;
 * - relative column geometry;
 * - exact and minimum row height;
 * - vertical cell alignment;
 * - independent horizontal paragraph alignment.
 */

use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// The template needs two placeholders:
// {{absolute_table}}
// {{relative_table}}
$template = new OdtTemplate(__DIR__ . '/templates/template_26_tableLayout.odt');

/*
 * -------------------------------------------------------------------------
 * Table 1: Absolute geometry
 * -------------------------------------------------------------------------
 *
 * A centered 15 cm table. Column sizing is left to Writer in this table so
 * that this document can also contain the independent ratio-based table below
 * without reusing the legacy positional column style names (co0, co1, ...).
 *
 * The first three content rows deliberately use the same exact row height
 * while their cells use top, middle, and bottom vertical alignment. This
 * makes the vertical positioning visible in LibreOffice at a glance.
 *
 * The final row uses a minimum height instead of an exact height so that
 * growable row behavior can be compared with the fixed-height rows.
 */

$absoluteTable = (new RichTable())
    ->setTableName('AbsoluteLayoutShowcase')
    ->setTableStyle([
        'width' => '15cm',
        'alignment' => 'center',
    ]);


$absoluteTable->addRow([
    (new RichTableCell('ABSOLUTE TABLE'))
        ->setStyle([
            'background' => '#1f4e78',
            'padding' => '0.18cm',
            'weight' => 'bold',
            'color' => '#ffffff',
            'text-align' => 'center',
            'vertical-align' => 'middle',
            'border' => '0.5pt solid #1f4e78',
        ])
        ->setColspan(2),
], [
    'row-height' => '0.9cm',
]);

$absoluteTable->addRow([
    new RichTableCell('Top aligned', [
        'background' => '#d9eaf7',
        'padding' => '0.18cm',
        'weight' => 'bold',
        'vertical-align' => 'top',
        'border' => '0.5pt solid #9fbad0',
    ]),
    new RichTableCell(
        'This row has an exact height of 1.8 cm. '
        . 'The text in this cell is aligned to the top.',
        [
            'padding' => '0.18cm',
            'vertical-align' => 'top',
            'border' => '0.5pt solid #9fbad0',
        ]
    ),
], [
    'row-height' => '1.8cm',
]);

$absoluteTable->addRow([
    new RichTableCell('Middle aligned', [
        'background' => '#e8f2e3',
        'padding' => '0.18cm',
        'weight' => 'bold',
        'vertical-align' => 'middle',
        'border' => '0.5pt solid #a9c49e',
    ]),
    new RichTableCell(
        'The same exact row height, but this cell is vertically centered.',
        [
            'padding' => '0.18cm',
            'vertical-align' => 'middle',
            'text-align' => 'center',
            'border' => '0.5pt solid #a9c49e',
        ]
    ),
], [
    'row-height' => '1.8cm',
]);

$absoluteTable->addRow([
    new RichTableCell('Bottom aligned', [
        'background' => '#f9e5d1',
        'padding' => '0.18cm',
        'weight' => 'bold',
        'vertical-align' => 'bottom',
        'border' => '0.5pt solid #d8b894',
    ]),
    new RichTableCell(
        'Again 1.8 cm exact height. '
        . 'This text should sit at the bottom of the cell.',
        [
            'padding' => '0.18cm',
            'vertical-align' => 'bottom',
            'text-align' => 'right',
            'border' => '0.5pt solid #d8b894',
        ]
    ),
], [
    'row-height' => '1.8cm',
]);

$absoluteTable->addRow([
    new RichTableCell('Minimum height', [
        'background' => '#f3f3f3',
        'padding' => '0.18cm',
        'weight' => 'bold',
        'vertical-align' => 'middle',
        'border' => '0.5pt solid #b8b8b8',
    ]),
    new RichTableCell(
        'This row has a minimum height of 1.2 cm. '
        . 'If the content needs more room, Writer may grow the row.',
        [
            'padding' => '0.18cm',
            'vertical-align' => 'middle',
            'border' => '0.5pt solid #b8b8b8',
        ]
    ),
], [
    'min-row-height' => '1.2cm',
]);

/*
 * -------------------------------------------------------------------------
 * Table 2: Relative geometry
 * -------------------------------------------------------------------------
 *
 * A right-aligned table using 70% relative width and Writer-compatible
 * 2:1:1 relative column ratios.
 */

$relativeTable = (new RichTable())
    ->setTableName('RelativeLayoutShowcase')
    ->setTableStyle([
        'relative-width' => '70%',
        'alignment' => 'right',
    ]);

$relativeTable->setColumnWidthRatios([2, 1, 1]);

$relativeTable->addRow([
    (new RichTableCell('RELATIVE TABLE'))
        ->setStyle([
            'background' => '#4f6d4a',
            'padding' => '0.16cm',
            'weight' => 'bold',
            'color' => '#ffffff',
            'text-align' => 'center',
            'vertical-align' => 'middle',
            'border' => '0.5pt solid #4f6d4a',
        ])
        ->setColspan(3),
], [
    'row-height' => '0.85cm',
]);

$relativeTable->addRow([
    new RichTableCell('Capability', [
        'background' => '#dde8da',
        'padding' => '0.14cm',
        'weight' => 'bold',
        'vertical-align' => 'middle',
        'border' => '0.5pt solid #9fb19a',
    ]),
    new RichTableCell('Status', [
        'background' => '#dde8da',
        'padding' => '0.14cm',
        'weight' => 'bold',
        'text-align' => 'center',
        'vertical-align' => 'middle',
        'border' => '0.5pt solid #9fb19a',
    ]),
    new RichTableCell('API', [
        'background' => '#dde8da',
        'padding' => '0.14cm',
        'weight' => 'bold',
        'text-align' => 'center',
        'vertical-align' => 'middle',
        'border' => '0.5pt solid #9fb19a',
    ]),
], [
    'min-row-height' => '0.8cm',
]);

foreach ([
    ['Table width', 'Ready', '1.0'],
    ['Row height', 'Ready', '1.0'],
    ['Vertical alignment', 'Ready', '1.0'],
] as [$capability, $status, $api]) {
    $relativeTable->addRow([
        new RichTableCell($capability, [
            'padding' => '0.14cm',
            'vertical-align' => 'middle',
            'border' => '0.5pt solid #c4c4c4',
        ]),
        new RichTableCell($status, [
            'padding' => '0.14cm',
            'text-align' => 'center',
            'vertical-align' => 'middle',
            'border' => '0.5pt solid #c4c4c4',
        ]),
        new RichTableCell($api, [
            'padding' => '0.14cm',
            'text-align' => 'center',
            'vertical-align' => 'middle',
            'border' => '0.5pt solid #c4c4c4',
        ]),
    ], [
        'min-row-height' => '0.75cm',
    ]);
}

// Insert both tables into the template.
$template->setElement('absolute_table', $absoluteTable);
$template->setElement('relative_table', $relativeTable);

// Save the generated ODT document.
$template->save(__DIR__ . '/output/output_26_tableLayout.odt');
