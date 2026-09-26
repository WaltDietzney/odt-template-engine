<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\OdtTemplate;

$template = new OdtTemplate(__DIR__ . '/templates/template_C02_advanced_table_layout.odt');

$absolute = (new RichTable())
    ->setTableName('BudgetByWorkstream')
    ->setTableStyle(['width' => '16cm', 'alignment' => 'center']);
$absolute->setColumnWidthRatios([3, 2, 2]);
$absolute->setHeaderRowCount(1);
$absolute->addRow([
    new RichTableCell('Workstream', ['background' => '#173e4a', 'color' => '#ffffff', 'weight' => 'bold', 'padding' => '0.2cm', 'vertical-align' => 'middle']),
    new RichTableCell('Budget', ['background' => '#173e4a', 'color' => '#ffffff', 'weight' => 'bold', 'text-align' => 'right', 'padding' => '0.2cm', 'vertical-align' => 'middle']),
    new RichTableCell('Owner', ['background' => '#173e4a', 'color' => '#ffffff', 'weight' => 'bold', 'padding' => '0.2cm', 'vertical-align' => 'middle']),
], ['row-height' => '0.95cm']);
$absolute->addRow([
    new RichTableCell('Research and discovery', ['padding' => '0.18cm', 'vertical-align' => 'middle']),
    new RichTableCell('€ 18,000', ['padding' => '0.18cm', 'text-align' => 'right', 'vertical-align' => 'middle']),
    new RichTableCell('Anna', ['padding' => '0.18cm', 'vertical-align' => 'middle']),
], ['row-height' => '1.15cm']);
$absolute->addRow([
    new RichTableCell('Template production', ['padding' => '0.18cm', 'vertical-align' => 'middle']),
    new RichTableCell('€ 24,500', ['padding' => '0.18cm', 'text-align' => 'right', 'vertical-align' => 'middle']),
    new RichTableCell('Ben', ['padding' => '0.18cm', 'vertical-align' => 'middle']),
], ['min-row-height' => '1.0cm']);
$absolute->addRow([
    new RichTableCell('Review and delivery', ['padding' => '0.18cm', 'vertical-align' => 'middle']),
    new RichTableCell('€ 9,500', ['padding' => '0.18cm', 'text-align' => 'right', 'vertical-align' => 'middle']),
    new RichTableCell('Carla', ['padding' => '0.18cm', 'vertical-align' => 'middle']),
], ['min-row-height' => '1.0cm']);

$relative = (new RichTable())
    ->setTableName('QuarterlyCapacity')
    ->setTableStyle(['relative-width' => '82%', 'alignment' => 'right']);
$relative->setColumnWidthRatios([3, 2, 2]);
$relative->setHeaderRowCount(1);
$relative->addRow([
    new RichTableCell('Quarterly capacity', ['background' => '#e6f0f0', 'weight' => 'bold', 'padding' => '0.16cm']),
    new RichTableCell('Plan', ['background' => '#e6f0f0', 'weight' => 'bold', 'text-align' => 'center', 'padding' => '0.16cm']),
    new RichTableCell('Unit', ['background' => '#e6f0f0', 'weight' => 'bold', 'text-align' => 'center', 'padding' => '0.16cm']),
], ['min-row-height' => '0.8cm']);
$relative->addRow([
    new RichTableCell('Available delivery days', ['padding' => '0.16cm']),
    new RichTableCell('240', ['padding' => '0.16cm', 'text-align' => 'center']),
    new RichTableCell('days', ['padding' => '0.16cm', 'text-align' => 'center']),
], ['min-row-height' => '0.8cm']);

$template->setElement('absolute_budget', $absolute);
$template->setElement('relative_capacity', $relative);
$template->save(__DIR__ . '/output/output_C02_advanced_table_layout.odt');
