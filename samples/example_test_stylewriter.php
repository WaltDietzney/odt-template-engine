<?php

require_once '../vendor/autoload.php';

use OdtTemplateEngine\OdtTemplate;
use OdtTemplateEngine\Elements\RichText;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\RichTable;
use OdtTemplateEngine\Elements\RichTableCell;

$template = new OdtTemplate('templates/template_stylewriter.odt');

// 1️⃣ RichText mit gestyltem Text
$rich = new RichText();
$rich->addText("Hallo ", ['bold' => true, 'color' => '#3366cc']);
$rich->addText("Welt!", ['italic' => true, 'underline' => true, 'color' => '#cc0000']);
$template->setElement('richtextblock', $rich);

// 2️⃣ Paragraph mit Absatzstil
$heading = new Paragraph();
$heading->setParagraphStyle('MyHeading');
$heading->setParagraphStyleOptions([
    'text-align' => 'center',
    'margin-top' => '1cm',
    'margin-bottom' => '0.5cm',
    'background-color' => '#eeeeee'
]);
$heading->addText("Zentrierte Überschrift", ['bold' => true, 'font-size' => '16pt']);
$template->setElement('headingblock', $heading);

// 3️⃣ Tabelle mit gestylten Zellen
$table = new RichTable();
$table->addRow([
    new RichTableCell("Spalte 1", ['background' => '#ddeeff', 'border' => '0.05pt solid #000000', 'align' => 'center']),
    new RichTableCell("Spalte 2", ['background' => '#ccffcc', 'border' => '0.05pt solid #000000'])
]);
$table->addRow([
    new RichTableCell("Inhalt A"),
    new RichTableCell("Inhalt B")
]);
$template->setElement('tableblock', $table);

// 💾 Speichern
$template->save('output/test_stylewriter.odt');

echo "✅ Test erfolgreich gespeichert.\n";
