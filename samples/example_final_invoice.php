<?php
require '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// 1️⃣ Load your template
$template = new OdtTemplate('templates/template_with_tabstops.odt');
$template->load();


$title = new Paragraph('Title');
$title->addText('Different Ways to set Tabs');

// 2️⃣ Build a complex RichText block

$par1 = new Paragraph();

// Define shared tab stops: 5cm and 11cm from left margin
$tabStops = [
    ['position' => 5.0, 'alignment' => 'left', 'text' => 'Itam A', 'style' => ['bold' => true]],
    ['position' => 11.0, 'alignment' => 'right', 'text' => '€12.50', 'style' => ['color' => '#cfcfcf'], 'italic' => true]
];

// Single line with two values
$par1->addTabsWithTexts(
    $tabStops
);

// Tabular block: header + data rows
$rows = [
    ['Product', 'Price'],
    ['Widget', '€9.99'],
    ['Gadget', '€14.20'],
];

$par2 = new Paragraph();

$par2->addTabularLines(
    $rows,
    $tabStops,
    [
        'color' => '#0066cc',         // text color
        'InvoiceTable'
    ]
);
$par3 = new Paragraph();
// Key-Value summary
$par3->addKeyValueLine('Subtotal', '€24.19', 11.0, ['italic' => true, 'bold' => true]);

$rich = new RichText();
$rich->addParagraph($title)
    ->addParagraphBreak(4)
    ->addParagraph($par1)
    ->addParagraphBreak(2)
    ->addParagraph($par2)
    ->addParagraphBreak(2)
    ->addParagraph($par3);

// 3️⃣ Inject into your template
$template->setElement('tabular_block', $rich);

// 4️⃣ Save result
$template->save('output/final_invoice.odt');
echo "✅ Generated invoice with rich tabs!";
