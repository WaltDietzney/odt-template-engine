<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_L07_tables.odt');

$headerStyle = [
    'background' => '#27616d',
    'border' => '0.5pt solid #27616d',
    'padding' => '0.18cm',
    'bold' => true,
    'color' => '#ffffff',
];
$bodyStyle = [
    'border' => '0.5pt solid #cbd7dc',
    'padding' => '0.16cm',
];

$table = (new RichTable())->setHeaderRowCount(1);
$table->addRow([
    new RichTableCell('Task', $headerStyle),
    new RichTableCell('Owner', $headerStyle),
    new RichTableCell('Status', $headerStyle),
]);

$requirements = (new Paragraph())
    ->addText('Requirements', ['bold' => true])
    ->addLineBreak()
    ->addText('Scope and stakeholders');

$table->addRow([
    new RichTableCell($requirements, $bodyStyle),
    new RichTableCell('Anna', $bodyStyle),
    new RichTableCell('Complete', array_merge($bodyStyle, ['background' => '#e4f2eb', 'color' => '#245d3c'])),
]);
$table->addRow([
    new RichTableCell('Template design', $bodyStyle),
    new RichTableCell('Ben', $bodyStyle),
    new RichTableCell('In progress', array_merge($bodyStyle, ['background' => '#fff2d6', 'color' => '#815b14'])),
]);
$table->addRow([
    new RichTableCell('Review', $bodyStyle),
    new RichTableCell('Carla', $bodyStyle),
    new RichTableCell('Planned', $bodyStyle),
]);

$template->setElement('project_status', $table);
$template->save(__DIR__ . '/output/output_L07_tables.odt');
