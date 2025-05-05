<?php

use OdtTemplateEngine\AbstractOdtTemplate;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;
use OdtTemplateEngine\Elements\Paragraph;

$template = new OdtTemplate('samples/templates/template_12_advancedTable.odt');
$template->load();

$template->ensureParagraphStylesExist(
    [
        'CenterPara' => [
            'text-align' => 'center'
        ],
        'RightPara' => [
            'text-align' => 'right'
        ],
        'LeftPara' => [
            'text-align' => 'left'
        ]
    ]
);


$table = new RichTable();

$paragraphC = new Paragraph('CenterPara', [
    'text-align' => 'center'
]);
$paragraphC->addText('Centered Text');

// ✅ Erste Zelle: Mit Padding, Border, Hintergrund, Zentrierung
$cell1 = (new RichTableCell($paragraphC))->setStyle([
    'background' => '#e0f7fa',
    'padding' => '0.2cm',
    'border' => '0.06pt solid #006064',
    'padding-left' => '0.3cm',
    'padding-right' => '0.3cm'
]);

$paragraphR = new Paragraph('RightPara', [
    'text-align' => 'right'
]);
$paragraphR->addText('Right Text and colores');


// ✅ Zweite Zelle: Nur Rahmen und Farbe
$cell2 = (new RichTableCell($paragraphR))->setStyle([
    'background' => '#ffe0b2',
    'border-left' => '0.1pt solid #ff6f00',
    'border-bottom' => '0.1pt solid #ff6f00',
]);

// ✅ Zweite Zeile
$cell3 = (new RichTableCell('Bottom left cell'))->setStyle([
    'padding-top' => '0.1cm',
    'padding-bottom' => '0.1cm',
]);

$cell4 = (new RichTableCell('Bottom right cell'))->setStyle([
    'border-top' => '0.1pt dashed #d32f2f',
]);

// Tabelle zusammenbauen
$table
    ->addRow([$cell1, $cell2])
    ->addRow([$cell3, $cell4]);

// Tabelle einsetzen
$template->setElement('tableblock', $table);

// Speichern
$template->save('samples/output/output_12_advancedTable.odt');
