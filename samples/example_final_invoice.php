<?php
require '../vendor/autoload.php';

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;

// 1️⃣ Load your ODT template
$template = new OdtTemplate('templates/template_with_tabstops.odt');
$template->load();

// 🏷️ Title paragraph
$title = new Paragraph('Title');
$title->addText('Different Ways to Set Tabs');

// 2️⃣ Create a Paragraph with inline tab stops
$par1 = new Paragraph();

$tabStops = [
    ['position' => 5.0, 'alignment' => 'left', 'text' => 'Item A', 'style' => ['bold' => true]],
    ['position' => 11.0, 'alignment' => 'right', 'text' => '€12.50', 'style' => ['color' => '#cfcfcf'], 'italic' => true]
];

// 🔵 🔹 Method: <center style="color:#349beb;"><code>addTabsWithTexts()</code></center>
$par1->addTabsWithTexts($tabStops);

// 📷 Image (optional visual support)
$img1 = new ImageElement('../assets/addsTabsWithTexts.png', ['width' => '7cm', 'align' => 'center', 'wrap' => 'none']);

// 3️⃣ Create a tabular structure
$rows = [
    ['Product', 'Price'],
    ['Widget', '€9.99'],
    ['Gadget', '€14.20'],
];

$par2 = new Paragraph();

// 🔵 🔹 Method: <center style="color:#349beb;"><code>addTabularLines()</code></center>
$par2->addTabularLines(
    $rows,
    $tabStops,
    ['color' => '#0066cc', 'InvoiceTable']
);

// 4️⃣ Key-Value tabbed line (e.g., totals)
$par3 = new Paragraph();

// 🔵 🔹 Method: <center style="color:#349beb;"><code>addKeyValueLine()</code></center>
$par3->addKeyValueLine('Subtotal', '€24.19', 11.0, ['italic' => true, 'bold' => true]);

// 5️⃣ Combine into a single rich content block
$rich = new RichText();

$rich->addParagraph($title)
    ->addParagraphBreak(2)
    ->addText('The following examples demonstrate how to use our tab handling to generate rich layouts with a single template variable.')
    ->addParagraphBreak(1)

    ->addParagraph('addTabsWithTexts', 'Heading 1',['underline'=>'single'])
    ->addParagraphBreak(1)
    ->addText('Use ')
    ->addText('addTabsWithTexts()', ['italic' => true, 'color' => '#349beb'])
    ->addText(' to insert tabs within a single line. For instance, ')
    ->addText('Item A', ['bold' => true])
    ->addText(' is placed at ')
    ->addText('5.0 cm (left aligned)', ['italic' => true])
    ->addText(', while the price is right-aligned at ')
    ->addText('11.0 cm in light gray.', ['bold' => true])
    ->addParagraphBreak(1)

    // 💡 Einführung
    ->addText('To add tabs to a single paragraph line, follow these three steps:')

    // 🔹 Step 1: Paragraph erstellen
    ->addParagraphBreak(1)
    ->addText('1️⃣ First, create a new Paragraph object:')
    ->addParagraphBreak(1)
    ->addParagraph('$par1 = new Paragraph();','Quotations',['font-size'=>'10pt','color'=>'#349beb', 'italic'=>true])

    ->addParagraphBreak(1)

    // 🔹 Step 2: Tabstops definieren
    ->addText('2️⃣ Then, define an array of tab stops with positions, alignment and optional styling:')
    ->addParagraphBreak(1)
    ->addParagraph('$tabStops = [
    [\'position\' => 5.0, \'alignment\' => \'left\', \'text\' => \'Item A\', \'style\' => [\'bold\' => true]],
    [\'position\' => 11.0, \'alignment\' => \'right\', \'text\' => \'€12.50\', \'style\' => [\'color\' => \'#cfcfcf\'], \'italic\' => true]
];','Quotations',['font-size'=>'10pt','color'=>'#349beb', 'italic'=>true])
    ->addParagraphBreak(2)

    // 🔹 Step 3: Tabs setzen
    ->addText('3️⃣ Finally, apply the tabbed content to your paragraph using:')
    ->addParagraphBreak(1)
    ->addParagraph(
        '$par1->addTabsWithTexts($tabStops);','Quotations',['font-size'=>'10pt','color'=>'#349beb', 'italic'=>true] )
    ->addParagraphBreak(2)

    // 🔎 Kontext
    ->addText('This will render a single line of text where "Item A" is aligned left at 5 cm, and "€12.50" is aligned right at 11 cm — both stylized individually.')
    ->addParagraphBreak(2)
    ->addParagraphBreak(2)
    ->addParagraph($par1)
    ->addParagraphBreak(2)

    ->addParagraph('addTabularLines', 'Heading 1',['underline'=>'single'])
    ->addParagraphBreak(1)
    ->addText('Use ')
    ->addText('addTabularLines()', ['italic' => true, 'color' => '#349beb'])
    ->addText(' to generate multiple tab-aligned rows. The first line can be styled independently as a header.')
    ->addParagraphBreak(1)
    ->addParagraph($par2)
    ->addParagraphBreak(1)

    ->addParagraph('addKeyValueLine', 'Heading 1', ['underline'=>'single'])
    ->addParagraphBreak(1)
    ->addText('Use ')
    ->addText('addKeyValueLine()', ['italic' => true, 'color' => '#349beb'])
    ->addText(' to create a classic key-value layout (e.g. totals).')
    ->addParagraphBreak(1)
    ->addParagraph($par3);

// 6️⃣ Insert into template
$template->setElement('tabular_block', $rich);

// 7️⃣ Save the final document
$template->save('output/final_invoice.odt');
