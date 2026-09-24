<?php

declare(strict_types=1);

/**
 * L12 — Writer Table Population
 *
 * This sample teaches how to populate an existing named table authored and
 * formatted in LibreOffice Writer. PHP supplies scalar row data; it does not
 * rebuild the table, its columns, or its formatting.
 *
 * keepRows uses zero-based indices from the ordinary Writer source-row
 * sequence. Here, source row 0 is kept while the remaining source rows are
 * populated or cloned as needed.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L12_writer_table_population.odt');

// Writer owns the named table, header, columns, and formatting. PHP supplies
// only the mutable scalar data and protects the first ordinary source row.
$template
    ->table('L12_ResultTable')
    ->populate(
        [
            ['Participant outcomes', '248', 'UPDATED'],
            ['Completion rate', '87%', 'UPDATED'],
            ['Employer partners', '42', 'UPDATED'],
            ['Further training', '23%', 'UPDATED'],
        ],
        [
            'keepRows' => [0],
        ]
    );

$template->save(__DIR__ . '/output/output_L12_writer_table_population.odt');
