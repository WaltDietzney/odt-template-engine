<?php

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$template = new OdtTemplate(__DIR__ . '/templates/template_12_advancedTable.odt');

$template->ensureParagraphStylesExist([
    'CenterPara' => [
        'text-align' => 'center',
    ],
    'RightPara' => [
        'text-align' => 'right',
    ],
    'LeftPara' => [
        'text-align' => 'left',
    ],
]);

$table = new RichTable();

$paragraphC = new Paragraph('CenterPara', [
    'text-align' => 'center',
]);
$paragraphC->addText('Centered Text');

// First cell: padding, border, background color and centered content.
$cell1 = (new RichTableCell($paragraphC))->setStyle([
    'background' => '#e0f7fa',
    'padding' => '0.2cm',
    'border' => '0.06pt solid #006064',
    'padding-left' => '0.3cm',
    'padding-right' => '0.3cm',
]);

$paragraphR = new Paragraph('RightPara', [
    'text-align' => 'right',
]);
$paragraphR->addText('Right Text and colores');

// Second cell: background color and partial borders.
$cell2 = (new RichTableCell($paragraphR))->setStyle([
    'background' => '#ffe0b2',
    'border-left' => '0.1pt solid #ff6f00',
    'border-bottom' => '0.1pt solid #ff6f00',
]);

// Second row: demonstrate per-cell padding and border styles.
$cell3 = (new RichTableCell('Bottom left cell'))->setStyle([
    'padding-top' => '0.1cm',
    'padding-bottom' => '0.1cm',
]);

$cell4 = (new RichTableCell('Bottom right cell'))->setStyle([
    'border-top' => '0.1pt dashed #d32f2f',
]);

// Assemble the table.
$table
    ->addRow([$cell1, $cell2])
    ->addRow([$cell3, $cell4]);

// Insert the table into the template and save the result.
$template->setElement('tableblock', $table);
$template->save(__DIR__ . '/output/output_12_advancedTable.odt');
