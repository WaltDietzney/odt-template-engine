<?php

require_once '../vendor/autoload.php'; // ggf. anpassen

use OdtTemplateEngine\Elements\ImageElement;
use OdtTemplateEngine\Elements\Paragraph;
use OdtTemplateEngine\Elements\Image; // falls du Images testen willst
use DOMDocument;
use OdtTemplateEngine\OdtTemplate;

$temp = new OdtTemplate('templates/test_paragraph_methods.odt');
$temp->load();

// Hilfsfunktion zur Anzeige
function prettyPrintDOM(DOMNode $node): void {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $imported = $dom->importNode($node, true);
    $dom->appendChild($imported);
    echo $dom->saveXML();
}

// Initialisiere das DOM
$doc = new DOMDocument('1.0', 'UTF-8');

// 1️⃣ Einfache Texte mit Formatierungen
$p1 = new Paragraph();
$p1->addText("Normaler Text. ")
   ->addText("Fetter Text. ", ['bold' => true])
   ->addText("Roter Text. ", ['color' => '#f00'])
   ->addLineBreak()
   ->addText("Text nach Zeilenumbruch. ")
   ->addTab()
   ->addText("Nach Tabulator.");

// 2️⃣ Aufzählung
$p2 = new Paragraph();
$p2->setBulleted()->addText("Erster Punkt einer Liste");

// 3️⃣ Nummerierung
$p3 = new Paragraph();
$p3->setNumbered('MyNumber')->addText("Erster nummerierter Punkt");

// 4️⃣ Hyperlink mit und ohne Stil
$p4 = new Paragraph();
$p4->addText("Ein Link: ")
   ->addHyperlink("OpenAI", "https://www.openai.com", ['underline' => true, 'color' => '#00f']);

// 5️⃣ Paragraph Style definieren
$p5 = new Paragraph('MyCustomStyle', [
    'margin-top' => '0.5cm',
    'margin-bottom' => '0.5cm',
    'text-align' => 'center'
]);
$p5->addText("Zentrierter Absatz mit Abstand oben/unten.");

// 6️⃣ Embedded Element (optional, falls du z. B. ein Image-Element hast)
 $image = new ImageElement('../assets/Logo.png', ['width'=>'5cm']);
 $p5->addElement($image);

 $temp -> setElement('paragraph1',$p1);
 $temp -> setElement('paragraph2',$p2); 
 $temp -> setElement('paragraph3',$p3);
 $temp -> setElement('paragraph4',$p4);
 $temp -> setElement('paragraph5',$p5); 


 $temp->save('output/output_test_paragraph.odt');

// 7️⃣ Ausgabe
echo "📄 Test: addText / addLineBreak / addTab\n";
prettyPrintDOM($p1->toDomNode($doc));

echo "\n\n📄 Test: setBulleted\n";
prettyPrintDOM($p2->toDomNode($doc));

echo "\n\n📄 Test: setNumbered\n";
prettyPrintDOM($p3->toDomNode($doc));

echo "\n\n📄 Test: addHyperlink\n";
prettyPrintDOM($p4->toDomNode($doc));

echo "\n\n📄 Test: ParagraphStyle\n";
prettyPrintDOM($p5->toDomNode($doc));

// Optional: Ausgabe aller Styles
echo "\n\n🧾 Style Definitions:\n";
print_r($p1->getStyleDefinitions());
print_r($p4->getStyleDefinitions());
print_r($p5->getStyleDefinitions());



